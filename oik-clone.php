<?php
/*
Plugin Name: oik-clone
Plugin URI: http://www.oik-plugins.com/oik-plugins/oik-clone-clone-your-wordpress-content
Description: clone your WordPress content 
Version: 0.7
Author: bobbingwide
Author URI: http://www.oik-plugins.com/author/bobbingwide
License: GPL2

    Copyright 2014, 2015 Bobbing Wide (email : herb@bobbingwide.com )

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
 * Registers the actions and filters that make this plugin work
 */
function oik_clone_loaded() {
  add_action( "wp_ajax_oik_clone", "oik_clone_nopriv_oik_clone_post" );
  add_action( "wp_ajax_nopriv_oik_clone_post", "oik_clone_nopriv_oik_clone_post" );
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
  register_setting( 'oik_clone', 'bw_clone_servers', 'oik_plugins_validate' ); // No validation for oik-clone
  $hook = add_submenu_page( 'oik_menu', 'oik clone', "Clone", 'manage_options', 'oik_clone', "oik_clone_admin_page" );
  add_action( "load-$hook", "oik_clone_add_options" );
  add_action( "admin_head-$hook", "oik_clone_admin_head" );
  
  if ( !defined('DOING_AJAX') ) {
    add_action( "save_post", "oik_clone_save_post", 10, 3 );
    add_action( 'add_meta_boxes', 'oik_clone_add_meta_boxes', 10, 2 );
    add_action( "edit_attachment", "oik_clone_edit_attachment", 10, 1 );
    add_action( "add_attachment", "oik_clone_add_attachment", 10, 1 );
  }  
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
      oik_require( "admin/oik-clone-self.php", "oik-clone" );
      oik_clone_lazy_nav_tabs_oik_clone( "self" );
      break;
        
    case "servers":
    case null:
      oik_require( "admin/oik-clone-servers.php", "oik-clone" );
      oik_clone_lazy_nav_tabs_servers();
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

/**
 * Implement cloning when a post is saved 
 *
 * We invoke the logic as a lazy function.
 *
 * @param ID $id - the ID of the post being updated
 * @param post $post - the post object
 * @param bool $update - true more often than not
 */
function oik_clone_save_post( $id, $post, $update ) {
  oik_require( "admin/oik-save-post.php", "oik-clone" );
  oik_clone_lazy_save_post( $id, $post, $update );
}

/**
 * Implement "add_meta_boxes" for oik-clone
 *
 * Only add the box for post_type's that support publicize.
 * 
 * Note: We can add_post_type_support( $post_type, "publicize" ) 
 * using the oik-types plugin, or the logic can be in the theme/plugin
 * 
 * @param string $post_type - the post type for which the meta boxes are being created
 * @param object $post - the post object / comment / link
 */
function oik_clone_add_meta_boxes( $post_type, $post) {
  $publicize = post_type_supports( $post_type, "publicize" );
  if ( $publicize ) {
    oik_require( "admin/oik-clone-meta-box.php", "oik-clone" );
    add_meta_box( 'oik_clone', __( "Clone on update", "oik"), 'oik_clone_box', null, 'side', 'default'  );
  }  
}

/**
 * Implement "add_attachment" for oik-clone 
 *
 * Until we can find a valid reason for handling add_attachment differently
 * we'll just treat it as edit attachment.
 */
function oik_clone_add_attachment( $post_ID ) {
  oik_clone_edit_attachment( $post_ID );
}

/**
 * Implement "edit_attachment" for oik-clone
 * 
 * If we're publicizing attachments, which is more likely than not if you
 * want this thing to work nicely, then perform a lazy load to do the business
 *
 * @param ID $post_ID - the attachment post ID
 */
function oik_clone_edit_attachment( $post_ID ) {
  oik_require( "admin/oik-clone-media.php", "oik-clone" );
  if ( post_type_supports( "attachment", "publicize" ) ) {
    oik_clone_lazy_edit_attachment( $post_ID );
  }  
} 

/**
 * Implement AJAX oik_clone_post for oik-clone
 *
 * We use the same routine regardless of logged in status
 * But we always validate the API key
 *
 */
function oik_clone_nopriv_oik_clone_post() {
  oik_require( "admin/oik-clone-json.php", "oik-clone" );
  $target_id = 0;
  add_filter( 'oik_validate_apikey', 'oik_clone_oik_validate_apikey', 10, 2 );
  $continue = oik_clone_validate_apikey();
  if ( $continue ) {
    oik_require( "admin/oik-clone-clone.php", "oik-clone" );
    $target_id = oik_clone_lazy_clone_post();
  }   
  oik_clone_reply_with_json( $target_id );
  bw_backtrace();
  bw_flush();
  exit();
}
  
oik_clone_loaded();

