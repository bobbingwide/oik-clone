<?php // (C) Copyright Bobbing Wide 2016

if ( PHP_SAPI !== "cli" ) { 
	die();
}

/**
 * Class: OIK_clone_reset_ids
 * 
 * Reset the cloned target IDs from a slave server
 *
 * Note: We don't expect there to be much in common with OIK_clone_reset_slave
 * so we're not presently inheriting from a common class.
 * Some methods may look similar.
 * 
 */
class OIK_clone_reset_ids {

	public $slave; 
	public $apikey;
	public $master = null;
	public $post_type;
	public $mapping;

	/**
	 * Constructor for OIK_clone_reset_ids
	 * 
	 * Controls the resetting of the _oik_clone_id
	 */
	function __construct() {
		oik_require( "includes/bw_posts.inc" );
		oik_require( "admin/oik-save-post.php", "oik-clone" );
		oik_require_lib( "class-oik-remote" );
		$this->set_slave();
		$this->set_apikey();
		$this->set_master();
		$this->process_post_types();
		gob();
	}
	
	/**
	 * Obtain the value for the slave
	 * 
	 * If not specified then die.
	 * If it is specified perhaps we should check it to be a valid URL
	 * or maybe we can determine the slave as the first from the list of slaves
	 */
	function set_slave() {
		$slave = oik_batch_query_value_from_argv( 1, null );
		if ( !$slave ) {
			echo PHP_EOL;																
			echo "Syntax: oikwp class-oik-clone-reset-ids.php slave" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-reset-ids.php http://oik-plugins.co.uk" . PHP_EOL;
			echo "or, to reset from a local copy of the slave at qw/oikcouk," . PHP_EOL;
			echo "oikwp class-oik-clone-reset-ids.php http://qw/oikcouk " . PHP_EOL;
			die( "Try again with the right parameters");
		}
		$this->slave = $slave;
		
	}
	
	/** 
	 * Retrieve the API key for the AJAX calls
	 */
	function set_apikey() {
		$this->apikey = oik_clone_get_apikey();
		bw_trace2( $this->apikey, "API key" );
	}
	
	
	/** 
	 * Set the master URL
	 */
	function set_master() {
		$this->master = get_site_url();
		bw_trace2( $this->master, "master" );
	}
	
	
	/**
	 * Reset IDs for each clonable post type
	 *
	 * foreach clonable post type
	 * - request the array of mappings of source to target IDs and the latest time stamp ( if available )
	 * - apply the mapping to the local post
	 *  	
	 */
	function process_post_types() {
		$post_types = get_post_types();
		//print_r( $post_types );
		foreach ( $post_types as $post_type ) {
			$supports = post_type_supports( $post_type, "clone" );
			if ( $supports ) {
				echo "Processing: $post_type" . PHP_EOL;
				$this->process_post_type( $post_type );
			}	else {
				echo "Skipping: $post_type" . PHP_EOL;
			}
		}
	}
	
	/**
	 * Send an AJAX request to the server
	 */
	function process_post_type( $post_type ) {
		$result = $this->request_mapping( $post_type );
		$this->apply_mapping( $result );
	}
	
	/**
	 * Request the mapping of cloned posts
	 * 
	 * The server is expected to reply with a JSON array consisting of the 
	 * narrative and the mappings
	 * where each mapping consists of: master_ID, slave_ID and time
	 * 
	 * @param string $post_type - the post type for which the mapping is requested
	 * @return array the result of the AJAX request
	 */
	function request_mapping( $post_type ) {
		$url = $this->slave . "/wp-admin/admin-ajax.php" ;
		$body = array( "action" => "oik_clone_request_mapping" 
								 , "master" => $this->master
								 , "oik_apikey" => $this->apikey
								 , "post_type" => $post_type
								 );
		$args = array( "body" => $body 
								 , 'timeout' => 30
								 ); 
		$result = oik_remote::bw_remote_post( $url, $args );
		bw_trace2( $result );
		return( $result );
	}
	
	/**
	 * Apply the mapping for each master ID
	 */
	function apply_mapping( $result ) {
		bw_trace2();
		gob();
	}
	
	

}
