<?php // (C) Copyright Bobbing Wide 2014

/**
 * Validate the action against the given parameters
 *
 *
 * -import - Copy the source content into a new post
 * -update - Update the local (target) post 
 * -compare - Compare the contents of each post
 * -delete - Delete the local (target) post
 * -undo   - Undo the most recent revision to the local (target) post
 * 
 * action  | source | target | Processing
 * ------- | ------ | ------ | ---------
 * import  | -      | -      | Invalid - neither source not target set 
 * import  | -      | set    | Invalid - no
 * import  | set    | -      | Import into a new post with the same post type - or selected post type     
 * import  | set    | set    | Update the target post with the contents from the source - including ALL post meta
 * update  | -      | -      | Invalid 
 * update  | -      | set    | Invalid
 * update  | set    | -      | Invalid - action should be import - so we just do that
 * update  | set    | set    | same as for Import
 * delete  | n/a    | -      |
 * delete  | n/a    | set    |
 * undo    | set    |        |
 * compare | set    | set    | Compare the two posts. Any other combination is invalid
 *
 * @TODO Should we invoke a filter to find out which actions are supported or do we make them classes and invoke a method?
 * 
 * 
 * 
 */
function oik_clone_validate_action( $action, $source, $target ) {
  $valid = false;
  switch ( $action ) {
    case "import":
      if ( $source ) { 
        p( "Performing import" );
        if ( $target ) {                        
          p( "updating post $target from $source" );
          $action = "update";
        } else {
          p( "creating new post from $source" );
        }
      } else {
        p( "Invalid - no source specified" );
      } 
      $valid = $action;
      break;
    
    case "update":
      if ( $source ) { 
        p( "Performing update" );
        if ( $target ) {                        
          p( "updating post $target from $source" );
        } else {
          p( "creating new post from $source" ); 
          $action = "update";
        }
      } else {
        p( "Invalid - no source specified" );
      } 
      $valid = $action;
      break;
      
    case "compare":
      if ( $source && $target ) {
        $valid = $action;
      }  
      break;
      
    case "delete":
    case "undo": 
      // It must be the target that's set
      
    case "view":
      // It must be either the source of the target that's set
      
    case "edit":
      // It must be the target that's set
      
    
    default:
      // It's invalid
  }
  return( $valid );    
}

/**
 * Validate and perform the requested action(s)
 
 */
function oik_clone_lazy_perform_actions() {

  $action = oik_clone_action();
  $source = bw_array_get( $_REQUEST, "source", null );
  $target = bw_array_get( $_REQUEST, "target", null );


  $action = oik_clone_validate_action( $action, $source, $target );
  if ( $action ) {
    p( "Performing $action for source $source target $target" );
    $action_func = "oik_clone_perform_$action"; 
    if ( is_callable( $action_func ) ) {
      $action_func( $source, $target ); 
    } else {
      p( " $action_func not defined " );
    }
    
    // Shall we call do_action or what?
    do_action( "oik_clone_perform_$action", $source, $target ); 
  } else { 
    p( "Invalid request." );
  }
}

/** 
 * Perform the import into the specified target
 *
 * If no target is specified it's an import, otherwise it's an update.
 * So we can use the same logic for both Import and Update.
 *
 * We need to import the post and the post meta data
 * 
 */ 
function oik_clone_perform_import( $source, $target ) {
  $post = oik_clone_load_source( $source ); 
  if ( $post ) {
    if ( $target ) {
      oik_clone_update_target( $post, $target );
    } else {
      $new_post = oik_clone_insert_post( $post );
      oik_clone_update_post_meta( $post, $new_post );
    }
  } else {
    p( "Failed to load $source" );
  }
}

/** 
 * Perform the update into the specified target
 *
 * We need to import the post and the post meta data
 * The post meta in the target may be completely replaced
 *
 */ 
function oik_clone_perform_update( $source, $target ) {
  oik_clone_perform_import( $source, $target );
}

/**
 * Update the target post with the contents from the (source) post
 *
 * Load the target post just to confirm that it's there.
 * Then we basically ignore it since just about everything else comes from the source $post
 * BUT that's just daft since wp_update_post loads the post too!
 *
 * @param object $post - the complete source post, including post_meta
 * @param ID $target - the ID of the local post to update
 * 
 */
function oik_clone_update_target( $post, $target ) {
  $target_post = get_object_vars( $post );
  unset( $target_post['guid'] );
  $target_post = wp_slash( $target_post );
  $target_post['ID'] = $target;
  $result = wp_update_post( $target_post, true );
  bw_trace2( $result, "wp_update_post result" );
  if ( $result != $target ) {
    e( "Something went wrong: $result <> $target " );
   
  } else {
    e( "Post modified" );
    oik_clone_update_post_meta( $post, $target );
  } 
}

/**
 * Update the post_meta data in the target post
 *
 * Here we simply load and delete then insert new entries. 
 * There is no optimisation based in existing key/value pairs.
 * 
 * @param object $post - which contains the post_meta array to create
 * @param ID $target - the ID of the post to update 
 * 
 */ 
function oik_clone_update_post_meta( $post, $target ) {
  oik_clone_delete_all_post_meta( $target );
  oik_clone_insert_all_post_meta( $post, $target );
}

/**
 * Delete all existing post_meta data for the target post
 *
 * @param ID $target - the target post ID
 */
function oik_clone_delete_all_post_meta( $target ) {
  $post_meta = get_post_meta( $target );
  bw_trace2( $post_meta, "post_meta", true );
  foreach ( $post_meta as $key=> $meta ) {
    bw_trace2( $meta, $key, false );
    foreach ( $meta as $value ) {
      e( "Deleting $key: $value" );
      delete_post_meta( $target, $key );
    }  
  }
}

/**
 * Insert all the post meta data
 * 
 * @param object $post - a post object including post_meta data
 * @param ID $target - the post ID of the target post
 */
function oik_clone_insert_all_post_meta( $post, $target ) {
  $post_meta = $post->post_meta;
  bw_trace2( $post_meta, "post_meta", true );
  foreach ( $post_meta as $key=> $meta ) {
    bw_trace2( $meta, $key, false );
    foreach ( $meta as $value ) {
      e( "Adding $key: $value" );
      add_post_meta( $target, $key, $value );
    }  
  }
} 





/**
 * Load the source from the selected source 
 *
 * Currently only catering for WPMS - _oik_ms_source
 * @TODO What about post_meta? 
 * @TODO We have to set the post parent correctly for any post we create
 * Attachments are dealt with separately - 
 * @TODO - Implement in OO to support multiple source instances
 * 
 */  
function oik_clone_load_source( $source ) {
  $post = null;
  $post = apply_filters( "oik_clone_load_source", $post, $source );
  return( $post );
}

/**
 * Create a new post locally
 *
 * @TODO What should we do with the guid?
 *
 * @param $post - post object which needs to become an array
 * @return $post_id - the ID of the newly created post
 * 
 */ 
function oik_clone_insert_post( $source_post ) {
  $post = (array) $source_post;

  unset( $post['ID'] );
  p( "Inserting post: " . $post['post_title'] );
  bw_trace2( $post );
  $post_id = wp_insert_post( $post, true );
  //$post_id = 123;
  if ( is_wp_error( $post_id ) ) {
    p( "oops" );
    bw_trace2( $post_id, "wperror", false );
  } else {
    p( "Post created: "  . $post_id );
  }  
  return( $post_id );
}

/**
 * Load the target post including post_meta data
 * 
 * This is the same as calling oik_clone_load_post() !
 *
 */
function oik_clone_load_target( $target ) {
  $post = oik_clone_load_post( $target );
  return( $post );
}


/**
 * Load ALL the information about a post 
 *
 * This includes the post_meta data 
 * 
 */  
function oik_clone_load_post( $post_id ) {  
  oik_require( "includes/bw_posts.inc" );
  $post = get_post( $post_id );
  $post_meta = get_post_meta( $post_id );
  //bw_trace2( $post_meta, "post_meta" );
  $post->post_meta = $post_meta;
  bw_trace2( $post, "post" );
  return( $post );
}


/**
 * Implement side by side comparison of two posts
 *
 * with a central bar to show if same or different?
 * Can this be done using another List Table? I imagine so
 * but then how do we handle multiple list tables on a page.
 * Hmm, tricky
 * Meanwhile we'll just use bw_tablerow() logic.  
 * 
 */
function oik_clone_perform_compare( $source, $target ) {

  $spost = oik_clone_load_source( $source ); 
  $tpost = oik_clone_load_target( $target ); 
  //bw_trace2( $spost, "spost" );
  //bw_trace2( $spost, "tpost" );
  
  $args = array( "plugin" => "oik-clone", "source" => $spost, "target" => $tpost );
  
  $compare_table = bw_get_list_table( "OIK_Clone_Compare_List_Table", $args );
  bw_trace2( $compare_table );
  $compare_table->display();
  bw_flush();
  
  /*
  bw_trace2( $tpost, "tpost" );
  if ( $spost && $tpost ) {
      stag( "table", "wide_fat" );
      comp( "post_title", $spost, $tpost );
    e( $spost->post_title );
    e( esc_html( $spost->post_content ) );
    e( $tpost->post_title );
    e( esc_html( $tpost->post_content ) );
  }
  */
}


  
