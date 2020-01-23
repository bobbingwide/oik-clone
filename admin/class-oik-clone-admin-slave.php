<?php
/**
 * Oik_clone_admin_slave class manages the oik options > clone > Slave tab
 *
 * @copyright (C) Copyright Bobbing Wide 2020
 * @package oik-clone
 *
 */
class Oik_clone_admin_slave {

	private $slave;
	private $clone_post_type;
	private $posts;
	private $table;

	function __construct() {
		$this->slave = null;
		$this->clone_post_type = null;
		$this->posts = [];
		$this->table = [];
		$this->do_admin_page();
	}

	function do_admin_page() {

		oik_box( null, 'slave_form', "Slave", [ $this, 'oik_clone_slave_form' ] );
		oik_box( null, 'slave_posts', 'Posts', [ $this, 'oik_clone_slave_posts' ] );
		oik_menu_footer();

	}

	function oik_clone_slave_form() {

		$slave_list = $this->oik_clone_slave_form_validate();
		if ( $slave_list ) {
			$slave_list = $this->validate_slave_form_fields();
		}
		$this->oik_clone_slave_form_display();
		if ( $slave_list ) {
			p( "Getting posts" );
		}
	}

	function oik_clone_slave_form_validate() {
		$slave_list = bw_array_get( $_REQUEST, '_oik_clone_slave_list', null );
		if ( $slave_list ) {
			oik_require_lib( "bobbforms" );
			//oik_require_lib( "oik-honeypot" );
			//do_action( "oik_check_honeypot", "Human check failed." );
			$slave_list = bw_verify_nonce( "_oik_clone_slave_list", "_oik_clone_slave" );
		}
		return $slave_list;
	}

	function validate_slave_form_fields() {
		$this->validate_slave();
		$this->validate_clone_post_type();
		$slave_list = $this->slave && $this->clone_post_type;
		return $slave_list;

	}

	function validate_slave(){
		$slave = bw_array_get( $_REQUEST, 'slave', null );
		$this->slave = $slave;
	}
	function validate_clone_post_type(){
		$clone_post_type = bw_array_get( $_REQUEST, 'clone_post_type', null );
		$this->clone_post_type = $clone_post_type;
	}


	function oik_clone_slave_form_display() {

		bw_form();
		stag( 'table', "form-table" );
		//bw_flush();

		bw_textfield( 'slave', 80, 'Slave', $this->slave );
		bw_textfield( 'clone_post_type', 32, 'Post type', $this->clone_post_type );

		etag( "table" );
		p( isubmit( "_oik_clone_slave_list", "List posts", "button-primary" ) );
		//BW_::p( isubmit( "_oik_clone_slave__edit_settings", __( "Change plugin", null ), null, "button-primary" ));
		e( wp_nonce_field( "_oik_clone_slave_list", "_oik_clone_slave", false, false ) );
		etag( "form" );
	}

	function oik_clone_slave_posts() {
		p( "posts go here");
		oik_require( 'admin/class-oik-clone-reconcile.php', 'oik-clone' );
		$oik_clone_reconcile = new Oik_clone_reconcile();
		$oik_clone_reconcile->set_slave( $this->slave );
		$oik_clone_reconcile->set_slave_url( $this->slave );
		//$oik_clone_reconcile->get_post_types( $this->clone_post_type );
		//$oik_clone_reconcile->set_apikey();
		//$this->set_master();
		//$this->get_dry_run();
		//$this->get_verbose();
		$oik_clone_reconcile->sanity_check();
		//$oik_clone_reconcile->process_post_types();
		$oik_clone_reconcile->table_start();
		$oik_clone_reconcile->process_post_type( $this->clone_post_type );
		$oik_clone_reconcile->table_end();
	}

	function the_complicated_stuff() {

	/** Do we need any of this or can we survive on just one page?
	 * Maybe there's one tab per slave?


	add_filter( "bw_nav_tabs_oik_clone_slave", "oik_clone_slave_sections", 10, 3 );

	oik_require( "admin/bw-nav-tab-sections.php", "oik-clone" );
	$section = bw_nav_tabs_section( "basic" );
	bw_trace2( $section, "section" );
	add_action( "oik_clone_nav_tab_slave", "oik_clone_lazy_nav_tab_slave" );

	//add_action( 'oik_clone_nav_tab_slave', 'oik_clone_lazy_nav_tab_slave' );
	// not necessary to load anything for the basic section
	add_action( "oik_clone_nav_tab_load-basic", "oik_clone_nav_tab_load_basic" );
	add_action( "oik_clone_nav_tab_load-advanced", "oik_clone_nav_tab_load_advanced" );

	do_action( "oik_clone_nav_tab_load-$section" );
	 */
}

	/**
    * Implement "bw_nav_tabs_oik_clone" filter for oik-clone
	 *
	 *
	 * Return the nav tabs supported by oik-clone
	 * @TODO - the filter functions should check global $pagenow before adding any tabs - to support multiple pages using this logic
	 * @TODO - support 'section' -  like WooCommerce - So that the Authentication area for WP-API is simpler
    */
	function oik_clone_slave_sections( $nav_tabs_sections, $page, $tab ) {
		$nav_tabs_sections['basic'] =  __( "Basic", "oik-clone" );
		$nav_tabs_sections['advanced'] = __( "Advanced", "oik-clone" );
		bw_trace2( $nav_tabs_sections, "nav_tabs_sections" );
		return( $nav_tabs_sections );
	}


}
