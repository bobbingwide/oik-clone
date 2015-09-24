<?php // (C) Copyright Bobbing Wide 2015

/**
 * Settings page for oik_clone servers
 * 
 * Register the function that responds to the request to display the detail for the "servers" tab
 *
 */ 
function oik_clone_lazy_nav_tabs_servers() {
  add_filter( "bw_nav_tabs_oik_clone_servers", "oik_clone_servers_sections", 10, 3 );
	
	oik_require( "admin/bw-nav-tab-sections.php", "oik-clone" );
	$section = bw_nav_tabs_section( "basic" );
	bw_trace2( $section, "section" );
  add_action( "oik_clone_nav_tab_servers", "oik_clone_lazy_nav_tab_servers" );
	// not necessary to load anything for the basic section
	add_action( "oik_clone_nav_tab_load-basic", "oik_clone_nav_tab_load_basic" );
	add_action( "oik_clone_nav_tab_load-advanced", "oik_clone_nav_tab_load_advanced" );
	
	do_action( "oik_clone_nav_tab_load-$section" );
}

/**
 * 
 */
 function oik_clone_nav_tab_load_basic() {
   //gobang();
 }

/**
 * Load the class to process the servers tab advanced section
 *
 * We need to load it early since we need to define the columns in the table
 * that appear in the Screen options tab.
 * 
 */
function oik_clone_nav_tab_load_advanced() {
  global $list_table;
	oik_clone_nav_tab_advanced_fields();
  oik_require( "includes/oik-list-table.php" );
	
	$args = array( "plugin" => "oik-clone", "tab" => "servers", "page" => "oik_clone", "section" => "advanced" );
	$args['actions'] = array( "edit" => __( 'Edit', 'oik-clone' )
													, "view" => __( 'View', 'oik-clone' )
													, "delete" => __( 'Delete', 'oik-clone' )
													); 
	$args['page_actions'] = array( "add" => __( 'Add new', 'oik-clone' ) );
												
  $args['submit_actions'] = array( "edit_update" => __( 'Update', 'oik-clone' )
                                , "add_entry" => __( 'Add', 'oik-clone' )
 															 );
	$args['object_type'] = 'bw_clone_servers'; 
	$args['option_field'] = 'servers'; 												
  $list_table = bw_get_list_table( "OIK_Clone_Servers_List_Table", $args );
}

/**
 * Define the fields for oik-clone Servers Advanced
 *
 * Uses the oik-fields API to define the fields that are stored in the "bw_clone_servers" options
 * Note: The oik-fields API should be implemented as a shared library. 
 */
function oik_clone_nav_tab_advanced_fields() {
	bw_register_field( 'slave', 'URL', __( "Server", 'oik-clone' ) );
	bw_register_field( 'apikey', 'text', __( "API key", 'oik-clone' ) );
	bw_register_field( 'active', 'checkbox', __( "Active?", 'oik-clone' ) );
	bw_register_field( 'matched', 'number', __( "Matched to", 'oik-clone' ) );
	
	bw_register_field_for_object_type( 'slave', 'bw_clone_servers' );
	bw_register_field_for_object_type( 'apikey', 'bw_clone_servers' );
	bw_register_field_for_object_type( 'active', 'bw_clone_servers' );
	bw_register_field_for_object_type( 'matched', 'bw_clone_servers' );
	
}	

/**
 * 
 * Implement "oik_clone_nav_tab_servers" 
 *
 * Display the detail for the servers tab
 * 
 */
function oik_clone_lazy_nav_tab_servers() {
	add_action( "oik_clone_nav_tab_servers_basic", "oik_clone_server_options" );
	add_action( "oik_clone_nav_tab_servers_advanced", "oik_clone_server_options_advanced" );
	// Should we implement this action to allow other servers to register their hooks? 
	// do_action( "oik_clone_nav_tab_servers" ); 
	
	oik_require( "admin/bw-nav-tab-sections.php", "oik-clone" );
	$section = bw_nav_tabs_section_list( "basic" );
	if ( $section ) { 
	
		do_action( "oik_clone_nav_tab_servers_$section" );
	} else {
		gobang();
	}
  bw_flush();
	
	//
	//
  //oik_box( null, null, "Settings", "oik_clone_server_options" );
	//oik_box( null, null, "Advanced", "oik_clone_server_options_advanced" );
  oik_menu_footer();
}

/**
 * Display the oik-clone Servers form
 *
 * 
 * Single fields
 * - CSV separated list of servers
 * - server API key
 * 
 * 
 */ 
function oik_clone_server_options() {
  //bw_flush();
  $option = "bw_clone_servers";
  $options = bw_form_start( $option, "oik_clone" );
  bw_textfield_arr( $option, "Servers", $options, "slaves", 80 ); 
	bw_textfield_arr( $option, "Reclone servers, for [clone] shortcode", $options, "reclone", 80 );
  bw_textfield_arr( $option, "Server API key", $options, "apikey", 32 );
  etag( "table" );       
  p( isubmit( "ok", "Save changes", "button-primary"  ) ); 
  etag( "form" );
}


/**
 * Display the oik-clone Servers advanced form
 *
 * Support a tabular display with multiple fields:
 * - slave
 * - client API key
 * - active - true if the server is to be listed in "Clone on update"
 * - matched - highest post ID when the clone was first made
 *
 * Future fields
 * - REST - true if the server supports the REST API
 * - Inbound - true if we allow inbound requests
 * - Auto forward - true if we allow inbound requests and will auto forward
 * 
 * Single fields
 * - server API key
 * 
 * 
 */
function oik_clone_server_options_advanced() {
	//p( "Advanced settings page" );
	
	//p( "Now we want to process and display the options array bw_clone_servers['slaves'] using BW_List_Table logic" );
	
	
	//oik_require( "admin/oik-clone-servers-list-table.php", "oik-clone" );
	//$servers = new OIK_Clone_Servers_List_Table( 'bw_clone_servers' );
	
	
  global $list_table;
	bw_trace2( $list_table, "LIST" );
	$list_table->perform_submit_action();
	
	
	
	$list_table->perform_action();
	$list_table->display_page_actions();
	
  $list_table->prepare_items();
	
	/*
	 * Now build the list if a submit action has been performed
	 */
	
  //$list_table->prepare_items();
	
	
  bw_flush();
  //$list_table->match_posts();
  $list_table->display();
   



}


 
/**
 * Implement "bw_nav_tabs_oik_clone" filter for oik-clone
 *
 *
 * Return the nav tabs supported by oik-clone
 * @TODO - the filter functions should check global $pagenow before adding any tabs - to support multiple pages using this logic
 * @TODO - support 'section' -  like WooCommerce - So that the Authentication area for WP-API is simpler
 */
function oik_clone_servers_sections( $nav_tabs_sections, $page, $tab ) {
  $nav_tabs_sections['basic'] =  __( "Basic", "oik-clone" );
  $nav_tabs_sections['advanced'] = __( "Advanced", "oik-clone" );
	bw_trace2( $nav_tabs_sections, "nav_tabs_sections" );
  return( $nav_tabs_sections );
}
