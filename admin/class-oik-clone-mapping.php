<?php // (C) Copyright Bobbing Wide 2015


/**
 * 
 * OIK_clone_mapping class
 * 
 * Implements the server end of the interface between the client ( source,  master ) and server ( target, slave )
 * of post relationships in a cloned / syndicated environment.
 *
 * The client passes its understanding of the mapping between posts in a simple array
 * which it stored as $target_relationships.
 * 
 * On the AJAX call this is passed as a JSON encoded field called "mapping".
 * 
 * @TODO - Decide how to handle the mapping in the pull model for MultiSite and WP-API
 *  
 */
class OIK_clone_mapping {
  public $mapping;
  
  function __construct() {
    $this->mapping = array();
  }
  
  /**
   * Load the mapping in a JSON server 
   *
   * Note: We need to decode to an associative array; post IDs are not valid/good variable names
   *
   */
  function load_mapping() {
    $jmapping = bw_array_get( $_REQUEST, "mapping", null );
    $jmapping = stripslashes( $jmapping );
    
    bw_trace2( $jmapping, "jmapping" );
    
    $mapping = json_decode( $jmapping, true );
    $this->mapping = $mapping;
    bw_trace2( $mapping, "mapping" );
  }
  
  /**
   * Receive the mapping directly 
   */
  function receive_mapping( $mapping ) {
    $this->mapping = $mapping;
  }
  
  /** 
   * Apply post_parent mapping
   */
  function apply_post_parent_mapping( $post ) {
    $mapped = 0;
    $parent = $post->post_parent;
    if ( $parent ) {
      $mapped = $this->get_mapping( $parent );
    }
    $post->post_parent = $mapped;
    bw_trace2( $post, "post mapped", false );
  }
  
  
  /**
   * Find and check the mapping 
   *
   * @TODO - inefficient use of get_post() when the same ID is used multiple times
   * We need a better way of knowing that this post ID has been checked
   * Perhaps we just perform a check mapping first of all.
   * 
   */  
  function get_mapping( $id ) {
    $mapped = bw_array_get( $this->mapping, $id, 0 );
    if ( $mapped ) {
      $post = get_post( $mapped );
      if ( !$post ) {
        $mapped = 0;
        $this->mapping[ $id ] = 0;
        
      }
    }
    return( $mapped ); 
  }
  
  /**
   * Apply the mapping
   *
   * Apply the mapping to post meta data
   *
   * 
   */
   function apply_mapping( $post ) {
     bw_trace2( $post );
     gobang();
   }
   
  
  /**
   * Check if this is a relationship field?
   * 
   * Determine if this field stores relationships to other fields by 
   * checking its meta_key or field type
   *
   * @TODO - this is the same code as in the client
   * Should it be a function in oik-clone-relationships
   * OR in a parent class? 
   * 
   * 
   * @param string $key - the meta_key
   * @return bool - true if this is a relationship field
   *
   */
  function is_relationship_field( $key ) {
    $is_relationship_field = false;
    switch ( $key ) {
      case "_thumbnail_id":
        $is_relationship_field = true;
        //gobang();
        break;
        
      default:
        $field_type = bw_query_field_type( $key );
        $is_relationship_field = ( $field_type == "noderef" );
    }
    return( $is_relationship_field );
  }
   

}


