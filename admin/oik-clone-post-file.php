<?php // (C) Copyright Bobbing Wide 2015

/**
 * Post a file using wp_remote_post()
 *
 * This is an extract from the WP-API documentation ( {@link http://wp-api.org/#media_create-an-attachment} )
 * taken April 2015.
 * 
 * The primary input method accepts raw data POSTed with the corresponding content type set via the Content-Type HTTP header. 
 * This is the preferred submission method.
 * 
 * In addition, a Content-MD5 header can be set with the MD5 hash of the file, 
 * to enable the server to check for consistency errors. 
 *
 * If the supplied hash does not match the hash calculated on the server, 
 * a 412 Precondition Failed header will be issued.
 *
 * @link http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Field_names
 *
 * @param string $url - either the REST API URL to create media ( e.g. http://example.com/wp-json/media ) or the OIK Ajax URL (tbc )
 * @param string $file - fully qualified source file name ( e.g. /apache/htdocs/wordpress/wp-content/uploads/filename.ext )
 * @param string $file_type - the file's MIME type ( ie. $post->post_mime_type )
 * @return mixed WP_Error or the details for the new post
 */
function bw_remote_post_file_only( $url, $file, $file_type ) {
  oik_require( "includes/oik-remote.inc" );
  $args = array();
  $args['headers']['Content-Type'] = $file_type;
  $args['headers']['Content-Length'] = filesize( $file );
  $args['headers']['Content-Disposition'] = "filename=" . basename( $file );
  $contents = file_get_contents( $file );
  $args['headers']['Content-MD5'] = md5( $contents );
  $args['body'] = $contents;
  $args['headers']['Authorization'] = bw_basic_authorization(); 
  $result = bw_remote_post( $url, $args );
  return( $result );
}

/**
 * Post content and a file using wp_remote_post()
 * 
 * Implement RFC2388 to post a file to a WordPress server along with other data
 *
 * We want to create/update an attachment in a single request.
 *
 * The intention is to use wp_remote_post() to post a multipart/form-data with
 *
 * - the original body as one part
 * - the file as another
 * - additional fields to make it work at the server end
 *
 * Each multipart is of the general form:
 *
 * boundary
 * 
 * `
 * --boundary
 * Content-Disposition: form-data; name="file"; filename="example.ext" `
 *
 * value
 * `
 *
 * There is a trailing boundary record
 * `
 * --boundary--
 * `
 *
 * Where: 
 * 
 * - The boundary field that separates the parts is defined in the Content-Type header
 * - When sending a file we include the filename= keyword.
 * - Data is just named with the appropriate field name
 * - Each line ends with \r\n
 * - The overall Content-Length is the length of the 'body' that's created from each of the parts
 * 
 * The following notes could be due to programming issues:
 * - File content is received as passed. If you base64 encode it on the client end it needs to be decoded on the server
 * - Get it slightly wrong and Apache mod_security may return an HTTP 403 
 *
 * @param string $url target URL for the request
 * @param array $args parameters 
 * @param string $file full file name of the media file
 * @param string $file_type file type e.g. image/jpeg
 * @return mixed - WP_Error or the details of the new post
 */
function bw_remote_post_file( $url, $args, $file, $file_type ) {
  oik_require( "includes/oik-remote.inc" );
  oik_require( "admin/class-oik-clone-post-file.php", "oik-clone" );
  $post_file = new OIK_clone_post_file();
  $result = $post_file->post( $url, $args, $file, $file_type );
  //bw_remote_post( $url, $args );
  return( $result );
}

/**
 * Return the Basic Authorization code
 *
 * We use the URL to allow multiple servers with different user:pass for each
 * 
 * @param string $url
 * @return string - the code made up from a constant found in wp-config.php
 */
function bw_basic_authorization( $url=null ) {
  $suffix = md5( $url );
  if ( defined( 'OIK_BASIC_AUTHORIZATION_'. $suffix ) ) {
    $basic_authorization = 'Basic ' . constant( 'OIK_BASIC_AUTHORIZATION_' . $suffix );
  } elseif ( defined( 'OIK_BASIC_AUTHORIZATION' ) ) {
    $basic_authorization = 'Basic ' . OIK_BASIC_AUTHORIZATION;
  } else {
    $basic_authorization = 'Basic ally_we_expect_this_to_fail';
  }
  return( $basic_authorization );
}




