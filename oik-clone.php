<?php
/*
Plugin Name: oik clone
Plugin URI: http://www.oik-plugins.com/oik-plugins/oik-clone
Description: clone your WordPress content 
Version: 0.1
Author: bobbingwide
Author URI: http://www.oik-plugins.com/author/bobbingwide
License: GPL2

    Copyright 2014 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

/**
 * Function to invoke when loaded 
 * 
 */
function oik_clone_loaded() {
  //add_action( "wp_ajax_oik_clone", "oik_clone_oik_clone" );
  //add_action( "wp_ajax_nopriv_oik_clone", "oik_clone_nopriv_oik_clone" );
  add_action( "oik_admin_menu", "oik_clone_oik_admin_menu" );
  add_filter( 'set-screen-option', "oik_clone_set_screen_option", 10, 3 );
}  

/**
 * Implement "oik_admin_menu" 
 *
 * Note: add_submenu_page() automatically registers the function to implement the $hook
 * which is invoked after load-$hook. See wp-admin/admin.php 
 */
function oik_clone_oik_admin_menu() {
  $hook = add_submenu_page( 'oik_menu', 'oik clone', "Clone", 'manage_options', 'oik_clone', "oik_clone_admin_page" );
  add_action( "load-$hook", "oik_clone_add_options" );
  add_action( "admin_head-$hook", "oik_clone_admin_head" );
}

/**
 * Implement "load-$hook" for oik-clone
 *
 * where $hook="oik-options_page_oik_clone" 
 *
 * 
 */
function oik_clone_add_options() {
  $option = 'per_page';
  $args = array( 'label' => __( 'Items' )
               , 'default' => 3
               , 'option' => 'oik_clone_per_page'
               );
  add_screen_option( $option, $args );
}

/**
 * Implement "set-screen-option" for oik-clone
 * 
 * Do we need to check the option name?
 */
function oik_clone_set_screen_option( $setit, $option, $value ) {
  $isay = $setit;
  //bw_trace2();
  if ( $option == 'oik_clone_per_page' ) {
    $isay = $value;
  } else {
    bw_backtrace();
    gobang();
  }
  return( $isay );
}

/**
 * 
 * Implement "admin_head-oik-options_page_oik_clone" for oik-clone
 * 
 * When we're trying to display a List Table then hooking into 
 * nav-tabs is too late to load the classes since 
 * WordPress's get_column_headers() function invokes the hook to find the columns to be displayed.
 * and we need to have instantiated the class in order for this hook to have been registered.
 * Therefore, we need to hook into "admin_head" and determine what's actually happening.
 * Actually, we can hook into the specific action for the page.
 * 
   `
 
 C:\apache\htdocs\wordpress\wp-content\plugins\oik-clone-wxr\oik-clone-wxr.php(72:0) 
 2014-10-29T16:29:11+00:00 2.482412 0.001367 171 cf=admin_head 7 0 27392328/27509456 F=208 ocw_admin_head(4) 
 pagenow admin.php
C:\apache\htdocs\wordpress\wp-content\plugins\oik-clone-wxr\oik-clone-wxr.php(73:0) 2014-10-29T16:29:11+00:00 2.483206 0.000794 172 
cf=admin_head 7 0 27392400/27509456 F=208 ocw_admin_head(6) hook_suffix oik-options_page_oik_clone
   `
 */
function oik_clone_admin_head() {
  $tab = bw_array_get( $_REQUEST, "tab", null );
  switch ( $tab ) {
    case "ms":
      oik_require( "admin/oik-clone-ms.php", "oik-clone" );
      oik_clone_ms_lazy_nav_tabs_oik_clone( $tab );
      break;
        
    case "self":
    case null:
      oik_require( "admin/oik-clone-self.php", "oik-clone" );
      oik_clone_lazy_nav_tabs_oik_clone( "self" );
      break;
        
    default:
      // Someone else will handle this
  }
}

/**
 * Display the oik-clone admin page
 * 
 * Implements "oik-options_page_oik_clone" action
 */
function oik_clone_admin_page() {
  //bw_backtrace();
  oik_require( "admin/oik-clone.php", "oik-clone" );
  oik_clone_lazy_admin_page();
}
  
oik_clone_loaded();

