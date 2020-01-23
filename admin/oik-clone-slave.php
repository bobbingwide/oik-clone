<?php

/**
 * Implements Slave page in oik-clone admin.
 *
 * To support reconciliation with a slave server that's hosted remotely.
 * The master server is most likely to be a local server  and therefore has to be the one to initiate any pulling.
 * @copyright Copyright Bobbing Wide 2020
 * @package oik-clone
 */

function oik_clone_lazy_nav_tabs_slave() {
	//echo __FUNCTION__;
	add_action( 'oik_clone_nav_tab_slave', 'oik_clone_lazy_nav_tab_slave' );
	//oik_clone_slave_form();


}

function oik_clone_lazy_nav_tab_slave() {

	oik_require( 'admin/class-oik-clone-admin-slave.php', 'oik-clone' );
	$oik_clone_admin_slave=new Oik_clone_admin_slave();
}
