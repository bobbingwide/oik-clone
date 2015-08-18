<?php // (C) Copyright Bobbing Wide 2015

/**
 * Push media files from the master to the slaves
 *
 * oik-clone needs to be able to support the pushing of media files
 *
 * Requirements
 * 
 * - Provide the 'Clone on update' dialog for Media ( Attachments )
 * - Client should only need to send the attached file on the first clone request to a particular server
 * - Subsequent requests should not have to resend the file
 * - The file should be sent by an appropriate method
 *
 * Possible solutions
 *
 * - Send file separately, then perform the update
 * - Send the media as part of the JSON request
 * We're using the second option.
 *  
 * 
 * Notes from WP-API documentation
 *  
 * The attachment creation endpoint can accept data in two forms.
 * The primary input method accepts raw data POSTed with the corresponding content type set via the 
 * Content-Type HTTP header. 
 * This is the preferred submission method.
 * The secondary input method accepts data POSTed via multipart/form-data, as per RFC 2388. 
 *
 *  The uploaded file should be submitted with the name field set to "file", 
 * and the filename field set to the relevant filename for the file.
 * In addition, a Content-MD5 header can be set with the MD5 hash of the file, to enable the server to check for consistency errors. 
 * If the supplied hash does not match the hash calculated on the server, a 412 Precondition Failed header will be issued.
 *
 * That's the WP-API / JSON REST API method.
 * 
 * Here we're implementing a more pragmatic solution... to get it to work first of all.
 * 
 */

/**
 * Implement "edit_attachment" actions for oik-clone
 *
 * The parameter we receive is pretty useless, 
 * but we can find quite a lot from $_REQUEST
 * if it's really necessary.
 * 
 * @param ID $id - the post ID
 */
function oik_clone_lazy_edit_attachment( $id ) {
  oik_require( "admin/oik-save-post.php", "oik-clone" );
  oik_clone_publicize( $id, true );
}

/**
 * Return a JSON encoded array for attached media file
 *
 * The media array is expected to consist of:
 * 
 * - url - the attachment URL e.g. http://qw/oikcom/wp-content/uploads/2015/03/oik-clone-v0.6.zip
 * - file - the file name e.g.  oik-clone-v0.6.zip
 * - md5 - the MD5 calculated hash of the base64 encoded contents e.g. 5b3f0cf1c13169de99ad3e94d201d7cd
 * - data - the base64 encoded file contents. 
 * 
 * This array should resemble a file in $_FILES
 * 
 * - name - the file name
 * - type - the file type e.g. text/plain
 * - tmp_name - the filename where it's been stored temporarily 
 * - error 
 * - size 
 * 
 * BTW. No idea what we're going to do with large video files or mp3's
 * Note: We assume that "attachment_url" is set. It may not be needed.
 * 
 * @param ID $id - the post ID of the attachment 
 * @return string - the JSON encoded media file
 */
function oik_clone_load_media_file( $id, $payload ) {
  static $jmedia; 
  if ( empty( $jmedia ) ) {
    $media_file = array();
    if ( $payload->post_type == "attachment" ) {
      $media_file['url'] = bw_array_get( $_REQUEST, "attachment_url", null );
      $media_file['type'] = $payload->post_mime_type; 
      $file = get_post_meta( $id, "_wp_attached_file", true );
      $media_file['name'] = basename( $file );
      $full_file = oik_clone_determine_full_file( $file );
      $media_file['file'] = $full_file;
      
      $contents = file_get_contents( $full_file );
      
      $media_file['md5'] = md5( $contents ); 
      $base64 = oik_clone_load_media_file_base64( $contents );
      // $media_file['md5'] = md5( $base64 ); 
      $media_file['size'] = filesize( $full_file );
      bw_trace2( $media_file, "media_file" );
      $media_file['data'] = $base64;
    }  
    $jmedia = json_encode( $media_file ); 
  }
  return( $jmedia );
}

/**
 * Determine full file name
 * 
 */
function oik_clone_determine_full_file( $file ) {
  $upload_dir = wp_upload_dir();
  $basedir = $upload_dir['basedir'];
  $full_file = $basedir . "/". $file;
  return( $full_file );
}

/**
 * Return the upload month
 *
 * @param string $file attachment file name
 * @return string 
 *
 */
function oik_clone_get_upload_month( $file ) {
	$yyyy = substr( $file, 0, 4 );
	$mm = substr( $file, 5, 2 );
	$date = bw_format_date( "$yyyy-$mm-01 01:01:01" );
	bw_trace2( $date, "date" );
	return( $date );  

}

/**
 * Return a base64 encoded version of the file
 *
 * @TODO This routine needs to cater for files which aren't really where we think they are.
 * e.g. in oik-plugins ZIP files attached to oik_premiumversion posts are stored outside of the upload directory
 * Easy Digital Downloads uses its own folder too
 *
 * @param string $file - the value of _wp_attached_file
 * @return string - the base64 encoded version of the contents of the file
 *
 */   
function oik_clone_load_media_file_base64( $contents ) {
  //if ( strlen( $contents ) > 750000 ) {
  //  $contents = "Dummy file: $file. File too large for push. Replace using ftp";
  //  gobang(); 
  //}   
  $base64 = base64_encode( $contents );
  return( $base64 );
}

/**
 * Save the contents of the media file block as an actual media file
 *
 * If sent the media array is expected to consist of:
 * 
 * - url - the attachment URL - probably ignored
 * - name - the file name ( e.g. oik-clone-v0.6.zip )
 * - type - the type of the file ( e.g. application/zip )
 * - md5 - the MD5 calculated hash of the file
 * - data - the base64 encoded file contents
 * 
 * No idea what we're going to do with large video files or mp3's
 * We should also perform some validation on the file name; we don't want anything executable. 
 *
 * @param string $time - the original post date. It's used to put the attachment in the correct folder
 * @return array - the file array defining the temporary file created from the media or null 
 * 
 */
function oik_clone_save_media_file( $time ) {
  $media_file = null;
  $jmedia = bw_array_get( $_REQUEST, "media", null ); 
  if ( $jmedia ) {
    $jmedia = stripslashes( $jmedia ); 
    $media = json_decode( $jmedia, true );
    if ( $media ) {
      bw_trace2( $media, "media" );
      $url = bw_array_get( $media, "url", null );
      $name = bw_array_get( $media, "name", null );
      $type = bw_array_get( $media, "type", null );
      $data = bw_array_get( $media, "data", null );
      $md5 = bw_array_get( $media, "md5", null ); 
      $contents = oik_clone_validate_media_fields( $data, $md5 );
      if ( $contents ) {
        $tmp_file = oik_clone_write_tmp_file( $name, $contents );
        if ( $tmp_file ) {
          $media_file = oik_clone_write_media_file( $name, $type, $tmp_file, $time );
        }
      }
    }
  } else {
    $media_file = oik_clone_load_media_from_files( $time );
  } 
      
  return( $media_file );
}

/**
 * Extract the media information from the $_FILES array
 *
 * We also need to get additional fields
 * since this doesn't seem to be available any other way
 *
 
    [filecontent] => chunked
    [filesize] => 238647
    [filemd5] = a1b2c3d4e5f6g7h8etc
 */

function oik_clone_load_media_from_files( $time ) {
  bw_trace2( $_FILES, "_FILES" );
  $media_file = null;
  if ( isset( $_FILES['file'] ) ) {
    $tmp_file = $_FILES['file']['tmp_name'];
    $name = $_FILES['file']['name'];
    $type = $_FILES['file']['type'];
    $data = file_get_contents( $tmp_file );
  
    $filecontent = bw_array_get( $_REQUEST, "filecontent", null );
    $filesize = bw_array_get( $_REQUEST, "filesize", null );
    $filemd5 = bw_array_get( $_REQUEST, "filemd5", null );
    
    $contents = oik_clone_validate_media_fields( $data, $filemd5 );
    if ( $contents ) {
      $tmp_file = oik_clone_write_tmp_file( $name, $contents );
      if ( $tmp_file ) {
        $media_file = oik_clone_write_media_file( $name, $type, $tmp_file, $time );
      }
    }
  }
  return( $media_file );

}

/**
 * Validate the media file contents 
 *
 * Perform a simple check that the md5 value passed matches the md5 calculation
 * against the data passed. If so, decode the contens and return it.
 *
 * @param string $data - base64 encoded file contents
 * @param string $md5 - MD5 calculation for the base64 encoded contents
 * @return string - decoded contents
 */
function oik_clone_validate_media_fields( $data, $md5 ) {
  $contents = base64_decode( $data ); 
  $testmd5 = md5( $contents );
  if ( $md5 == $testmd5 ) {
    p( "MD5 OK" );
  } else {
    p( "MD5 mismatch: $testmd5 <> $md5" );
    // $contents = null;
  }
  //bw_trace2( $contents, "contents" );
  return( $contents );
}

/**
 * Write the contents to a temporary file 
 *
 * This file will be used by wp_handle_upload()
 * 
 * @param string $name - the file name
 * @param string $content - the contents for the file
 * @return string - the generated value for "tmp_name"
 */
function oik_clone_write_tmp_file( $name, $contents ) {
  $tmp_name = wp_tempnam( $name );
  file_put_contents( $tmp_name, $contents );
  return( $tmp_name );
}  

/**
 * Write the media file
 *
 * - No need to set size? 
 * - We can't call wp_handle_upload is_uploaded_file() fails
 * @TODO wp_insert_post only needs the 'file' part of $media_file
 *
 * @param string $name - the media file name
 * @param string $type - the media file type 
 * @param string $tmp_file - the name of the temporary file 
 * @return array - media file to pass to wp_insert_post()
 */
function oik_clone_write_media_file( $name, $type, $tmp_file, $time ) {
  $file = array();
  $file['name'] = $name;
  $file['type'] = $type;
  $file['tmp_name'] = $tmp_file;
  $overrides = array( "test_form" => false, "test_size" => false );                                    
  $media_file = wp_handle_sideload( $file, $overrides, $time );
  bw_trace2( $media_file, "media_file" );
  return( $media_file ); 
}


/**
 * Create the attachment metadata
 *
 * @param ID $target_id - the target attachment
 * @param string $media_file - the full file name of the attached file
 */
function oik_clone_update_attachment_metadata( $target_id, $media_file ) {
  $metadata = wp_generate_attachment_metadata( $target_id, $media_file );
  bw_trace2( $metadata, "attachment_metadata" );
  wp_update_attachment_metadata( $target_id, $metadata );
}





 
