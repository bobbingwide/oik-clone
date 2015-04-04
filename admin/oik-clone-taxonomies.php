<?php // (C) Copyright Bobbing Wide 2015

/**
 * Load the taxonomies for this post
 *
 * We want an array of all the taxonomies for this post ( categories and tags )
 * in a sensible format
 * get_the_taxonomies() produces a mostly textual version
 * wp_get_object_terms() produces the data we need
 * 
 * @param ID $post_id - the ID of the post for
 * @param post $post - the post object
 * @return array - arary of post_taxonomies
 * 
 */
function oik_clone_load_taxonomies( $post_id, $post ) {
  $taxonomies = get_object_taxonomies( $post, "objects" );
  bw_trace2( $taxonomies, "taxonomies", false );
  $post_taxonomies = array();
  foreach ( $taxonomies as $taxonomy => $data ) {
    //bw_trace2( $terms );
    if ( $data->hierarchical ) {
      $post_taxonomies[ $taxonomy ] = oik_clone_load_hierarchical_terms( $taxonomy, $post_id );
       
      //$post_taxonomies[ $taxonomy ] = oik_clone_get_keyed_array( $terms, "name" );
    } else {  
     /// $terms = wp_get_object_terms( $post_id, $taxonomy );
     // $post_taxonomies[ $taxonomy ] = oik_clone_get_keyed_array( $terms, "name" );
     
      $post_taxonomies[ $taxonomy ] = oik_clone_load_flat_terms( $taxonomy, $post_id );
    }
  }
  bw_trace2( $post_taxonomies, "post_taxonomies", false );
  //gobang();
  return( $post_taxonomies );    
}

/**
 * Return a keyed array of objects using a unique ( we hope ) key name
 *
 * @param array $object_arr - array of stdClass Objects each of which has a field with the $key name
 * @return array - sorted assoc array of objects 
 */
function oik_clone_get_keyed_array( $object_arr, $key ) {
  $keyed = array();
  foreach ( $object_arr as $object ) {
    $value = $object->$key;
    $keyed[$value] = (array) $object;
  }
  ksort( $keyed );
  return( $keyed ); 

}

/**
 * Load the terms for a hierarchical taxonomy
 * 
 * @param ID $post_id - the ID of the post
 * @param string $taxonomy - the taxonomy name
 * @return array - array of terms and term hierarchy to pass to the server
 *                           
 */ 
function oik_clone_load_hierarchical_terms( $taxonomy, $post_id ) {
  oik_require( "admin/class-oik-clone-taxonomies.php", "oik-clone" );
  $taxonomy_class = new OIK_clone_taxonomies();
  $tax = $taxonomy_class->set_taxonomy( $taxonomy );
  $terms = $taxonomy_class->get_terms( $post_id );
  $source_tree = $taxonomy_class->get_term_hierarchy();
  $source_terms = $taxonomy_class->get_source_terms();
  bw_trace2( $source_terms, "source_terms hierarchical" );
  return( $source_terms );
}

/**
 * Load the terms for a flat taxonomy 
 *
 * @param ID $post_id - the ID of the post
 * @param string $taxonomy - the taxonomy name
 * @return array - array of terms and term hierarchy to pass to the server
 */
function oik_clone_load_flat_terms( $taxonomy, $post_id ) {
  oik_require( "admin/class-oik-clone-taxonomies.php", "oik-clone" );
  $taxonomy_class = new OIK_clone_taxonomies();
  $tax = $taxonomy_class->set_taxonomy( $taxonomy );
  $terms = $taxonomy_class->get_terms( $post_id );
  //$source_tree = $taxonomy_class->get_term_hierarchy();
  $source_terms = $taxonomy_class->get_source_terms();
  bw_trace2( $source_terms, "source_terms flat" );
  return( $source_terms );
}

/**
 * Update the taxonomies for the post
 * 
 * 
 * @param array $post - the source post object 
 * @param ID $target - the ID of the target post 
  
 * The source post may send an array like this
   `
   [post_taxonomies] => stdClass Object
        (
            [required_version] => Array
                (
                    [0] => stdClass Object
                        (
                            [term_id] => 347
                            [name] => 4.0
                            [slug] => 4-0
                            [term_group] => 0
                            [term_taxonomy_id] => 381
                            [taxonomy] => required_version
                            [description] => 
                            [parent] => 0
                            [count] => 2
                            [filter] => raw
                        )

                )

            [compatible_up_to] => Array
                (
                    [0] => stdClass Object
                        (
                            [term_id] => 317
                            [name] => 4.0
                            [slug] => 4-0
                            [term_group] => 0
                            [term_taxonomy_id] => 349
                            [taxonomy] => compatible_up_to
                            [description] => 
                            [parent] => 0
                            [count] => 38
                            [filter] => raw
                        )

                    [1] => stdClass Object
                        (
                            [term_id] => 341
                            [name] => 4.0.1
                            [slug] => 4-0-1
                            [term_group] => 0
                            [term_taxonomy_id] => 375
                            [taxonomy] => compatible_up_to
                            [description] => 
                            [parent] => 317
                            [count] => 16
                            [filter] => raw
                        )

                    [2] => stdClass Object
                        (
                            [term_id] => 345
                            [name] => 4.1
                            [slug] => 4-1
                            [term_group] => 0
                            [term_taxonomy_id] => 379
                            [taxonomy] => compatible_up_to
                            [description] => 
                            [parent] => 0
                            [count] => 15
                            [filter] => raw
                        )

                    [3] => stdClass Object
                        (
                            [term_id] => 348
                            [name] => 4.1.1
                            [slug] => 4-1-1
                            [term_group] => 0
                            [term_taxonomy_id] => 382
                            [taxonomy] => compatible_up_to
                            [description] => 
                            [parent] => 345
                            [count] => 3
                            [filter] => raw
                        )

                )

       )
       `
       
       The target post may have an array like this
       
       `
           [required_version] => Array
        (
            [0] => stdClass Object
                (
                    [term_id] => 159
                    [name] => 3.9
                    [slug] => 3-9
                    [term_group] => 0
                    [term_taxonomy_id] => 230
                    [taxonomy] => required_version
                    [description] => 
                    [parent] => 0
                    [count] => 17
                    [filter] => raw
                )

        )

    [compatible_up_to] => Array
        (
            [0] => stdClass Object
                (
                    [term_id] => 222
                    [name] => 4.0
                    [slug] => 4-0
                    [term_group] => 0
                    [term_taxonomy_id] => 283
                    [taxonomy] => compatible_up_to
                    [description] => 
                    [parent] => 0
                    [count] => 4
                    [filter] => raw
                )

        )
        `
        
        We need to reconcile the target's taxonomies with the source's
        
        Source taxonomy | Target taxonomy | Processing
        --------------- | --------------- | ----------
        x               | -               | Ignore this source taxonomy 
        x               | x               | Process the terms in this taxonomy
        -               | x               | Ignore this target taxonomy
        
        There will be a top level in the $taxonomies arrays for each registered taxonomy.
        In the event of a mismatch at this level we do nothing.
        The Compare function may help us find mismatches like this.
        
        
        
        
         
        
 */        
function oik_clone_lazy_update_taxonomies( $post, $target ) {  
  $source_taxonomies = $post->post_taxonomies;
  $target_taxonomies = oik_clone_load_taxonomies( $target, $post->post_type );

  bw_trace2( $target_taxonomies, "target_taxonomies" );
  
  foreach ( $source_taxonomies as $source_taxonomy => $source_terms ) {
    $target_terms = bw_array_get( $target_taxonomies, $source_taxonomy, null ) ;
    bw_trace2( $target_terms, "target_terms", false );
    bw_trace2( $source_terms, "source_terms", false );
    if ( $target_terms !== null ) {
      oik_clone_lazy_update_taxonomy_terms( $target, (array) $source_terms, $target_terms );
    } else {
      p( "Source taxonomy missing from target: $source_taxonomy" );
  gobang();
    }
  } 
}

/**
 * OO approach to updating taxonomy terms in the target
 * 
 * @param array $post - the source post object - which contains post->taxonomies 
 * @param ID $target - the ID of the target post 
 */
function oik_clone_lazy_update_taxonomies2( $post, $target ) {
  oik_require( "admin/class-oik-clone-taxonomies.php", "oik-clone" );
  $source_taxonomies = $post->post_taxonomies;
  $taxonomy_class = new OIK_clone_taxonomies();
  foreach ( $source_taxonomies as $taxonomy => $value ) {
    $taxonomy_class->init();
    $taxonomy_class->set_taxonomy( $taxonomy );
    $taxonomy_class->receive_source_tree( $post );
    $taxonomy_class->get_target_terms( $target );
    $taxonomy_class->get_target_tree();
    $taxonomy_class->update_target_tree();
  }
}

/**
 * Update the taxonomy terms for the post
 * 
 * @TODO - determine if this is a redundant piece of code

        The terms array may be an empty array if there are no categories or tags defined for the taxonomy.
        
        Source term | Target term | Processing
        ----------- | ----------- | --------------
        x           | -           | Add the source term to the target
        x           | x           | Do nothing. This is a match
        -           | x           | Remove the target term from the target
        
        Logic
        
        For each term in the source
         - Check for a match. If not present, create the term
         - Remove the entry from the target array
         For each remaining term in the target
         - Delete the term from the target
 
 
 */

function oik_clone_lazy_update_taxonomy_terms( $target, $source_terms, $target_terms ) {

  
  bw_trace2();
  
  foreach ( $source_terms as $source_term => $term_data ) {
    
    bw_trace2( $term_data, "term_data", false );
    // Trying "name" instead of slug
    // 2015/04/01
    $terms = array( $term_data->name );
    wp_set_object_terms( $target, $terms, $term_data->taxonomy, true );
  
  }
  
  //gobang();
}


/**
 * We can use a JSON request to get the current terms for a taxonomy from the server
   then we can map our data to theirs
   Alternatively, we can pass the information to the server and they can work it out
   But how do we do this with hierarchical taxonomies? 
   
   

http://qw/wordpress/wp-json/taxonomies/compatible_up_to/terms
 
 * 
 terms":{"compatible_up_to":
 [{"ID":289,
 "name":"4-1-1",
 "slug":"4-1-1",
 "description":"",
 "taxonomy":"compatible_up_to",
 "parent":null,
 "count":1,
 "link":"http:\/\/qw\/wordpress\/compatible_up_to\/4-1-1\/",
 "meta":{"links":{"collection":"http:\/\/qw\/wordpress\/wp-json\/taxonomies\/compatible_up_to\/terms",
 "self":"http:\/\/qw\/wordpress\/wp-json\/taxonomies\/compatible_up_to\/terms\/228"}}}
 ]}
 
 }
 
 */

