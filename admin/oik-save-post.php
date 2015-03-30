<?php // (C) Copyright Bobbing Wide 2015

/**
 * Clone a saved post to slave sites
 *
 * When a post is "published" with a special "clone now" flag set
 * ... somewhere then we attempt to push the information to the slave site(s).
 *
 * This function is supported for post types which support 'publicize'
 * and which have the "clone to" post meta data set.
 * 
 * We need to ignore draft posts and newly created posts.
 
 * Special logic may be required for "deleted" posts 
 * 
 * @param ID $id - the ID of the post being saved
 * @param post $post - the post object
 * @param bool $update - true for an update, probably never false.
 
 */
function oik_clone_lazy_save_post( $id, $post, $update ) {
  bw_trace2();
  $post_type = $post->post_type;
  switch ( $post->post_status ) {
    case 'auto-draft':
    case 'draft':
    case 'future': 
    case 'private':
    break;
    
    case 'inherit':
      // what do we do with attachments?
      break;
  
    case 'publish':
      if ( post_type_supports( $post->post_type, "publicize" ) ) {
         oik_clone_publicize( $id, $post, $update );
      } else {
        // Post type does not support 'publicize' so we don't do anything
      }
    break;
    
    case 'trash':
      // One day we may also trash the slaves
    break;
    
    default: 
      echo "$id {$post->post_status}";
      gobang();
      break;
      
  }

}

/**
 * Return the slaves to publicize to
 *
 * @return array - array of slave servers to be shown as choices for cloning / replicating
 */
function oik_clone_get_slaves() {
  $slaves = bw_get_option( "slaves", "bw_clone_servers");
  if ( !$slaves ) {
    $slaves_arr = array( "http://qw/wordpress"
                       , "http://oik-plugins.biz"
                      );
  } else {
    $slaves_arr = bw_as_array( $slaves ); 
  }
  bw_trace2( $slaves_arr, "slaves array" );                   
  return( $slaves_arr );
}

/**
 * Get the requester API key
 * 
 * Note that there are requester and server API keys.
 * we need to find the API key to use to make the request on the target server
 * This will be different from the API key we check for when someone's making a request to our server
 *
 *
 * @TODO - Support requester API keys by server
 *
 * @return string - an API key - format decided by the server
 *
 */
function oik_clone_get_apikey() {
  if ( defined( "OIK_APIKEY" ) ) {
    $apikey = OIK_APIKEY;
  } else {
    $apikey = null;
  }
  return( $apikey );
}

/**
 * Return an array of target slaves
 *
 * This array should be validated and/or sanitized
 * OR is that the responsibility of the slaves themselves
 * 
 * We only need the slaves that are "on"
 * Note: This function may be called when posts are being programmatically created.
 *
 */
function oik_clone_get_target_slaves() {
  $slaves = bw_array_get( $_REQUEST, "slaves", null );
  $targets = array();
  if ( $slaves ) {
    foreach ( $slaves as $slave => $value ) {
      if ( $value == "on" ) {
        $targets[] = $slave;
      }
    }
  }  
  bw_trace2( $targets  );
  return( $targets );
}

/**
 * Publicize the update to the slaves
 *
 * Determine the slaves to update
 * If there are any load the complete post and json encode it
 * then for each slave
 * - find the target post id - from post_meta
 * - AJAX request the update
 * - obtain the actual target post ID
 * - update the post_meta for next time
 * 
 * Note: The post meta data should record target IDs for each slave
 *
 * @param ID $id - the post ID that's been updated
 * @param object $post - the post object
 * @param bool $update  
 */
function oik_clone_publicize( $id, $post, $update ) {
  $slaves = oik_clone_get_target_slaves();
  if ( count( $slaves ) ) {
    oik_require( "admin/oik-clone-actions.php", "oik-clone" );
    oik_require( "includes/oik-remote.inc" );
    $payload = oik_clone_load_post( $id );
    $jpayload = json_encode( $payload );
    oik_require( "admin/oik-clone-relationships.php", "oik-clone" );
    $relationships = oik_clone_relationships( $payload );
    foreach ( $slaves as $slave ) {
      $target = oik_clone_query_slave_target( $slave, $payload ); 
      $mapping = $relationships->mapping( $slave );
      $jmapping = json_encode( $mapping ); 
      $result = oik_clone_update_slave( $id, $jpayload, $slave, $target, $jmapping );
      $slave_id = oik_clone_determine_slave_id( $target, $result );
      if ( $slave_id != $target ) {
        $post_meta = oik_clone_update_slave_target( $id, $slave, $slave_id );
      }
      //gobang();
      //$payload =  
    }
  } else { 
    p( "No slaves to which to clone" );
  }
  //gobang();

}

/**
 * Determine the ID for the slave server
 *
 * @param ID $target - the post ID that we thought the target should have
 * @param mixed $result - the response from the server - may have a different target ID from first thought of 
 * @return ID - the slave_id
 */
function oik_clone_determine_slave_id( $target, $result ) {
  bw_trace2();
  //$slave_id = $target;
  $result = json_decode( $result );
  $slave_id = bw_array_get( $result, "slave", $target );
  return( $slave_id );
}

/**
 * Update the slave target, if necessary
 *
 * @param ID $id - post ID of the post that's been saved
 * @param string $slave - the slave host 
 * @param ID $slave_id - the slave's post ID
 *
 */
function oik_clone_update_slave_target( $id, $slave, $slave_id ) {
  $post_meta = get_post_meta( $id, "_oik_clone_ids", false );
  //bw_trace2( $post_meta );
  if ( $post_meta ) {
    //e( "Time to add the update logic" );
    //if ( isset( $post_meta[ $slave ] ) ) {
      $post_meta[0][ $slave ] = $slave_id;
    //} else {
    //  $post_meta[] = array( $slave => $slave_id );
    //
    //}  
  } else {
    $post_meta = array();
    $post_meta[] = array( $slave => $slave_id );
  }
  update_post_meta( $id, "_oik_clone_ids", $post_meta[0] ); 
  
  //bw_trace2( $post_meta, "post_meta" );
  return( $post_meta ); 
}
 

/**
 * Find the target ID of the post in the slave
 *
 * $payload contains the complete post and post_meta
 * within post_meta we should find "_oik_clone_ids"
 * which should be an associative array of post IDs keyed by host ( http://www.oik-plugins.com or qw/wordpress )
 * with the value representing the post ID used for this post.
 *
 * On exactly cloned systems the IDs are expected to be the same
 * On systems which are totally out of sync then it's a different ball game
 * 
 * `
 * array( http://site.com => 123, http://site.uk => 123, http://site.co.uk => 999 )
 * `
 */
function oik_clone_query_slave_target( $slave, $payload ) {
  $post_meta = bw_array_get( $payload, "post_meta", null );
  $slave_ids = bw_array_get( $post_meta, "_oik_clone_ids", null );
  if ( $slave_ids ) {
    $target = bw_array_get( $slave_ids, $slave, null );
  } else {
    $target = 0; // We can't pass null as this won't appear on the other end.
  }
  return( $target );
}

/**
 * Update the slave with the latest information
 * 
 * We pass the target ID, if known, to allow the post to be matched.
 * The target ID may vary depending on the server.
 *
 * When we first start cloning then we don't know the target post ID
 *
 * The AJAX call is expected to return a simple array
 *
 * {"narrative":null,"slave":0}
 * 
 * narrative | slave  | meaning
 * --------- | ------ | -------
 * null      | 0      | Most likely to be invalid API key 
 * whatever  | 0      | Unable to create/update the post. Maybe it's an invalid post type in the server.
 * whatever  | nnn    | This is the ID of the post in the target
 *
 * @param ID $id - the source ID
 * @param mixed $payload - the post object, including post_meta
 * @param string $slave - the slave server root
 * @param ID $target - the post ID on the target
 * @param string $mapping - the JSON encoded mapping
 * @return mixed - the result of the AJAX call 
 * 
 */
function oik_clone_update_slave( $id, $payload, $slave, $target, $mapping ) {
  $url = "$slave/wp-admin/admin-ajax.php" ;
  $body = array( "action" => "oik_clone_post" 
               , "master" => get_site_url()
               , "oik_apikey" => oik_clone_get_apikey()
               , "target" => $target 
               , "payload" => $payload
               , "mapping" => $mapping
               );
  $args = array( "body" => $body 
               , 'timeout' => 15
               ); 
  $result = bw_remote_post( $url, $args );
  bw_trace2( $result );
  return( $result );
}
