<?php // (C) Copyright Bobbing Wide 2015

oik_require( "admin/class-oik-clone-tree-node.php", "oik-clone" );

/**
 * OIK_clone_tree class
 *
 * Builds the complete family tree of posts starting from a given post.
 * 
 * It builds the 
 * - family tree
 * - extended family relationships tree
 * 
 * This can then be used to drive cloning to multiple targets
 * 
 * Uses OIK_clone_tree_node objects in the "lists" 
 */
 
class OIK_clone_tree {
  public $nodes; // array of OIK_clone_tree_node objects;
	public $unkeyed_nodes; // unkeyed array of nodes - accessed by node_index
	public $handled_nodes; // array of nodes that we've processed;
	public $ordered_nodes; // nodes sorted by relative relationship ascending
	public $reprocess_nodes; //
	public $id; 
	public $atts; // attributes
	public $current_relative_position;
	public $node_index;
   
  /**
   * Initialise the class
   *
   * Ensure fields are initialised. 
   */
  function __construct( $id, $atts ) {
		$this->nodes = array(); 
		$this->handled_nodes = array();
		$this->ordered_nodes = array();
		$this->reprocess_nodes = array(); 
		$this->id = $id;
		$this->atts = $atts;
		$this->current_relative_position = 0;
		$this->node_index = 0;
		$node = new OIK_clone_tree_node( $id, $id );
		$this->build_tree( $node );
		$this->next_node( $node );
  }
	
	/**
	 * Build the tree
	 *
	 *
	 * @param ID $id the post ID to start from OR
	 * @param object $node the node to start from?
	 
	 * 
	 */
	function build_tree( $node ) {
		$this->add_node( $node );
		if ( !$this->is_handled_node( $node ) ) {
			$this->build_parent_tree( $node );
			$this->build_child_tree( $node );
			//$this->build_formal_relationships( $node );
			//$this->build_informal_relationships( $node );
		}
		
		
	}
	
	/**
	 * Add a node to the 'tree'
	 *
	 */
	function add_node( $node ) {
		if ( !isset( $this->nodes[ $node->id ] ) ) {
			$node->get_post();
			$this->nodes[ $node->id] = $node;
			$this->unkeyed_nodes[] = $node;
			return( true );
		} else {
			return( false );
		}
	}
	
	/**
	 * Process the next node in the tree
	 *
	 * Add the current node to the list of handled nodes
	 * 
	 * 
	 * @param object $node the node we've just processed
	 */
	function next_node( $node ) {
		$this->add_handled_node( $node );
		$next_node = $this->next_unhandled_node(); 
		while ( $next_node ) {
			//e( "Processing next node" . $next_node->id );
			$this->build_tree( $next_node );
			$next_node = $this->next_unhandled_node();
		}
	}
	
	/**
	 * Return the next unhandled / unkeyed node
	 */	
	function next_unhandled_node() {
	  $this->node_index++;
		$next_node = null;
		if ( isset( $this->unkeyed_nodes[ $this->node_index ] ) ) {
			$next_node = $this->unkeyed_nodes[ $this->node_index ];
			//e( "Next: " . $next_node->id );
		}	else {
			//e( "No more nodes" . $this->node_index );
		}
		return( $next_node );
	}
	
	function add_handled_node( $node ) {
		$this->handled_nodes[ $node->id ] = $node;
		
	}
	
	/**
	 * Test if node has been handled
	 *
	 * @param object $node the node we're looking to handle
	 * @return bool true if the post id has already been handled
	 */
	function is_handled_node( $node ) {
		$handled = isset( $this->handled_nodes[ $node->id ] );
		bw_trace2( $handled, "handled?", true, BW_TRACE_DEBUG );
		return( $handled );
	}
	
	function add_reprocess_node( $node ) {
	}
	function is_reprocess_node() {
	}
	
	/**
	 * Build the parent tree
	 *
	 * We need to build the whole parent tree since we need to start cloning from the lowest
	 * relative_position
	 *
	 * @param object $node
	 */
	function build_parent_tree( $node ) {
		$originator_id = $node->id;
		$relative_position = $node->relative_position;
		while ( $node->post && $node->post->post_parent )  { 
			$relative_position--;
			$node = new OIK_clone_tree_node( $node->post->post_parent, $originator_id, $relative_position, OIK_Clone_Tree_Node::CLONE_ANCESTOR);
			$this->add_node( $node );
		}
	}
	
	/**
	 * Build the child tree
	 *
	 * When building the child tree we only need to find the first children since the others will be found later on. 
	 * But we're sorting anyway so probably the same sorting theory applies.
	 *
	 * The post can be a parent in the normal hierarchy and to attachments.
   * 
	 */
	function build_child_tree( $node ) {
		$originator_id = $node->id;
		$relative_position = $node->relative_position;
		$relative_position++;
		$atts = array( 'post_parent' => $node->id
								 , 'number_posts' => -1
								 , 'post_type' => 'any'
								 , 'post_status' => 'any'
								 );
		oik_require( "includes/bw_posts.inc" );								 
		$posts = bw_get_posts( $atts );
		//e( "Building children of {$node->id}, {$node->originator}" );
		foreach ( $posts as $post ) {
			$node = new OIK_clone_tree_node( $post->ID, $originator_id, $relative_position, OIK_Clone_Tree_Node::CLONE_CHILD, $post );
			$this->add_node( $node );
		}	
	}
		
	/* Display the clone tree
	 */
	function display() {
		e( "Clone:" );
		e( $this->id );
		p( count( $this->nodes ) );
		foreach ( $this->nodes as $key => $node ) {
			
			$node->display( $node );
		}
	}
	
	/**
	 * 	Display the clone tree ordered by sequence they should be cloned
	 *
	 */
	function display_ordered() {
	
		e( "Clone order:" );
		e( $this->id );
		p( count( $this->ordered_nodes ) );
		$this->order_nodes();
		foreach ( $this->ordered_nodes as $key => $node ) {
			$node->display( $node );
		}
	}
	
	/**
	 * Sort nodes by their relative positions
	 */
	function order_nodes() {
		$relative_positions = array();
		$this->ordered_nodes = array();
		foreach ( $this->nodes as $key => $node ) {
		  $relative_positions[ $key ] = $node->relative_position;
			$this->ordered_nodes[ $key ] = $node;
		}
		//bw_trace2( $this->ordered_nodes, "ordered nodes before", false, BW_TRACE_DEBUG );
		array_multisort( $relative_positions, SORT_ASC, SORT_NUMERIC, $this->ordered_nodes );
		
		//bw_trace2( $this->ordered_nodes, "ordered nodes after", false, BW_TRACE_DEBUG );
	}
	
}	
	
