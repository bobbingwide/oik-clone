<?php // (C) Copyright Bobbing Wide 2014, 2023


/**
 * Handle the "Self" tab for oik-clone
 *
 * The Self tab has been selected (it's default anyway) so we need to be ready to do something
 */
function oik_clone_lazy_nav_tabs_oik_clone( $tab ) {
  //bw_backtrace();
  add_action( "oik_clone_nav_tab_self", "oik_clone_lazy_nav_tab_self" );
  global $list_table;
  
  oik_require( "includes/oik-list-table.php" );
  $list_table = bw_get_list_table( "OIK_Clone_List_Table", array( "plugin" => "oik-clone", "tab" => $tab, "page" => "oik_clone" ) );
  // $_list_table = bw_get_list_table( "OIK_Clone_MS_List_Table", array( "plugin" => "oik-clone" ) ); 
  bw_trace2( $list_table );
}

/**
 * Load a local post
 * 
 * @param object $post - a post object - which may be null
 * @param ID - the ID of the source object to load
 * @return object - the populated post object.
 */
function oik_clone_load_self_source( $post, $source ) {
  $post = oik_clone_load_post( $source );
  return( $post );
}


/**
 * Define the action hooks and filters that we respond to
 * 
 * Each provider will implement its own functions.
 * The provider does not need to be OO.
 * 
 * oik_clone_load_source - filter to load the complete post from the source
 * oik_clone_match_post - common filter to match by GUID
 * 
 */
function oik_clone_self_register_hooks() { 
  add_filter( "oik_clone_load_source", "oik_clone_load_self_source", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_ID", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_GUID", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_slug", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_title", 10, 2 );
  //add_filter( "oik_clone_match_post", "oik_clone_match_post_by_content", 10, 2 );
}

/**
 * Implement "oik_clone_nav_tab_self" for oik-clone against itself
 *
 * Implement the oik-clone logic for the current local website
 * 
 */
function oik_clone_lazy_nav_tab_self() {
  //bw_backtrace();
  oik_clone_self_register_hooks();
  if ( oik_clone_action() ) { 
    oik_box( null, null, "Activity", "oik_clone_perform_actions" );
  }  
  //oik_box( null, null, "Selection criteria", "oik_clone_selection_criteria_self" );
  // @TODO Change from home grown to using BW_List_Table
  oik_box( null, null, "Posts", "oik_clone_self_list_table" );
  oik_menu_footer();
}

/**
 * Display the selected posts and find out what the user wants to do with them
 *
 * THIS IS THE "OLD" WAY OF DOING IT
 * 
 */
function oik_clone_selection_criteria_self() {
  gobang();
  $fields = bw_as_array( "ID,post_title,post_name,post_type,guid,post_modified" );
  $posts = oik_clone_list_self_posts( $fields );
  oik_require( "admin/oik-clone-match.php", "oik-clone" );
  $posts = oik_clone_match_posts( $posts, $fields );
  $fields['matched'] = 'matched';
  $fields[] = 'actions';
  //$fields['matched_title'] = 'matched_title';
  //oik_clone_display_header();
  oik_clone_display_posts( $posts, $fields );
}

/**
 * Display the oik-clone Self table
 * 
 * Implement the Self list table using standard WordPress admin list table logic.
 * 
 * Note that the screen ID generated ( oik-options_page_oik_clone ) is used to 
 * create the filters that other plugins can use to extend the behaviour.
 * But it's in the OIK_Clone_List_Table class that we define the default behaviour.
 * @TODO - Confirm the above statement.
 *
 * We have to decide who implements the business logic:
 * 1. Decide which columns to load ... inside prepare_items()
 * 2. Work out what page we're on  ... inside prepare_items()
 * 3. Load the items for the page  ... inside prepare_items()
 * 4. Fiddle about with matching   ... inside match_items()
 * 5. Display                      ... inside display() 
 * 
 */                               
function oik_clone_self_list_table() {
  global $list_table;
  bw_flush();
  $list_table->prepare_items();
  $list_table->match_posts();
  $list_table->display();
}

/**
 * List the most recent posts
 * 
 * 
 */   
function oik_clone_list_self_posts( $fields ) {
  gobang();
  oik_require( "includes/bw_posts.php" );
  $atts = array( "post_type" => "any" 
               , "orderby" => "ID"
               , "order" => "DESC"
               , "numberposts" => 30
               , "posts_per_page" => 5
               , 
               
               );
//  $atts = oik_clone
//  $posts = bw_get_posts( $atts ); 
  $posts = oik_clone_list_posts( $posts, $fields  );
  return( $posts );
}

/**
 * Perform actions against ourself
 */
function oik_clone_perform_actions_self() {

  p( "steady now" );
  //bw_flush();


}