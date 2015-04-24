<?php // (C) Copyright Bobbing Wide 2015

/**
 * OIK_clone_post_file
 *
 * Clone the attachment and its media file
 * 
 
 */
class OIK_clone_post_file {

  public $boundary; 
  
  public $file;
  
  public $file_type;
  
  public $args;
  
  public $media;
  
  public $md5;
  
  /** 
   * Constructor for OIK_clone_post_file
   *
   */
  function __construct() {
    $this->set_boundary();
    $this->file = null;
    $this->file_type = null;
    $this->media = null;
    $this->md5 = null;
  }
  
  /**
   * Create the unique boundary string
   *
   * Here we prefix it with 123456 to make it the same length as the string produced by Chrome
   * Aids debugging
   *
   * ----WebKitFormBoundary0qDwJHhQrsM6UC9n
   * 123456ae51c529bafed977b1e5e39e24e97067
   *
   * @return string boundary code used between parts 
   */
  function set_boundary() {
    //$this->_mime_boundary = "Snoopy" . md5(uniqid(microtime()));
    $this->boundary = "123456" . md5( uniqid( microtime() ) );
    return( $this->boundary );
  }
  
  /**
   * Return a boundary line
   *
   * $end  | boundary line
   * ----- | ----------------
   * true  | --boundary--\r\n
   * false | --boundary\r\n
   *
   * When $end the boundary line starts and ends with "--"
   * There's only one CRLF.
   * 
   * @param bool $end - set to true for the last boundary line
   * @return string - boundary line
   */
  function boundary_line( $end=false ) {
    $boundary_line = "--"; 
    $boundary_line .= $this->boundary;
    if ( $end ) {
      $boundary_line .= "--"; 
    }
    $boundary_line .= PHP_EOL;
    return( $boundary_line );
  }
  
  /**
   * 
   */
  
  function body_disposition( $name="body" ) {
    $line = 'Content-Disposition: form-data; name="' . $name . '"' . PHP_EOL . PHP_EOL ;
    return( $line );
  }
  
  /**
   *
   * We might have one too many PHP_EOL here!
   *
   */
  function body() {
    //bw_trace2( $this->args['body'], "this args body" );
    $body = json_encode( $this->args['body'] );
    $body .= PHP_EOL;
    return( $body );
  }
  
  
  /**
   * Return the Content-Disposition for the file
   *
   */
  function file_disposition() {
    $file = basename( $this->file );
    $line = 'Content-Disposition: form-data; name="file"; filename="' . $file . '"' . PHP_EOL ; 
    return( $line );
  }
  
  /**
   * Return the Content-Type and encoding for the file
   *
   * Adding Transfer-Encoding seems to be a pointless exercise if the server doesn't cater for this automatically
   * It's also not clear if the server pays attention to the Content-Type other than to pass it through in the $_FILES array
   *
   * @return string - note the two EOLs 
   */
  function file_type() {
    $line = "Content-Type: " . $this->file_type . PHP_EOL . PHP_EOL;
    //$line .= "Content-Transfer-Encoding: base64" . PHP_EOL . PHP_EOL;
    //$line .= "Transfer-Encoding: chunked" . PHP_EOL . PHP_EOL;
    return( $line );
  }
  
  /**
   * Return the chunked base64 format of the file
   * 
   */
  function file_contents() {
    if ( $this->media ) {
      $base64 = $this->media->data;
      $this->md5 = $this->media->md5;
    } else {
      $contents = file_get_contents( $this->file );
      //if ( strlen( $contents ) > 750000 ) {
      //  $contents = "Dummy file: $file. File too large for push. Replace using ftp";
      //  gobang(); 
      //}   
      $base64 = base64_encode( $contents );
      $this->md5 = md5( $base64 );
    }  
    $chunked = chunk_split( $base64 );
    return( $chunked );
    //return( $contents );
  }
  
  /**
   *
   
    $filedata = "--" . $this->_mime_boundary . "\r\n";
                        $postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n\r\n";
                        $postdata .= "$file_content\r\n";
   */
  function attach_file( $file, $file_type ) {
    $this->file = $file;
    $this->file_type = $file_type;
    $filedata = $this->boundary_line();
    $filedata .= $this->file_disposition(); 
    $filedata .= $this->file_type();
    $filedata .= $this->file_contents();
    $filedata .= PHP_EOL;
    return( $filedata );
  }
  
  /**
   * Return the "body" as if it were multipart form data called "body"
   * 
   * We assume the body has already been encoded into a form the target understands
   * Nope
   *
   */
  function attach_body() {
    $filedata = null;
    foreach ( $this->args['body'] as $name => $value ) {
      $filedata .= $this->attach_var( $name, $value );
    } 
  
    //$filedata = $this->boundary_line();
    //$filedata .= $this->body_disposition();
    //$filedata .= $this->body();
    //$filedata .= PHP_EOL;
    return( $filedata );
  }
  
  /**
   * Attach a variable 
   *
   * We don't seem to be able to extract the 
   * information from within each part of the multipart message; 
   * perhaps the fields are only available in the headers.
   * 
   * So this is a quick and dirty way to add fields, just like on an HTML form
   * Very similar to the logic in the deprecated class Snoopy.
   * 
   */
  function attach_var( $name, $value ) {
    $filedata = $this->boundary_line();
    $filedata .= $this->body_disposition( $name );
    $filedata .= $value;
    $filedata .= PHP_EOL;
    //echo $filedata;
    return( $filedata );   
  }

  /**
   * Create the headers for the multipart form
   *
   * Note:
   * - Don't append PHP_EOL to the end of these settings
   * - WordPress adds them
   * - More than one and the server baulks 
   * {@link http headers **?**
   * 
   * 
   */
  function headers() {
    $this->args['headers']['Content-Type'] = "multipart/form-data; boundary=". $this->boundary;
    $this->args['headers']['Content-Length'] = strlen( $this->args['body'] );
    return( $this->args );
  } 
   
  /**
   * Post the body and file in a multipart form
   *
   * - The body has already been created in the $args
   * - We need to reproduce this as one of the multiparts
   * - The file comes from the other parameters
   *  
   *
   * 
   */
  function post( $url, $args, $file, $file_type ) {
    $args['headers']['Accept'] =  "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
    // $args['headers']['transfer-encoding'] = "base64";
    //$args['httpversion'] = '1.1';
    $this->args = $args;
  
    //$body = $args['body'];
    $this->detach_media();
    
    $new_body = "";
    $new_body = $this->attach_body();
    //$new_body .= $this->attach_var( "filename", $file );
    $new_body .= $this->attach_var( "filecontent", "chunked" );
    $new_body .= $this->attach_var( "filesize", $this->filesize( $file ) );
    $new_body .= $this->attach_file( $file, $file_type );
    $new_body .= $this->attach_var( "filemd5", $this->md5 );
    
    $new_body .= $this->boundary_line( true );
    //print_r( $new_body );
    // Now replace the body with the new body
    
    $this->args['body'] = $new_body;
  
    //print_r( $this->args );
     
    $this->headers();
    
    bw_trace2( $this->args, "this args" );
    $result = bw_remote_post( $url, $this->args );
    return( $result );
  }
  
  function filesize( $file ) {
    if ( $this->media ) {
      $filesize = $this->media->size;
    } else {
      $filesize = filesize( $file );
    }
    return( $filesize );
  }
  
  /**
   * Detach the media file if passed in the args
   *
   * The media is expected to be json_decoded by this time.
   *
   */
  function detach_media() {
    if ( isset( $this->args['body']['media'] ) ) {
      $this->media = $this->args['body']['media'];
      unset( $this->args['body']['media'] );
    } else {
      $this->media = null;
    }
    //bw_trace2( $this->media, "this media" );
  }
  


}
