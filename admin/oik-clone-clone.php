<?php // (C) Copyright Bobbing Wide 2015


/**
 * Clone the post identified by the "payload" and/or "target" fields in the AJAX body
 *
 * - The payload is in JSON format. 
 * - We need to strip slashes before it can be converted back into an object.
 * - The mapping is in JSON format.
 * 
 * @return ID - the target ID of the post created/updated... if it worked.
 *
 */
function oik_clone_lazy_clone_post() {
  $payload = bw_array_get( $_REQUEST, "payload", null );
  $target_id = bw_array_get( $_REQUEST, "target", null ); 
  bw_trace2( $payload, "payload", false );
  if ( $payload ) {
    $payload = stripslashes( $payload );
    $post = json_decode( $payload, false );
    
    bw_trace2( $post, "post", false );
    if ( is_object( $post ) ) {
      $source_id = $post->ID;
      
      //p( "Updating post $id" );
      
      // We need to load the target to make sure this is what we're supposed to be updating
      // 
      $target_id = oik_clone_attempt_import( $source_id, $target_id, $post );
      
      
      
    } else {
      p( "Payload is not an object" );
    }
  } else {
    p( "Missing payload" );
  }
  return( $target_id );

}

/**
 * Determine the ID of the post to update
 * 
 * source_id | target_id | processing
 * --------- | --------- | -----------
 * x         | 0         | Attempt to find target by match on GUID. see below for more
 * x         | x         | Load the selected target - assume it really is a match
 * x         | y         | Load the selected target - assume it really is a match 
 *  
 * Notes:
 *
 * When the target_id is 0 it means the master doesn't know the slave's post id.
 * If we find a match by GUID then we return that target_id 
 * if we don't we create a new post and return the new post_id
 *
 * @TODO - Having assumed it's a match we can still check the GUID 
 * or check the "_oik_clone_ids" post meta for the target post.
 * 
 * If the post ID for the master (requester) is present then it's a match.
 * But if it's not present we don't really know.
 *
 * @param ID $source_id - the ID of the source post
 * @param ID $target_id - the ID that the source thinks the target might me. 0=unknown
 * @param object $post - the source post object - containing the GUID of the source post
 * @return ID - the target post to update. May be null.
 */
function oik_clone_determine_target_id( $source_id, $target_id, $post ) {
  $matched_target_id = 0;
  if ( 0 == $target_id ) {
    $matched_target_id = oik_clone_find_target_by_GUID( $post );
  } else {
    $target = get_post( $target_id ); 
    if ( $target ) {
      $matched_target_id = $target->ID;
    }
  }
  bw_trace2( $matched_target_id, "matched target id" );
  return( $matched_target_id );
}

/**
 * Try finding a match by GUID
 *
 * @TODO - write logic to find the 'best' if there is more than one
 *
 * Notes:
 * - The post type has to match
 * - Since we're only passing a single post type, which may be "attachment" or "page",
 *   we have to ensure the post_parent parameter will not be used in the query
 * - 'attachment's have a post_status of 'inherit'... we don't pass the post status
 * - Does this mean we can't clone 'private' posts,
 * - or should we be passing the master's post_status?
 * - Actually, all we want to do is find the post. 
 * - We're going to set the status to the same as the source.
 *
 * @param object $source - the source post object 
 * @return integer - the ID of the 'best' matching post or null
 */
function oik_clone_find_target_by_GUID( $source ) {
  oik_require( "includes/bw_posts.inc" );
  oik_require( "admin/oik-clone-match.php", "oik-clone" );
  $args = array( "numberposts" => -1 
               , "post_type" => $source->post_type
               , "post_parent" => "ignore"
               );
  oik_clone_match_add_filter_field( "AND guid = '" . $source->guid . "'" );            
  $posts = bw_get_posts( $args );
  $target_post = bw_array_get( $posts, 0, null );
  bw_trace2( $target_post, "target_post", false );
  $target_id = bw_array_get( $target_post, "ID", null );
  bw_trace2( $target_id, "target_id" );
  return( $target_id );
}

/**
 * Import the contents into the target post
 * 
 * Attempt to load the post that's been asked for.
 * 
 * 
 * If the target post is what we hope it is.
 * If it's not then we have to decide:
 * - Do we try to find the matching post
 * - or should we already know it
 * If we should already know it then it should have been listed in the post meta data on the client.
 * Maybe we should have loaded the post by guid in the first place.
 * 
 * @param ID $source - the ID of the source post
 * @param ID $target - the ID of the target post
 * @param array $post - the post to clone
 * 
 */
function oik_clone_attempt_import( $source, $target, $post ) { 
  oik_require( "admin/oik-clone-actions.php", "oik-clone" );
  oik_require( "admin/oik-clone-relationships.php", "oik-clone" );
  $media_file = null;
  
  if ( $post->post_type == "attachment" ) {
    oik_require( "admin/oik-clone-media.php", "oik-clone" );
    $media_file = oik_clone_save_media_file( $post->post_date );
    $post->file = $media_file['file'];
  }
  
  $target_id = oik_clone_determine_target_id( $source, $target, $post );
  if ( $target_id ) {
    $target_post = oik_clone_load_target( $target_id );
    if ( $target_post ) {
      oik_clone_apply_mapping( $post );
      oik_clone_update_target( $post, $target_id ); 
    } else {
      p( "That's odd" );
    }  
  } else {
    p( "Looks like we'll have to create it" );
    
    oik_clone_apply_mapping( $post );
    
    $target_id = oik_clone_insert_post( $post );
    if ( $target_id && $media_file ) {
      oik_clone_update_attachment_metadata( $target_id, $media_file['file'] );
    }
    oik_clone_update_post_meta( $post, $target_id );
  }
  
  
  // update the post meta with the master post ID of the source ... regardless of the current source
  // Can we trust [HTTP_USER_AGENT] => WordPress/4.1.1; http://qw/oikcom ?
  // If not we'll have to pass it.
  // Or get it from???
  oik_require( "admin/oik-save-post.php", "oik-clone" );
  $master = bw_array_get( $_REQUEST, "master", null );
  oik_clone_update_slave_target( $target_id, $master, $source );
  return( $target_id );
}

