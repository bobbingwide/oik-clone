<?php // (C) Copyright Bobbing Wide 2015

/**
 * Class OIK_clone_tree_node 
 *
 * Implements a node in the OIK_clone_tree
 * 
 * We need some information about each post in the tree
 * 
 */
class OIK_clone_tree_node {
	public $id; // The ID of the post
	public $originator; // The ID of the post that caused this post to appear in the tree
	public $relative_position; // Relative position in the tree. Starting from 0
	public $relationship; // How the $post_id is related to the $originator
	public $post; // Pointer to the post itself
	//public $cloned; 
	
	/**
	 * @TODO We either use constants to reflect the relationship
	 * or perhaps we just create each node as a different class extending OIK_clone_tree_node
	 */
	const CLONE_SELF = "self";
  const CLONE_ANCESTOR = "parent";
  const CLONE_CHILD = "child"; 
	const CLONE_FORMAL = "related";
	//const CLONE_INFORMAL = "informal"; 
	
	
	/**
	 * Construct a node
	 *
	 * We shouldn't add the post until we've actually added the node to the list
	 * Otherwise we end up getting posts too many times.
	 * But other times we may already have the post to pass.
	 *
	 * @TODO oik-shortcode parser can't handle self::CLONE_SELF so default is hardcoded as "self"
	 * 
	 * @param ID $id 
	 * @param string $originator
	 * @param integer $relative_position
	 * @param string $relationship Note: oik-shortcode parser can't handle self::CLONE_SELF so default
	 * @param post $post
	 */
	function __construct( $id, $originator=null, $relative_position=0, $relationship="self", $post=null ) {
		$this->id = $id;
		$this->originator = $originator;
		$this->relative_position = $relative_position;
		$this->relationship = $relationship;
		$this->post = $post;
		// $this->get_post();
	}
	
	/**
	 * Get a post
	 *
	 * Access the post regardless of post type, status etc
	 */
	function get_post() {
		if ( !$this->post ) {
			//e( "Fetching post? " . $this->id );
			$this->post = get_post( $this->id );
			bw_trace2( $this->post, "this post", false, BW_TRACE_DEBUG );
		}
	}
	
	/**
	 * Get the post's meta data
	 */
	function get_post_meta() {
		if ( !$this->post->post_meta ) {
			$this->post->post_meta = get_post_meta( $this->id );
		} 
		return( $this->post->post_meta );
	}
	
	/**
	 * Display information about a node
	 *
	 * This is a very simple version to start with
	 */
	function display() {
		//print_r( $this );
		$this->get_post();
		if ( $this->post ) {
			if ( $this->post->post_type != 'revision' ) {
				$line = retlink( null, get_permalink( $this->id ), $this->id );
			} else {
				$line = $this->id;
			}
			$line .= " ";
			$line .= $this->originator;
			$line .= " ";
			$line .= $this->relative_position;
			$line .= " ";
			$line .= $this->relationship;
			$line .= " ";
			$line .= $this->post->post_type;
			$line .= $this->clone_status();
			br();
			e( $line );
		}
	}
	
	/**
	 * Return the clone status
	 * 
	 * Build the clone status from the information in $this->post->post_meta
	 * for "_oik_clone_ids" 
	 *
	 * @param bool $just_count - true if we just need the count of slaves to clone to
	 */ 
	function clone_status( $just_count=false) {
		$clone_status = null;
		$count_to_clone = 0;
		if ( $this->valid_type() && $this->valid_status() && $this->get_post_meta() ) {
			if ( $this->post->post_meta ) {
				$modified_gmt = $this->get_post_modified_gmt();
				$servers = $this->get_targets();
				$to_clone = $this->to_clone( $servers, $modified_gmt );
				$count_to_clone = count( $to_clone );
				$count_servers = count( $servers );
				$clone_status = " $count_to_clone / $count_servers";
				if ( $count_to_clone ) {
					$form = OIK_clone_tree::get_atts( "form" );
					if ( $form ) {
						$clone_status .= $this->clone_link( $to_clone );
					}
				}
			} else {
				$clone_status = " ?";
			}
		} else {
			$clone_status = " n/a";
		}
		if ( $just_count ) {
			$clone_status = $count_to_clone;
		}
		return( $clone_status );

	
	}
	
	/**
	 * Return the servers to clone to
	 *
	 *  
	 */
	function get_targets() {
		$servers = OIK_clone_tree::get_targets( $this );
		return( $servers );
		
	}
	
	/**
	 * Return the deserialized cloned information
	 *
	 * We know $post_meta is set, but that doesn't mean `_oik_clone_ids` is
	 *
	 */
	function get_cloned() {
		$post_meta = $this->post->post_meta;
		$cloned = bw_array_get( $post_meta, "_oik_clone_ids", null );
		if ( $cloned ) {
			//bw_trace2( $slave_ids, "slave_ids", false );
			$cloned = unserialize( $cloned[0] );
		} else {
			$cloned = array();
		}
		bw_trace2( $cloned, "cloned", false, BW_TRACE_DEBUG );
		return( $cloned );
	}
	
	/** 
	 * Return the list of cloned targets
	 *
	 * @TODO Check array_keys() works for null or empty arrays.
	 *
	 * @return array targets already cloned
	 */											
	function targets() {
		$cloned = $this->get_cloned();
		$slaves = array_keys( $cloned );
		bw_trace2( $cloned, "cloned", false, BW_TRACE_DEBUG );
		return( $slaves );
	}
	
	/**
	 * Return the post modified GMT as UNIX timestamp
	 */
	function get_post_modified_gmt() {
		$modified_gmt = $this->post->post_modified_gmt;
		$unix_time = strtotime( $modified_gmt );
		return( $unix_time );
	}
	
	/**
	 * Return the subset of servers to clone
	 *
	 * $to_clone shows the current status of cloning
	 * which may include servers to which we no longer want to clone
	 * 
	 * We match this against the $servers array
	 * 
	 * We decide if we need to clone based on $modified_gmt
	 * which we assume to be 0 if it's not set.
	 * 
	 * 
	 * @TODO How do we remove these?
	 * @TODO Eventually change the code back to cater for not having the last modified data set
	 * 
	 * @param array $servers
	 * @param integer $modified_gmt
	 * @return array targets to which the post should be cloned
	 *
	 */
	function to_clone( $servers, $modified_gmt ) {
		$cloned = $this->get_cloned();
		$to_clone = array();
		foreach ( $servers as $server ) {
			$clone_info = bw_array_get( $cloned, $server, null ); 
			if ( $clone_info ) {
				if ( is_array( $clone_info ) ) {
					$cloned_date = $clone_info['cloned'];
        } else {
					$cloned_date = $modified_gmt; // Change back to 0 when enough updates have been done.
				}
				if ( $cloned_date < $modified_gmt ) {
					$to_clone[ $server ] = $clone_info;
        }
			} else {
				$to_clone[$server] = null;
			}
			  
		}
		bw_trace2( $to_clone, "to_clone", true, BW_TRACE_DEBUG );
		return( $to_clone );
	}
	
	/**
	 * Create a checkbox to clone this post
	 *
	 * @param array $to_clone - servers to which to clone - unused?
	 * @return string	a checkbox for the given post
	 */ 
	function clone_link( $to_clone ) {
		bw_trace2( );
		
		$clone_link = " ";
		$clone_link .= icheckbox( "clone[{$this->id}]");
		return( $clone_link );
	}
	
	/**
	 * Clone the node to the selected targets
	 *
	 * @TODO After we've cloned the post we need to do something about cloning of informal relationships otherwise
	 * these show up on the next display of the clone tree.  
	 */ 
	function cloneme() {
		$slaves = $this->get_targets();
		if ( count( $slaves ) ) {
			p( "Cloning: {$this->id} " );
			oik_require( "admin/oik-save-post.php", "oik-clone" );
			oik_clone_clone( $this->id, false, $slaves );
		}
	}
	
	/**
	 * Check if the post type is valid for cloning
	 *
	 * @return bool - true when valid for cloning, false when not
	 */
	function valid_type() {
		$valid = true;
		if ( $this->post ) {
			switch ( $this->post->post_type ) {
				case 'revision': 
					$valid = false; 
					break;
					
				default: 
					$valid = post_type_supports( $this->post->post_type, "clone" );
			}
		} else {
			$valid = false;
		}
		return( $valid );
			
	}
	
	/**
	 * Check for valid node status
	 *
	 * We don't want to clone certain post statuses
	 * But more often than not we handle this by not cloning certain post types
	 * 
	 * Status |  Valid?	| Explanation
	 * ------ |  ------ | -----------
	 * abandoned |  No - edd_payment
	 * active | No | edd_discount
	 * auto-draft | No | Should be published first
	 * draft  | No | Should be published first
	 * failed | No | edd_payment
	 * inactive | No | edd_discount
	 * inherit |  Yes | Attachments have this status	 
	 * pending | No | Should be published first
	 * private | Yes | We're happy to clone these
	 * publish |  Yes | OK
	 * trash | No | Trashed posts not supported by "Clone on update"
	 * other? | Yes | We don't know so err towards Yes
	 */
	function valid_status() {
		//bw_trace2( $this, "valid status?", false );
		$valid = true;
		if ( $this->post ) {
			switch ( $this->post->post_status ) {
				case 'auto-draft':
				case 'draft':
				case 'pending': 
				case 'trash':
					$valid = false;
					break;
					
				default:
					//
			}
		} else {
			bw_trace2( $this, "Invalid post", false, BW_TRACE_WARNING );
			$valid = false;
		}
		return( $valid );
	}
	
} /* end class */
	
 
