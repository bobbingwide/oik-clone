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
	private $action; // The action to perform for a selected post
	private $slave_id; // The slave ID against which to perform the action
	private $show_same; // True if we want to display items with "None" in the action.

	function __construct() {
		$this->slave = null;
		$this->clone_post_type = null;
		$this->posts = [];
		$this->table = [];
		$this->do_admin_page();
	}

	function do_admin_page() {
		$slave_list = $this->oik_clone_slave_form_validate();
		if ( $slave_list ) {
			// If they've requested a list we don't perform the action.
		} else {
			$action      =$this->validate_action();
			$slave_id    =$this->validate_slave_id();
			$slave_fields=$this->validate_slave_form_fields();
			if ( $action && $slave_id && $slave_fields ) {
				oik_box( null, 'slave_action', 'Processing', [ $this, 'oik_clone_slave_action' ] );
			}
		}
		oik_box( null, 'slave_form', "Slave post selection", [ $this, 'oik_clone_slave_form' ] );
		oik_box( null, 'slave_posts', 'Posts', [ $this, 'oik_clone_slave_posts' ] );
		oik_menu_footer();

	}

	/**
	 * Performs the requested action against the slave.
	 *
	 * The action and slave_id should have been validated
	 * Now we have to find which parts of the mapping are needed to perform the action!
	 *
	 */
	function oik_clone_slave_action() {
		do_action( 'oik-clone-slave-action-' . $this->action );
	}

	function oik_clone_slave_form() {
		$slave_list = $this->oik_clone_slave_form_validate();
		if ( $slave_list ) {
			$slave_list = $this->validate_slave_form_fields();
		}
		$this->oik_clone_slave_form_display();
		if ( $slave_list ) {

		}
		return $slave_list;
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

	function validate_action() {
		$action = bw_array_get( $_REQUEST, 'action', null );
		switch ( $action ) {
			case 'import':
				add_action( 'oik-clone-slave-action-import', [$this, 'oik_clone_slave_action_import'] );
				break;
			case 'pull':
				add_action( 'oik-clone-slave-action-pull', [$this, 'oik_clone_slave_action_pull'] );
				break;
			case 'push':
				add_action( 'oik-clone-slave-action-push', [ $this, 'oik_clone_slave_action_push'] );
				break;
			default:
				$action = null;
		}
		$this->action = $action;
		return $this->action;
	}

	function validate_slave_id() {
		$this->slave_id = null;
		$slave_id = bw_array_get( $_REQUEST, 'slave_id', null );
		if ( is_numeric( $slave_id ) ) {
			$this->slave_id = $slave_id;
		}
		return $this->slave_id;
	}

	/**
	 * Imports a new post from the chosen slave ID.
	 */
	function oik_clone_slave_action_import() {
		p( "Processing import..." );
		p( "Slave ID: " . $this->slave_id );
		oik_require( 'admin/class-oik-clone-reconcile.php', 'oik-clone' );
		$oik_clone_reconcile = new Oik_clone_reconcile();
		$oik_clone_reconcile->set_slave( $this->slave );
		$oik_clone_reconcile->set_slave_url( $this->slave );
		$oik_clone_reconcile->set_post_type( $this->clone_post_type );
		$oik_clone_reconcile->set_verbose( true );
		$oik_clone_reconcile->set_dry_run( false );
		$oik_clone_reconcile->import( $this->slave_id );
	}

	/**
	 * Pushes an update to the slave.
	 */
	function oik_clone_slave_action_push() {
		p( "Processing push..." );
		p( "Slave ID: " . $this->slave_id );
		oik_require( 'admin/class-oik-clone-reconcile.php', 'oik-clone' );
		$oik_clone_reconcile = new Oik_clone_reconcile();
		$oik_clone_reconcile->set_slave( $this->slave );
		$oik_clone_reconcile->set_slave_url( $this->slave );
		$oik_clone_reconcile->set_post_type( $this->clone_post_type );
		$oik_clone_reconcile->set_verbose( true );
		$oik_clone_reconcile->set_dry_run( false );
		$oik_clone_reconcile->push_updates( $this->slave_id );
	}

	/**
	 * Pulls an update from the slave.
	 */
	function oik_clone_slave_action_pull() {
		p( "Processing pull..." );
		p( "Slave ID: " . $this->slave_id );
		oik_require( 'admin/class-oik-clone-reconcile.php', 'oik-clone' );
		$oik_clone_reconcile = new Oik_clone_reconcile();
		$oik_clone_reconcile->set_slave( $this->slave );
		$oik_clone_reconcile->set_slave_url( $this->slave );
		$oik_clone_reconcile->set_post_type( $this->clone_post_type );
		$oik_clone_reconcile->set_verbose( true );
		$oik_clone_reconcile->set_dry_run( false );
		$oik_clone_reconcile->pull_updates( $this->slave_id );
	}

	function validate_slave_form_fields() {
		$this->validate_slave();
		$this->validate_clone_post_type();
		$this->validate_show_same();
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

	function validate_show_same() {
		$show_same = bw_array_get( $_REQUEST, 'show_same', 'on' );
		//echo $show_same;
		$this->show_same = $show_same ;
	}

	/**
	 * Displays the slave field as a select list of the slaves listed in servers
	 */

	function display_slave_field() {
		$slaves = bw_get_option( "slaves", "bw_clone_servers" );
		$slaves = bw_as_array( $slaves );
		$slaves = bw_assoc( $slaves );
		//print_r( $slaves );
		//bw_textfield( 'slave', 80, 'Slave', $this->slave );
		//bw_select( 'slave', )
		bw_select( 'slave', "Slave", $this->slave, array( '#options' => $slaves ) );
	}

	/**
	 * Displays the clone post type as a select list of cloneable post types.
	 */
	function display_post_type_field() {
		// bw_textfield( 'clone_post_type', 32, 'Post type', $this->clone_post_type );.
		oik_clone_post_type_select( $this->clone_post_type );

	}

	function display_show_same_checkbox() {
		bw_checkbox( 'show_same', 'Display reconciled content', $this->show_same );
	}

	/**
	 * Displays the Slave post selection form
	 */
	function oik_clone_slave_form_display() {
		bw_form();
		stag( 'table', "form-table" );

		$this->display_slave_field();
		$this->display_post_type_field();
		$this->display_show_same_checkbox();

		etag( "table" );
		p( isubmit( "_oik_clone_slave_list", "List posts", "button-primary" ) );
		//BW_::p( isubmit( "_oik_clone_slave__edit_settings", __( "Change plugin", null ), null, "button-primary" ));
		e( wp_nonce_field( "_oik_clone_slave_list", "_oik_clone_slave", false, false ) );
		etag( "form" );
	}

	function oik_clone_slave_posts() {
		//p( "posts go here");
		oik_require( 'admin/class-oik-clone-reconcile.php', 'oik-clone' );
		$oik_clone_reconcile = new Oik_clone_reconcile();
		$oik_clone_reconcile->set_slave( $this->slave );
		$oik_clone_reconcile->set_slave_url( $this->slave );
		$oik_clone_reconcile->set_show_same( $this->show_same );
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
