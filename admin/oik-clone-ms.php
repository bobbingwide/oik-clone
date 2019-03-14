<?php // (C) Copyright Bobbing Wide 2014

/**
 * Handle the MS tab for oik-clone
 *
 * The MS tab has been selected so we need to be ready to do something.
 * Note: We don't actually need the instance of $list_table for OIK_Clone_List_Table
 * but it does need to be loaded since OIK_Clone_MS_List_Table extends it.
 * @TODO - improve bw_get_list_table
 */
function oik_clone_ms_lazy_nav_tabs_oik_clone( $tab ) {
  //add_action( "oik_clone_nav_tab_wxr", "ocw_nav_tab_wxr" );
  global $ms_list_table;
  
  oik_require( "includes/oik-list-table.php" );
  $args = array( "plugin" => "oik-clone" 
               , "tab" => $tab
               , "page" => "oik_clone"
               );
  $list_table = bw_get_list_table( "OIK_Clone_List_Table", $args );
  $ms_list_table = bw_get_list_table( "OIK_Clone_MS_List_Table", $args ); 
  bw_trace2( $ms_list_table );
}


/**
 * Define the action hooks and filters that we respond to
 * 
 * Each provider will implement its own functions.
 * The provide does not need to be OO.
 * 
 * oik_clone_load_source - filter to load the complete post from the source
 * oik_clone_match_post - common filter to match by post ID
 * oik_clone_match_post - common filter to match by GUID
 
 * And this is where the magic may or mAY NOT HAPPEN.
 * 
 */
function oik_clone_ms_register_hooks() { 
  add_filter( "oik_clone_load_source", "oik_clone_load_ms_source", 10, 2 );
  add_action( "oik_clone_match_filters", "oik_clone_match_filters_ms" );
}

/**
 * Implement "oik_clone_match_filters" for oik-clone-ms
 *
 * Here we define the filter that we'll use when performing matches for MultiSite
 * 
 */
function oik_clone_match_filters_ms() {
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_ID", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_GUID", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_slug", 10, 2 );
  add_filter( "oik_clone_match_post", "oik_clone_match_post_by_title", 10, 2 );
}



/** 
 * oik-clone for Multi-Site
 * 
 * 
 */
function oik_clone_lazy_nav_tab_ms() {
  oik_clone_ms_register_hooks();
  //oik_menu_header( "Clone admin", "w100pc" );
  oik_box( null, null, "source site", "oik_clone_source_site_ms" );
  if ( oik_clone_action() ) { 
    oik_box( null, null, "Activity", "oik_clone_perform_actions" );
  }  
  oik_box( null, null, "MultiSite Posts", "oik_clone_ms_list_table" );
  oik_menu_footer();
}

/**
 * Dialog to discover which site to clone and parameters to use
 *
 * Note: This allows you to select the current site as the source site
 */
function oik_clone_source_site_ms() {
  global $blog_urls;
  if ( is_multisite() ) {
    $oik_ms_source = oik_clone_ms_source();
    $site = get_blog_details();
    bw_trace2( $site, "site", false );
    p( "Target site: {$site->blogname} ( {$site->siteurl} )" );
    p( "Choose the source site to compare against." );
    bw_form();
    stag( "table" );
    $blog_urls = bw_get_blog_urls();
    bw_select( "_oik_ms_source", "Source site", $oik_ms_source, array( '#options' => $blog_urls, '#optional' => true ) );


    oik_clone_post_type_select();

    
    etag( "table" );
    p( isubmit( "_oik_clone_list", "List content", null, "button-primary" ) );
    etag( "form" );
  } else { 
    p( "Only available in Multi-Site environment. Use Self" );
  }
  
  $unset_action = bw_array_get( $_REQUEST, "_oik_clone_list", null );
  if ( $unset_action ) {
    unset( $_REQUEST['action'] );
    unset( $_REQUEST['paged' ] );
  }
  //unset( $_REQUEST['action'] );
  //unset( $_REQUEST['source'] );
  //unset( $_REQUEST['target'] );
  
  
  
}




/**
 * Display the oik-clone MS table
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
function oik_clone_ms_list_table() {
  bw_flush();
  $oik_ms_source = oik_clone_ms_source();
  if ( $oik_ms_source ) {
    global $ms_list_table;
    $ms_list_table->set_source( "_oik_ms_source", $oik_ms_source);
    $ms_list_table->prepare_items();
    $ms_list_table->match_posts();
    $ms_list_table->display();
  }  
}



/**
 * Display the selected posts and find out what the user wants to do with them
 *
 * Don't display anything if no source is selected. 
 *
 * 
 */
function oik_clone_selection_criteria_ms() {
  $fields = bw_as_array( "ID,post_title,post_name,post_type,guid,post_modified" );
  $display_fields = bw_as_array( "actions,post_title,post_type,post_modified,ID");
  
  $oik_ms_source = oik_clone_ms_source();
  if ( $oik_ms_source ) {
    $posts = oik_clone_list_ms_posts( $oik_ms_source, $fields );
    oik_require( "admin/oik-clone-match.php", "oik-clone" );
    $posts = oik_clone_match_posts( $posts, $fields );
    //$fields['matched'] = 'matched';
    $fields[] = 'actions';
    //$fields['matched_title'] = 'matched_title';
    //oik_clone_display_header();
    
    // If we want to unset a field we need to remove it from the posts array as well
    // is this really necessary? 
    //
    unset( $fields['guid'] );
    oik_clone_display_posts( $posts, $display_fields );
  }
}


/**
 * 
 *  This just returns the blog IDs not the titles
 *  
 */  
function oik_clone_get_blog_list() {
  $blogs = array();
  if ( !function_exists( "bw_get_blog_list" ) ) {
    oik_require2( "shortcodes/oik-blogs.php", "oik-ms" );
  } 
  if ( function_exists(  "bw_get_blog_list" ) ) {
    $blogs = bw_assoc( bw_get_blog_list());
  } 
  return( $blogs );
} 

/**
 * Get an array of blog URLs given their IDs
 *
 * We need to know the URL and blog name in order to be able to make an
 * informed decision of which blog we should be copying from  
 *
 * @param array $blogs - array of blog IDs
 * @return array $blog_urls - array suitable for use as a select list
 * 
 */
function bw_get_blog_urls( $blogs=null ) {
  if ( !$blogs ) {
    $blogs = oik_clone_get_blog_list();  
  }
  $blog_urls = array();
  foreach ( $blogs as $id ) {
    $bloginfo = bw_get_bloginfo( $id );
    //if ( is_numeric( $id ) ) {
    //  $url = get_blogaddress_by_id( $id );
    //} else { 
    //  $url = get_blogaddress_by_name( $id );
    //}
    $blog_urls[ $id ] = $bloginfo->blogname . " ( " .  $bloginfo->siteurl .  " ) " ;
  }
  return( $blog_urls );
}


/**
 * List the publicly accessible posts from the blog
 *
 * The query will only list publicly queryable posts
 * 
 * It won't include:
 * post_type: revision, unregistered post types ( e.g. left around by deactivated plugins )
 * 
 * post_status: 'inherited', 'auto-draft', 'draft'
 *
 * It may include 'private'
 
 * @TODO Replace 'any' with the posts types on the source website
 * We can't import post types that aren't supported after all. 
 *
 * @TODO Implement a post type mapping facility
 
 * 
 * @param ID $blog_id - the blog ID of the MultiSite site
 * @param array $fields - the set of fields to select
 * @return array - array of posts, keyed by ID with the selected fields only 
 */
function oik_clone_list_ms_posts( $blog_id, $fields ) {
  oik_require( "includes/bw_posts.php" );
  switch_to_blog( $blog_id );
  $atts = array( "post_type" => "any" 
               , "orderby" => "ID"
               , "order" => "DESC"
               );
  $ms_posts = bw_get_posts( $atts ); 
  $posts = oik_clone_list_posts( $ms_posts, $fields  );
  restore_current_blog();
  return( $posts );
}

function oik_clone_load_ms_source( $post, $source ) {

  $oik_ms_source = oik_clone_ms_source(); 
  if ( $oik_ms_source ) {
    $post = oik_clone_load_ms_post( $oik_ms_source, $source );
  }
  // else {
  //  $oik_clone_source = oik_clone_source();
  //  $post = oik_clone_load_post( $oik_clone_source, $source );
  //}
  return( $post );
}

/**
 * Load the selected post from the MS blog
 *
 * @param ID $blog_id - ID of the WPMS blog
 * @param ID $post_id - ID of the post to load
 * @return $post - the loaded post
 */
function oik_clone_load_ms_post( $blog_id, $post_id ) {
  switch_to_blog( $blog_id );
  $post = oik_clone_load_post( $post_id );
  restore_current_blog();
  return( $post );
}


/**
 * Display the header to indicate what we're doing
 */
function oik_clone_display_header() {
  global $blog_urls;
  $id = get_current_blog_id();
  $current = bw_array_get( $blog_urls, $id, null );
  p( "Current site: $current" );
  $oik_ms_source = oik_clone_ms_source();
  $source_site = bw_array_get( $blog_urls, $oik_ms_source, null );
  p( "Source site: $source_site" );
}




/**
 * Return the currently selected MultiSite blog ID
 *
 * If the value is not set then obtain it from $_REQUEST['_oik_ms_source']
 * Do we need to validate it?
 * Return whatever we've got.
 * 
 * @return integer - MultiSite blog ID
 */                             
function oik_clone_ms_source() {
  static $_oik_ms_source = null;
  if ( null === $_oik_ms_source ) {
    $_oik_ms_source = bw_array_get( $_REQUEST, "_oik_ms_source", 0 );
  }
  return( $_oik_ms_source );
}

/**
 * Create an "Import new" or "Add new" link for the selected post
 * 
 * When the user chooses this link a new post is created.
 * We have to decide how we'll decide the post type for it.
 *
 */
function oik_clone_add_new_link( $post ) {
  bw_trace2( $post );
  
  $ID = $post['ID'];
  $blog_id = oik_clone_ms_source();
  $links = retlink( null, admin_url("admin.php?page=oik_clone&amp;tab=ms&amp;action=import&amp;source=$ID&amp;_oik_ms_source=$blog_id"), "Import new" ); 
  return( $links );
}

