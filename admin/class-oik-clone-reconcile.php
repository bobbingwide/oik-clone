<?php // (C) Copyright Bobbing Wide 2019

if ( PHP_SAPI !== "cli" ) { 
	die();
}

/**
 * Class: OIK_clone_reconcile
 * 
 * Reconcile the master posts with a slave server
 *
 * Similar to OIK_clone_reconcile this routine determines the status of posts on the server
 * then attempts to reconcile changes between master and slave.
 * This will involve pushing and pulling.
 *
 *
 * 
 */
class OIK_clone_reconcile{

	public $slave; 
	public $slave_url;
	public $apikey;
	public $master = null;
	public $post_type;
	public $mapping;

	/**
	 * Constructor for OIK_clone_reconcile
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
			echo "Syntax: oikwp class-oik-clone-reconcile.php slave" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-reconcile.php https://oik-plugins.co.uk" . PHP_EOL;
			echo "or, to reconcile with a local copy of the slave at s.b/oikcouk," . PHP_EOL;
			echo "oikwp class-oik-clone-reconcile.php https://oik-plugins.co.uk http://s.b/oikcouk " . PHP_EOL;
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
		$this->master = site_url( null, 'https');
		bw_trace2( $this->master, "master" );
	}
	
	function sanity_check() {
		if ( $this->slave_url == $this->master ) {
			die( "Slave and master should not be the same: " . $this->slave_url );
		} 
	}
	
	
	/**
	 * Reconcile IDs for each clonable post type
	 *
	 * foreach clonable post type
	 * - request the array of mappings of source to target IDs incl. cloned time stamp and last modified date.
	 * - reconcile the posts
	 *  	
	 */
	function process_post_types() {
		$post_types = get_post_types();
		//print_r( $post_types );
		foreach ( $post_types as $post_type ) {
			$supports = post_type_supports( $post_type, "clone" );
			if ( $supports ) {
				echo "Processing: $post_type" . PHP_EOL;
				$this->post_type = $post_type;
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
		echo $result;

		return $result;
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
			echo $this->post_type;
			$match = $post->post_type == $this->post_type;
			echo $post->post_name;
			echo $mapping->name;
			$match &= $post->post_name === $mapping->name;
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
			$this->reconcile( $post, $mapping );
			//$post_meta = oik_clone_update_slave_target( $mapping->id, $this->slave, $mapping->slave, $mapping->cloned );
		}
	}


	/**
	 * Reconciles the master post with the slave post.
	 *
	 * We believe we have matching posts ( they may not have the same ID or name ) so now we have to find out
	 * which is the more recent and which way we need to clone the post, if at all
	 * Each post has two important timestamps: post_modified_gmt and cloned date ( Unix format )
	 *
	 * master modified | master cloned  | slave modified | slave cloned
	 * --------------- | -------------- | -------------- | -----------
	 *
	 * Notes:
	 * - We have to allow for the fact that the slave's cloned timestamp may be
	 *   slightly different to the slave's modified timestamp.
	 * - Should we trust the cloned date on the slave to be the same as the cloned date on the master?
	 * - It would be difficult to explain how they got out of sync.
	 *
	 * @param $post
	 * @param $mapping
	 */
	function reconcile( $post, $mapping ) {
		$date_master_cloned = $this->get_date_master_cloned( $post, $mapping );
		echo PHP_EOL;
		$this->echo( "Reconciling:", $post->ID );
		$this->echo( "Cloned:", $date_master_cloned );
		$this->echo( "Modified:", $post->post_modified_gmt );
		$this->echo( "With:", $mapping->slave );
		$this->echo( "Name:", $mapping->name );
		$date_mapping_cloned = date( "Y-m-d H:i:s", $mapping->cloned );
		$this->echo( "Cloned:", $date_mapping_cloned  );
		$this->echo( "Modified:", $mapping->modified );

		$clone_mismatch = $date_master_cloned != $date_mapping_cloned;
		if ( $clone_mismatch ) {
			$this->echo( "Clone mismatch:", "Cloned: dates!" );

		}

		//if ( $date_mapping_cloned > $mapping->modified ) {
		//	$mapping->modified = $date_mapping_cloned;
		//}

		$date_master_cloned = $this->get_date_master_cloned( $post, $mapping );

		$master_changed_since_clone = $post->post_modified_gmt > $date_master_cloned;
		$slave_changed_since_clone = $this->has_slave_been_changed( $mapping );

		if ( $post->post_modified_gmt > $mapping->modified ) {
			if( $master_changed_since_clone ) {
				$this->echo( "Push:", $post->ID );
			} else {
				$this->echo( "Slave changed?", $slave_changed_since_clone );
			}
		} elseif ( $post->post_modified_gmt < $mapping->modified ) {
			if ( $slave_changed_since_clone ) {
				$this->echo( " Pull:", $mapping->id );
				$this->pull( $post, $mapping );
			} else {
				$this->echo( " Wacky:", "Master reverted perhaps?");
			}

		} else {
			$this->echo( "Match:" );
		}
		$this->echo();

		/*
		if ( $mapping->modified > $date_mapping_cloned ) {
			$this->echo("Pull this?" );
			gob();
		} elseif {

		}
		*/

	}

	function get_date_master_cloned( $post, $mapping ) {
		$master_cloned = null;
		$post_meta = get_post_meta( $post->ID, "_oik_clone_ids", false );
		$slaves = $post_meta[0];

		$slave_info = bw_array_get( $slaves, $this->slave_url, null);
		if ( $slave_info['id'] !== $mapping->slave ) {
			$this->echo( "Error:", "Clone mismatch");
		} else {
			$master_cloned = $slave_info['cloned'];
			$master_cloned = date( "Y-m-d H:i:s", $master_cloned );
		}

		return $master_cloned;
	}

	function has_slave_been_changed( $mapping ) {
		$cloned   = $mapping->cloned;
		$modified = strtotime( $mapping->modified );
		$this->echo( "Cloned:", $cloned );
		$this->echo( "Modified:", $modified );
		$diff = $cloned - $modified;
		$this->echo( "Diff:", $diff );

		$diff = absint( $diff );
		$changed = $diff >= 60;
		return $changed;
	}

	function pull( $post, $mapping ) {
		oik_require( "admin/oik-clone-pull.php", "oik-clone");
		$target_id = oik_clone_master_pull( $this->slave_url, $post, $mapping );
		if ( $target_id === $post->ID ) {
			$this->echo( "Pulled:", $this->slave_url );
		}
	}

	function echo( $prefix=null, $value=null ) {
		echo "$prefix $value";
		echo PHP_EOL;
	}


}
