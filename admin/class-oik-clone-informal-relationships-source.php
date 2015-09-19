<?php // (C) Copyright Bobbing Wide 2015

oik_require( "admin/class-oik-clone-informal-relationships.php", "oik-clone" );

/**
 * OIK_clone_informal_relationships_source class
 *
 * Implements the client end of informal relationship mapping
 * 
 *
 */
class OIK_clone_informal_relationships_source extends OIK_clone_informal_relationships {

  public $source_ids;
	
	/**
	 * Set the source IDs on __construct
	 *
	 * @param array source IDs
	 */
  function __construct( $source_ids ) {
		bw_trace2( null, null, true, BW_TRACE_VERBOSE );
    parent::__construct();
    $this->source_ids = $source_ids;
  }
  
  /* 
   * Implement OIK_clone_informal_relationships::handle_id
   *
   * In the source we handle the ID by adding it to the source_ids array
   *
   */
  function handle_id( $id, $t ) {
    $this->add_id( $id );
  }

  /**
   * Add the ID to the source_ids array
   *
   * It doesn't matter if it's a duplicate
   * this gets dealt with in later processing
   *
   * @param integer $id - the post ID to add to the array
   */
  function add_id( $id ) {
    $this->source_ids[] = $id;
  }
  
}


