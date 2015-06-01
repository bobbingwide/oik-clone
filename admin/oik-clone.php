<?php // (C) Copyright Bobbing Wide 2014, 2015

/**
 * Implement "bw_nav_tabs_oik_clone" filter for oik-clone
 *
 *
 * Return the nav tabs supported by oik-clone
 * @TODO - the filter functions should check global $pagenow before adding any tabs - to support multiple pages using this logic
 * @TODO - support 'section' -  like WooCommerce - So that the Authentication area for WP-API is simpler
 */
function oik_clone_nav_tabs( $nav_tabs, $tab ) {
  $nav_tabs['self'] = "Self";
  $nav_tabs['servers'] = "Settings";
  if ( is_multisite() ) {
    $nav_tabs['ms'] = "MultiSite";
  }  
  //$nav_tabs['basic'] = "WP-API Basic";
  //$nav_tabs['oauth1'] = "WP-API OAuth1";
  //$nav_tabs['custom'] = "WP-API Custom";
  //$nav_tabs['import'] = "Import";
  
  
  return( $nav_tabs);
}

/**
 * Implement "oik_admin_menu" action for oik-clone
 * 
 * @TODO - decide where we add the actions to register the responses to oik_clone_nav_tab_$tab
 * It's NOT in the loaded files, but how would an extension plugin work?
 * 
 * 
 */
function oik_clone_lazy_admin_page() {
  add_filter( "bw_nav_tabs_oik_clone", "oik_clone_nav_tabs", 10, 2);
  // Don't add this here - it's already added
  //add_action( "oik_clone_nav_tab_self", "oik_clone_nav_tab_self" );
  if ( is_multisite() ) {
    add_action( "oik_clone_nav_tab_ms", "oik_clone_nav_tab_ms" );
  }
  oik_menu_header( "Clone admin", "w100pc" );
  oik_require( "includes/bw-nav-tab.php" );  
  //$tab = bw_nav_tabs( "self", "Self" );
  $tab = bw_nav_tabs( "servers", "Settings" );
  //e( "%$tab%" );
  //oik_clone_reset_request_uri(); 
  do_action( "oik_clone_nav_tab_$tab" );
  bw_flush();
}

/**
 * Ensure the pagination links don't attempt to perform any actions
 * 
 * REQUEST_URI is used by BW_List_Table::pagination() to build the paging links
 * we need to ensure that only pagination is performed.
 * So we need to remove the fields that can be set on the action links
 */
function oik_clone_reset_request_uri() {
  //$request_uri = $_SERVER['REQUEST_URI'];
  $request_uri = remove_query_arg( array( "action", "source", "target" ) );
  //$request_uri = add_query_arg( "_
  $_SERVER['REQUEST_URI'] = esc_url( $request_uri );
}  

/**
 * Implement "oik_clone_nav_tab_self" to allow a little self inspection
 * 
 * Use self when you're working on a single site and have made some changes that you now want to apply to something else
 * OR maybe you just want to copy some content to create some new content.
 
 * Whatever use you find this at least allows the code to be developed and tested outwith a MultiSite environment
 * which is what the original purpose of the code was
 * AND enables us to work with different implementations without the complexity of WP-API or Import
 *
 */
function oik_clone_nav_tab_self() {
  oik_require( "admin/oik-clone-self.php", "oik-clone" );
  oik_clone_lazy_nav_tab_self();
}

/**
 * Implement "oik_clone_nav_tab_ms" to support 'cloning' from a MultiSite site
 *
 */
function oik_clone_nav_tab_ms() {
  oik_require( "admin/oik-clone-ms.php", "oik-clone" );
  oik_clone_lazy_nav_tab_ms(); 
}

/**
 * Return the action being requested
 * 
 * The actions we (intend to) support are:
 * ` 
 * import - Copy the content into a new post
 * update - Update the local post 
 * delete - Delete the local post
 * undo   - Undo the most recent revision to the local post
 * view   - View the selected post
 * edit   - Edit the selected post
 * `
 * @return string - the requested action... it might not be valid / implemented
 * 
 */
function oik_clone_action() {
  $action = bw_array_get( $_REQUEST, "action", null );
  return( $action );
}

/**
 * Perform the requested action
 *
 * In this first version only one action can be requested at a time
 * @TODO In the future we might support bulk actions with array values being passed
 */ 
function oik_clone_perform_actions() {
  oik_require( "admin/oik-clone-actions.php", "oik-clone" );
  oik_clone_lazy_perform_actions();
}

/**
 * Extract the fields from the post into an array
 * 
 * {@TODO - check how WordPress deals with fields that need to be prefixed with post_} 
 *
 * @param object $post - a post object 
 * @param array $fields - the names of the fields to extract
 * @param string $info - the value of the "Info" field for a matched line
 *
 * 
 */
function oik_clone_get_fields( $post, $fields, $info ) {
  $array = array();
  foreach ( $fields as $field ) {
    $array[$field] = $post->$field;
  }
  $array['info'] = $info; 
  return( $array );
} 

/**
 * Extract the key post information from the posts array
 *
 * @TODO - function should be called something else.
 *
 * @param array $json_posts
 * @param array $fields
 */
function oik_clone_list_posts( $json_posts, $fields ) {
  //bw_trace2();
  $posts = array();
  foreach ( $json_posts as $key => $post ) {
    $posts[] = oik_clone_get_fields( $post, $fields, null ); 
  }
  //bw_trace2( $posts, "reduced_posts", false );
  return( $posts );
}

/**
 * Implement "oik_clone_tablerow_matched" 
 * 
 * Filter the matched array to provide a list of possible posts to compare against
 *
 * If there's no match then we can offer "Add new" post type
 * This assumes that the post_type is available.
 * Actually we can provide Add new even if there are matches.
 * 
 * 
 */
function oik_clone_tablerow_matched( $post, $fields ) {
  $matches = $post['matched_posts'];
  $matched = null;
  foreach ( $matches as $ID => $match ) {
    //$matched = $match;
    $matched .= $ID;
    //$matched_title = $match['post_title'];  
    //$matched_name = $match['post_name'];

    bw_trace2( $match, "matched array");
  }
  //$post['matched'] = $matched;
  //$post['matched_title'] = $matched_title;
  //$post['matched_name'] = $matched_name;
  
  $post['matched'] = oik_clone_add_new_link( $post );
  
  return( $post );
}

/**
 * Display the posts retrieved
 * 
 * @param array $posts - array of post's key information
 */
function oik_clone_display_posts( $posts, $fields ) {
  gobang();
  stag( "table", "widefat" );
  $count = 0;
  add_filter( "oik_clone_tablerow", "oik_clone_tablerow_matched", 10, 2 );
   
  stag( "thead");
  bw_tablerow( $fields, "tr", "th" );
  etag( "thead" );
  foreach ( $posts as $key => $post ) {
    $post = apply_filters( "oik_clone_tablerow", $post, $fields );
    bw_tablerow( $post );
    bw_trace2( $post, "post", false );
    oik_clone_display_matched_posts( $post );
    $count++;
  }
  etag( "table" );
  p( "Total: $count" );
}

/**
 * Display the matched posts and the applicable actions
 * 
 *
 */
function oik_clone_display_matched_posts( $post ) {
  $matched_posts = $post['matched_posts'];
  foreach ( $matched_posts as $matched ) {
    bw_tablerow( $matched );
  }  
}


/**
 * Find one or more matches for the remote post
 * 
 * 
    $matched_posts = array();
 *  
 */
function oik_clone_match_post( $ID, $data, $fields ) {
  $matched = array();
  $matched = apply_filters( "oik_clone_match_post", $matched, $ID, $data, $fields );
  bw_trace2( $matched, "matched with" );
  return( $matched );
}



