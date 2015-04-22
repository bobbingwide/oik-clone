<?php // (C) Copyright Bobbing Wide 2015

/**
 * OIK_clone_relationships class
 *
 * Implements the client end of the interface between client ( source,  master ) and server ( target, slave )
 * of post relationships in a cloned / syndicated environment.
 *  
 */
 
class OIK_clone_relationships {
  public $source_IDs; // array of source IDs
  public $source_post_meta; // array of source post_meta data
  public $target_relationships; // mapping array of source IDs to target IDs 
  public $target ; // target host
   
  /**
   * Initialise the class
   *
   * Ensure fields are initialised. 
   */
  function __construct() {
    $this->source_IDs = array();
    $this->source_post_meta = array();
    $this->target_relationships = array();
    $this->target = null;
  }
 
  /**
   * Build a list of relationships to other posts
   *
   * Create the list of all the post IDs for which we need to discover relationships
   * 
   * @param object $post - the 'complete' post object 
   */
  function build_list( $post ) { 
    $source_IDs = array();
    $source_IDs[] = $post->ID;
    if ( $post->post_parent ) {
      $source_IDs[] = $post->post_parent;
    }
    add_filter( "oik_clone_build_list", array( $this, "filter_metadata"), 10, 2 );
    $this->source_IDs = apply_filters( "oik_clone_build_list", $source_IDs, $post );
  }
  
  /**
   * Implement 'oik_clone_build_list' for oik-clone
   *
   * List post IDs from this post's relationships and/ or content
   *
   * 
   * @param array $source_IDs - array of source IDs
   * @param object $post - the complete post object incl. post_meta
   * @return array - ordered array of source post IDs, which may include 0 for any relationships which were "None".
   */ 
  function filter_metadata( $source_IDs, $post ) {
    $IDs = $source_IDs;
    $metadata = $post->post_meta;
    foreach ( $post->post_meta as $key => $meta_array ) {
      if ( $this->is_relationship_field( $key, $meta_array ) ) {
        //bw_trace2( $key, "key", false ); 
        //bw_trace2( $meta_array, "meta", false ); 
        $IDs = array_merge( $IDs, $meta_array ); 
      }
    }
    $IDs = array_unique( $IDs );
    sort( $IDs );
    
    bw_trace2( $IDs, "IDs", false );
    return( $IDs );
  }
  
  
  /**
   * Check if this is a relationship field?
   * 
   * Determine if this field stores relationships to other fields by 
   * checking its meta_key or field type
   * 
   * @param string $key - the meta_key
   * @param string $meta_value - the meta_value
   * @return bool - true if this is a relationship field
   *
   */
  function is_relationship_field( $key, $meta_value ) {
    $is_relationship_field = false;
    switch ( $key ) {
      case "_thumbnail_id":
        $is_relationship_field = true;
        break;
        
      case "_bw_image_link":
        $is_relationship_field = is_numeric( $meta_value[0] ); 
        bw_trace2( $is_relationship_field, "is_relationship" ); 
        break;
        
      default:
        $field_type = bw_query_field_type( $key );
        $is_relationship_field = ( $field_type == "noderef" );
    }
    return( $is_relationship_field );
  }
  
  /**
   * Load the slave IDs for all the source post IDs
   *
   * - Ignore post 0. 
   * - get_post_meta() may return an empty array 
   * - otherwise it should return an array like this
            [0] => Array
                (
                    [http://qw/wordpress] => 29893
                    [http://oik-plugins.co.uk] => 8148
                    [http://oik-plugins.biz] => 16317
                    [http://oik-plugins.uk] => 16319
                    [http://oik-plugins.com] => 16332
                )

   */
  function load_slave_ids() {
    $this->source_post_meta = array();
    bw_trace2( $this->source_IDs, "source_IDs" );
    foreach ( $this->source_IDs as $id ) {
      if ( $id ) {
        $this->source_post_meta[$id] = get_post_meta( $id, "_oik_clone_ids", false );
      }
    }
    bw_trace2( $this->source_post_meta, "source_post_meta" );
  }
  
  /**
   * Return the mapping for the target server
   *
   * Create an array of source to target IDs to pass to the server
   *
   * For each post ID we determine the known mapping or return 0
   * This may produce a larger array but it could help in the future
   * if the server was to reply with the numbers it already knew
   *
   *
   * @param string $target - the target server
   * @return array - the mapping
   */
   function mapping( $target ) {
     $this->target = $target; 
     $target_relationships = array();
     foreach ( $this->source_post_meta as $key => $meta ) {
       if ( empty( $meta ) ) {
         $target_id = 0;
       } else {
         $target_id = bw_array_get( $meta[0], $target, 0 );
       }
       $target_relationships[ $key ] = $target_id;
     }
     $this->target_relationships = $target_relationships;
     bw_trace2( $this->target_relationships, "target relationships" );
     return( $this->target_relationships );
   }
}
 
