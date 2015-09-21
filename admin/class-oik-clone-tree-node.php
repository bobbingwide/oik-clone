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
	
	/**
	 * @TODO We either use constants to reflect the relationship
	 * or perhaps we just create each node as a different class extending OIK_clone_tree_node
	 */
  const CLONE_ANCESTOR = "parent";
  const CLONE_CHILD = "child"; 
	const CLONE_FR = "formal";
	const CLONE_IR = "informal"; 
	
	
	/**
	 * Construct a node
	 *
	 * We shouldn't add the post until we've actually added the node to the list
	 * Otherwise we end up getting posts too many times.
	 * But other times we may already have the post to pass.
	 */
	
	function __construct( $id, $originator=null, $relative_position=0, $relationship=self::CLONE_FR, $post=null ) {
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
			e( "Fetching post? " . $this->id );
			$this->post = get_post( $this->id );
			bw_trace2( $this->post, "this post", false, BW_TRACE_DEBUG );
		}
	}
	
	/**
	 * Display information about a node
	 *
	 * This is a very simple version to start with
	 */
	function display() {
		//print_r( $this );
		$line = retlink( null, get_permalink( $this->id ), $this->id );
		$line .= " ";
		$line .= $this->originator;
		$line .= " ";
		$line .= $this->relative_position;
		$line .= " ";
		$line .= $this->relationship;
		$line .= " ";
		$this->get_post();
		if ( $this->post ) {
			$line .= $this->post->post_type;
		}
		p( $line );
	}


}
 
