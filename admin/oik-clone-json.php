<?php // (C) Copyright Bobbing Wide 2015

/**
 * Validate the API key from the point of view of the oik-clone server
 *
 * This is a very simple level of authentication. 
 *
 * @param bool $continue - true if the API key has been validated
 * @param string $passed_api_key - the value passed from the client
 *
 */
function oik_clone_oik_validate_apikey( $continue, $passed_api_key ) {
  if ( !$continue ) {
    $apikey = bw_get_option( "apikey", "bw_clone_servers" );
    if ( $apikey ) {
      $continue = $apikey == $passed_api_key;
    } 
  } 
  return( $continue );
} 

/**
 * Validate the apikey field
 *
 * @TODO - This invokes apply_filters in a non-standard way. The returned value is expected to be the first parameter.
 *
 * @return bool/null - indicator if the API key is valid
 */
function oik_clone_validate_apikey() {
  $apikey = bw_array_get( $_REQUEST, "oik_apikey", null );
  if ( $apikey ) {
    $apikey = apply_filters( "oik_validate_apikey", null, $apikey );
  } else { 
    p( "Missing oik_apikey" );
  }  
  return( $apikey );
}

/**
 * Reply with a JSON message
 * 
 * The requester is expecting something useful in the result; the 'slave' post ID
 * we also return any other rubbish created using bw_echo() as a field called 'narrative'
 * 
 * This should make it easier for the client to check what it receives.
 * This logic doesn't handle fatal errors or other plain text / HTML that's been accidentally produced
 *
 * @param ID $target_id - the ID of the post created/updated
 */ 
function oik_clone_reply_with_json( $target_id ) {
  $narrative = bw_ret();
  $result = array( "narrative" => $narrative
                 , "slave" => $target_id 
                 );
  $json = json_encode( $result );
  e( $json );
  bw_trace2( $json, "json" );
}



