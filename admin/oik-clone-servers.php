<?php // (C) Copyright Bobbing Wide 2015

/**
 * Settings page for oik_clone servers
 * 
 * Register the function that responds to the request to display the detail for the "servers" tab
 *
 */ 
function oik_clone_lazy_nav_tabs_servers() {
  add_action( "oik_clone_nav_tab_servers", "oik_clone_lazy_nav_tab_servers" );
}

/**
 * 
 * Implement "oik_clone_nav_tab_servers" 
 *
 * Display the detail for the servers tab
 */
function oik_clone_lazy_nav_tab_servers() {
  oik_box( null, null, "Settings", "oik_clone_server_options" );
  oik_menu_footer();
}

/**
 * Display the oik-clone Servers form
 *
 * @TODO - Eventually support a tabular display with multiple fields:
 * - slave
 * - client API key
 * 
 * Single fields
 * - server API key
 * 
 * 
 */ 
function oik_clone_server_options() {
  //bw_flush();
  $option = "bw_clone_servers";
  $options = bw_form_start( $option, "oik_clone" );
  bw_textfield_arr( $option, "Servers", $options, "slaves", 80 ); 
  bw_textfield_arr( $option, "Server API key", $options, "apikey", 32 );
  etag( "table" );       
  p( isubmit( "ok", "Save changes", "button-primary"  ) ); 
  etag( "form" );
}
