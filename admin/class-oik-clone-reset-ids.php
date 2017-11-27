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
	public $slave_url;
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
		oik_require( "includes/bw_posts.php" );
		oik_require( "admin/oik-save-post.php", "oik-clone" );
		oik_require_lib( "class-oik-remote" );
		$this->set_slave();
		$this->set_apikey();
		$this->set_master();
		$this->sanity_check();
		$this->process_post_types();
	}
	
	/**
	 * Obtain the value for the slave
	 * 
	 * If not specified then die.
	 * If it is specified perhaps we should check it to be a valid URL
	 * or maybe we can determine the slave as the first from the list of slaves
	 * 
	 * More importantly, we also need the slave's target URL - where we ask another server for the information that the real URL might tell us.
	 */
	function set_slave() {
		$slave = oik_batch_query_value_from_argv( 1, null );
		if ( !$slave ) {
			echo PHP_EOL;																
			echo "Syntax: oikwp class-oik-clone-reset-ids.php slave" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-reset-ids.php http://oik-plugins.co.uk" . PHP_EOL;
			echo "or, to reset from a local copy of the slave at qw/oikcouk," . PHP_EOL;
			echo "oikwp class-oik-clone-reset-ids.php http://oik-plugins.co.uk http://qw/oikcouk " . PHP_EOL;
			die( "Try again with the right parameters");
		}
		$this->slave = $slave;
		$this->slave_url = oik_batch_query_value_from_argv( 2, $slave );
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
	
	function sanity_check() {
		if ( $this->slave_url == $this->master ) {
			die( "Slave and master should not be the same: " . $this->slave_url );
		} 
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
		$mappings = $this->extract_mappings( $result );
		$this->apply_mappings( $mappings );
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
		$url = $this->slave_url . "/wp-admin/admin-ajax.php" ;
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
	 * Extract the mappings from the JSON result
	 */
	function extract_mappings( $result ) {
		bw_trace2( null, null, true, BW_TRACE_DEBUG );
		$result = oik_remote::bw_json_decode( $result );
		//bw_trace2( $result, "result", false );
		$mappings = bw_array_get( $result, "slave", array() );
		return( $mappings );
	}	
	
	/**
	 * Apply the mapping for each master ID
	 */
	function apply_mappings( $mappings ) {
		foreach ( $mappings as $mapping ) {
			$this->apply_mapping( $mapping );
		}
	}
	
	/**
	 * Apply the mapping to the master ID
	 * 
	 * We need to check the post type and post name ( slug ) before updating the clone IDs
	 * as the post ID returned from the slave might not actually match.
	 *
	 * Get the ID the slave reckons it should be and check if it's a match.
	 * If not found or not a match then try accessing the post by name, and if found, use the target ID returned locally.
	 * 
	 * @param object $mapping - mapping object for a cloned slave post
	 */
	function apply_mapping( $mapping ) {
		$match = false;
		$post = get_post( $mapping->id ); 
		if ( $post ) {
			$match = $post->post_type == $this->post_type;
			$match &= $post->post_name == $mapping->name;
		}
		if ( !$match ) {
			echo "Trying match by post name: " . $mapping->name . PHP_EOL;
			$post = bw_get_post( $mapping->name, $this->post_type );
			if( $post ) {
				$mapping->id = $post->ID;
				$match = true;
			}
		}
		if ( !$match ) {
			echo "Bad match on slave:" . $mapping->slave ." ". $mapping->name . PHP_EOL;
		} else {
			$post_meta = oik_clone_update_slave_target( $mapping->id, $this->slave, $mapping->slave, $mapping->cloned );
		}
	}


}
