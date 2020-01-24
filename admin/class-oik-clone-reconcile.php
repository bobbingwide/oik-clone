<?php
/**
 * Oik_clone_reconcile class.
 *
 * @copyright (C) Copyright Bobbing Wide 2020
 */

/**
 * Class: OIK_clone_reconcile
 *
 * Reconcile the master posts with a slave server
 *
 * Similar to OIK_clone_pull this routine determines the status of posts on the server
 * then attempts to reconcile changes between master and slave.
 * This will involve pushing and pulling.
 *
 *
 *
 */
class OIK_clone_reconcile {

	public $slave;          // The URL of the slave
	public $slave_url;      // The actual URL to use for the selected slave
	public $apikey;         // The oik-clone APIkey
	public $master=null;    // The master URL
	public $post_type;      // The clone post type
	public $mapping;        //
	public $dry_run;
	public $verbose;
	public $action;

	/**
	 * Constructor for OIK_clone_reconcile_batch
	 *
	 * Controls the resetting of the _oik_clone_id
	 */
	function __construct() {
		oik_require( "includes/bw_posts.php" );
		oik_require( "admin/oik-save-post.php", "oik-clone" );
		oik_require_lib( "class-oik-remote" );
		$this->set_apikey();
		$this->set_master();
		$this->dry_run = true;
		$this->verbose = false;


		/*
		 * The calling routine needs to do all these things
		$this->set_slave();
		$this->set_slave_url();


		$this->set_verbose();
		$this->set_dry_run();
		$this->sanity_check();
		$this->process_post_types();
		 */
	}

	/**
	 * Sets the value for the slave.
	 *
	 * More importantly, we also need the slave's target URL - where we ask another server for the information that the real URL might tell us.
	 * This is used when importing from a local instance of the remote site. See set_slave_url().
	 *
	 * @param string $slave The URL of the slave server
	 */
	function set_slave( $slave=null ) {
		if ( ! $slave ) {
			//p( "Choose a slave server");
		}
		$this->slave    =$slave;
	}

	/**
	 * Sets the value for the slave server when it's on a (different) local URL.
	 *
	 * @param string $slave_url The URL of the local instance of the slave server
	 */
	function set_slave_url( $slave_url=null ) {
		$this->slave_url = $slave_url;
	}

	/**
	 * Retrieve the API key for the AJAX calls
	 */
	function set_apikey() {
		$this->apikey=oik_clone_get_apikey();
		bw_trace2( $this->apikey, "API key" );
	}


	/**
	 * Set the master URL
	 */
	function set_master() {
		$this->master=site_url( null, 'https' );
		bw_trace2( $this->master, "master" );
	}

	function set_post_type( $post_type ) {
		$this->post_type = $post_type;
	}

	function set_dry_run( $dry_run = true ) {
		$this->dry_run = $dry_run;
		if ( $this->dry_run ) {
			$this->echo( "Dry run:", 'Yes' );
		} else {
			$this->echo( "Dry run:", 'No' );
		}
	}

	function set_verbose( $verbose = false ) {
		$this->verbose = $verbose;
		if ( $this->verbose ) {
			$this->echo( "Verbose:", 'Yes' );
		} else {
			$this->echo( "Verbose", 'No' );
		}
	}

	function sanity_check() {
		if ( $this->slave_url == $this->master ) {
			die( "Slave and master should not be the same: " . $this->slave_url );
		}
	}

	function get_post_types( $post_type) {
		//$post_type=oik_batch_query_value_from_argv( "post-type", null );
		if ( null !== $post_type ) {
			$post_types=explode( ',', $post_type );
		} else {
			$post_types=get_post_types();
		}

		return $post_types;

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
		$post_types=$this->get_post_types();
		//print_r( $post_types );
		foreach ( $post_types as $post_type ) {
			$supports=post_type_supports( $post_type, "clone" );
			if ( $supports ) {
				$this->echo( "Processing:", $post_type );
				$this->post_type=$post_type;
				$this->process_post_type( $post_type );
			} else {
				$this->echo( "Skipping:", $post_type );
			}
		}
	}

	/**
	 * Sends an AJAX request to the server.
	 */
	function process_post_type( $post_type ) {
		$this->post_type = $post_type;
		$result  =$this->request_mapping( $post_type );
		$mappings=$this->extract_mappings( $result );
		$this->apply_mappings( $mappings );
	}

	/**
	 * Requests the mapping of (cloned) posts.
	 *
	 * The server is expected to reply with a JSON array consisting of the
	 * narrative and the mappings
	 * where each mapping consists of: master_ID, slave_ID and time
	 *
	 * @param string $post_type - the post type for which the mapping is requested.
	 * @return array the result of the AJAX request.
	 */
	function request_mapping( $post_type ) {
		$url   =$this->slave_url . "/wp-admin/admin-ajax.php";
		$body  =array(
			"action"    =>"oik_clone_request_mapping"
		,
			"master"    =>$this->master
		,
			"oik_apikey"=>$this->apikey
		,
			"post_type" =>$post_type
		);
		$args  =array(
			"body"   =>$body
		,
			'timeout'=>30
		);
		$result=oik_remote::bw_remote_post( $url, $args );
		bw_trace2( $result );
		if ( ! is_wp_error( $result ) ) {
			$this->echo( "Result:", 'Mappings returned' );
		} else {
			$this->report_wp_error( $result );
			$result=null;
		}

		return $result;
	}

	function report_wp_error( $result ) {
		//print_r( $result );
		$error=$result->get_error_message();
		$code =$result->get_error_code();
		$this->echo( "WP Error: $code", $error );

	}

	/**
	 * Extract the mappings from the JSON result
	 */
	function extract_mappings( $result ) {
		bw_trace2( null, null, true, BW_TRACE_DEBUG );
		$result=oik_remote::bw_json_decode( $result );
		bw_trace2( $result, "result", false, BW_TRACE_DEBUG );
		$mappings=bw_array_get( $result, "slave", array() );

		return ( $mappings );
	}

	/**
	 * Apply the mapping for each master ID
	 */
	function apply_mappings( $mappings ) {
		foreach ( $mappings as $mapping ) {
			$this->set_action();
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
		$match=false;
		bw_trace2();


		//print_r( $mapping );
		$post=null;
		if ( null === $mapping->id ) {
			$this->echo( "Not cloned:", $mapping->slave );

		} else {
			$post=get_post( $mapping->id );
		}
		if ( $post ) {
			bw_trace2( $post, "post", false );
			$this->echo( "Title:", $post->post_title );
			$this->echo( "Post type:", $this->post_type );

			$match=$post->post_type == $this->post_type;
			bw_trace2( $match, "$post->post_type-$this->post_type", false );
			$this->echo( "Name:", $post->post_name );
			$this->echo( "Mapping:", $mapping->name );
			$match&=$post->post_name === $mapping->name;
		}
		if ( ! $match ) {
			$this->echo( "Trying match by post name:", $mapping->name );
			$post=bw_get_post( $mapping->name, $this->post_type );
			if ( $post ) {
				$mapping->id=$post->ID;
				$match      =true;
			}
		}
		if ( ! $match ) {
			$this->echo( "Bad match on slave:", $mapping->slave . " " . $mapping->name );
			$this->perform_import( $mapping );
		} else {
			$this->reconcile( $post, $mapping );
			//$post_meta = oik_clone_update_slave_target( $mapping->id, $this->slave, $mapping->slave, $mapping->cloned );
		}
		$this->summarise( $post, $mapping, $match );
	}

	function set_action( $action=null ) {
		$this->action=$action;
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
		$date_master_cloned=$this->get_date_master_cloned( $post, $mapping );
		//echo PHP_EOL;
		$this->echo( "Reconciling:", $post->ID );
		$this->echo( "Cloned:", $date_master_cloned );
		$this->echo( "Modified:", $post->post_modified_gmt );
		$this->echo( "With:", $mapping->slave );
		$this->echo( "Name:", $mapping->name );
		$date_mapping_cloned=date( "Y-m-d H:i:s", $mapping->cloned );
		$this->echo( "Cloned:", $date_mapping_cloned );
		$this->echo( "Modified:", $mapping->modified );

		$clone_mismatch=$date_master_cloned != $date_mapping_cloned;
		if ( $clone_mismatch ) {
			$this->echo( "Clone mismatch:", "Cloned: dates!" );

		}

		//if ( $date_mapping_cloned > $mapping->modified ) {
		//	$mapping->modified = $date_mapping_cloned;
		//}

		$date_master_cloned=$this->get_date_master_cloned( $post, $mapping );

		$master_changed_since_clone=$post->post_modified_gmt > $date_master_cloned;
		$slave_changed_since_clone =$this->has_slave_been_changed( $mapping );

		if ( $post->post_modified_gmt > $mapping->modified ) {
			if ( $master_changed_since_clone ) {
				$this->echo( "Push:", $post->ID );
				$this->push( $post, $mapping );
			} else {
				$this->echo( "Slave changed?", $slave_changed_since_clone );
				$this->pull( $post, $mapping );
				$this->set_action( "????" );
			}
		} elseif ( $post->post_modified_gmt < $mapping->modified ) {
			if ( $slave_changed_since_clone ) {
				$this->echo( "Pull:", $mapping->id );
				$this->pull( $post, $mapping );
			} else {
				$this->echo( " Wacky:", "Master reverted perhaps?" );
				$this->push( $post, $mapping );
				$this->set_action( "Wack" );
			}

		} else {
			$this->echo( "Match:", 'Yes' );
			$this->set_action( "None" );
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
		$master_cloned=null;
		$post_meta    =get_post_meta( $post->ID, "_oik_clone_ids", false );
		if ( $post_meta ) {
			$slaves=$post_meta[0];

			$slave_info=bw_array_get( $slaves, $this->slave_url, null );
			if ( $slave_info['id'] !== $mapping->slave ) {
				$this->echo( "Error:", "Clone mismatch" );
			} else {
				$master_cloned=$slave_info['cloned'];
				$master_cloned=date( "Y-m-d H:i:s", $master_cloned );
			}
		}

		return $master_cloned;
	}

	function has_slave_been_changed( $mapping ) {
		$cloned  =$mapping->cloned;
		$modified=strtotime( $mapping->modified );
		$this->echo( "Cloned:", $cloned );
		$this->echo( "Modified:", $modified );
		$diff=$cloned - $modified;
		$this->echo( "Diff:", $diff );

		$diff   =absint( $diff );
		$changed=$diff >= 60;

		return $changed;
	}

	function pull( $post, $mapping ) {
		$this->set_action( "Pull" );
		oik_require( "admin/oik-clone-pull.php", "oik-clone" );
		if ( $this->dry_run ) {
			$this->echo( "Dry pull:", $post->ID );
		} else {
			$target_id=oik_clone_master_pull( $this->slave_url, $post, $mapping );
			if ( $target_id === $post->ID ) {
				$this->echo( "Pulled:", $this->slave_url );
				$this->echo( "ID:", $target_id );
				//print_r( $mapping );
				//echo PHP_EOL;
			}
		}

	}

	function push( $post, $mapping ) {
		$this->set_action( "Push" );
		$this->echo( "Pushing:", $post->ID );
		$this->echo( "Slave:", $this->slave_url );
		$this->echo( "Mapping:", $mapping->slave );

		//oik_require( "admin/oik-save-post.php", "oik-clone" );
		$slaves=[ $this->slave_url ];
		if ( $this->dry_run ) {
			$this->echo( "Dry push:", $post->post_name );
		} else {
			oik_clone_clone( $post->ID, false, $slaves );
			$this->echo( "Pushed:", $post->post_name );
		}
	}

	/**
	 * Performs the import of the slave post to the master
	 *
	 * Creates a dummy post of the right type then performs the pull action.
	 * Hopefully the pull action updates all the fields that it should do.
	 * Interesting to see what happens for an attachment!
	 *
	 * @param $mapping
	 */

	function perform_import( $mapping ) {
		$post=null;
		if ( $this->dry_run ) {
			$this->set_action( "Dry import" );
		} else {
			$post=$this->insert_post( $mapping );
			$this->pull( $post, $mapping );
			$this->set_action( "Import" );
		}

		return $post;
	}

	/**
	 * Creates a post into which content can be imported.
	 *
	 * @param $mapping
	 *
	 * @return array|WP_Post|null
	 */
	function insert_post( $mapping ) {
		$post=array(
			'post_type'        =>$this->post_type
		,
			'post_title'       =>$mapping->name
		,
			'post_name'        =>$mapping->name
		,
			'post_content'     =>$mapping->name
		,
			'post_date'        =>$mapping->modified
		,
			'post_date_gmt'    =>$mapping->modified
		,
			'post_modified'    =>$mapping->modified
		,
			'post_modified_gmt'=>$mapping->modified
		,
			'post_status'      =>'published'
		);

		$post_id=wp_insert_post( $post, true );
		if ( is_wp_error( $post_id ) ) {
			p( "oops" );
			bw_trace2( $post_id, "wperror", false );
		} else {
			p( "Post created: " . $post_id );
			$post=get_post( $post_id );
		}

		return $post;
	}

	/**
	 * Echoes output if verbose is true.
	 *
	 * @param null $prefix
	 * @param null $value
	 */

	function echo( $prefix=null, $value=null ) {
		if ( $this->verbose ) {
			e( "$prefix $value" );
			br();
		}
	}


	/**
	 * Summarise the actions taken or to be taken.
	 *
	 * Primarily for a dry-run
	 *
	 * @param $post
	 * @param $mapping
	 * @param $match
	 */

	function summarise( $post, $mapping, $match ) {

		$summary  =[];
		$summary[] = $this->action_link( $this->action, $mapping->slave );
		$summary[] = $this->slave_link( $mapping->slave );
		if ( $mapping->cloned ) {
			$summary[]=date( "Y-m-d H:i:s", $mapping->cloned );
		} else {
			$summary[]=null;
		}
		$summary[]=$mapping->modified;


		if ( $match ) {
			$summary[]=$post->post_modified_gmt;
			$summary[] = $post->post_name;
			$summary[]=$post->post_type;
			$summary[] = $post->ID;
			$summary[]= $this->master_link( $post->ID );
			//$summary[]=$post->post_name;

		} else {
			$summary[]=null;
			$summary[]=$mapping->name;

			$summary[]=$this->post_type;
			$summary[] = null;
			$summary[]=null;

		}

		$summarised=implode( ',', $summary );
		bw_tablerow( $summary );
;		//echo $summarised;
		//echo PHP_EOL;
	}

	function table_start() {
		stag( 'table', 'form-table' );
		$headers = bw_as_array( 'Action,SlaveID,Cloned,Modified,Local-modified,Title,Type,ID,Name' );
		bw_tablerow( $headers, 'tr', 'th');

	}
	function table_end() {
		etag( 'table');

	}

	function admin_clone_url( $action, $key ) {
		$admin_clone_url = admin_url( 'admin.php?page=oik_clone&amp;tab=slave' );
		$args = [ 'slave' => $this->slave_url
				, 'clone_post_type' => $this->post_type
				, 'action' => $action
				, 'slave_id' => $key
				];
		$admin_clone_url = add_query_arg( $args, $admin_clone_url );
		return $admin_clone_url;
	}

	function action_link( $action, $slave_id ) {

		$retlink = $action;
		$link = null;
		switch ( $action ) {
			case 'Dry import':
				$link = $this->admin_clone_url( 'import', $slave_id );
				$action = 'Import';
				break;
			case 'Pull':
				$link = $this->admin_clone_url( 'pull', $slave_id );
				break;
			case 'Push':
				$link = $this->admin_clone_url( 'push', $slave_id );
				break;


		}
		if ( $link ) {
			$retlink = retlink( null, $link, $action );
		}
		return $retlink;
	}

	function slave_link( $slave_id ) {
		$slave_link = $this->slave;
		$slave_link .= '/?p=';
		$slave_link .= $slave_id;
		return retlink( null, $slave_link, $slave_id );
	}

	function master_link( $post_id ) {
		return retlink( null, get_permalink( $post_id ), get_the_title( $post_id ) );
	}

	/**
	 * Imports the specified post from the slave.
	 *
	 * We need to find the mapping in order to set the name and modified date.
	 * Can we do this by requesting the full mapping?
	 * Or just request one?
	 *
	 * @param ID $slave_id post ID of the slave post to import.
	 */
	function import( $slave_id ) {
		$mapping  = $this->retrieve_mapping( $slave_id );
		if ( $mapping ) {
			e( "Found: " . $mapping->slave );
			$this->perform_import( $mapping );
		} else {
			e( "Could not find slave post in mapping" );
		}
	}

	/**
	 * Retrieves the latest mapping for the slave ID.
	 *
	 * @param ID $slave_id Post ID on the slave.
	 * @return mixed|null mapping object if found.
	 */
	function retrieve_mapping( $slave_id ) {
		$result   = $this->request_mapping( $this->post_type );
		$mappings = $this->extract_mappings( $result );
		$mapping  = $this->get_mapping( $mappings, $slave_id );
		return $mapping;

	}

	function get_mapping( $mappings, $slave_id ) {
		$slave_mapping = null;
		foreach ( $mappings as $mapping ) {
			if ( $mapping->slave == $slave_id ) {
				$slave_mapping = $mapping;
				//e( "Found $slave_id");
				break;
			}
		}
		if ( null === $slave_mapping ) {
			bw_trace2( $mapping, "Slave ID not found in mapping", true, BW_TRACE_ERROR );
		}
		return $slave_mapping;
	}



}

