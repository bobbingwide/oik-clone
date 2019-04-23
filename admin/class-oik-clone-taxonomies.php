<?php // (C) Copyright Bobbing Wide 2015, 2019

/**
 * OIK_clone_taxonomies class 
 *
 * Provide client/server methods for managing taxonomy terms associated with posts 
 *
 * Note: This may only be needed for hierarchical taxonomies
 * The WordPress core APIs do not nicely deal with interchange of taxonomy terms between sites
 * 
 * - The unique IDs on one site are useless to another
 * - Similarly, we can't trust the values of slugs
 * - So we have to attempt to work with term names 
 * - While understanding the term tree hierarchy
 * 
 *
 * - You can have a term called "custom CSS" in multiple places in the tree.
 * - Each term has a unique slug
 * - So you can list all posts with that slug 
 * - And get just the ones you were aiming for
 * - But, if a user were to perform a query on the name, then you could get some odd results
 *
 * WordPress 4.2 is doing some work to sort out a number of challenges in this area.
 * This solution pays no attention to those problems.
 * Let's hope that the data structures are unchanged!
 *
 * 
 */
class OIK_clone_taxonomies {

  /**
   * The name of the taxonomy being processed
   */                   
  public $taxonomy;  
  
                    
  /**
   * array of source terms for the selected post                  
   */
  public $terms;
  
  /**
   * array of source terms which have parents
   */  
  public $source_tree;
  
  /**
   * array of target terms for the selected post
   *
   */
  public $target_terms;
  
  /**
   * array of all target terms, including those which have a parent of 0
   */
  public $target_tree;
  
  /**
   * Ancestry of the current source term
   * 
   * Used for locating the term in the target tree
   */
  public $ancestry; 
  
  /**
   * The ID of the post we're dealing with
   */
  public $id; 
  
  
  /**
   * Constructor for OIK_clone_taxonomies
   *
   * Initialises the public variables
   */
  function __construct() {
    $this->init();
  }
  
  /**
   * Initialise/reinitialise the public variables
   *
   * This done for each new taxonomy being processed
   */
  function init() {
    $this->taxonomy = null;
    $this->terms = array();
    $this->source_tree = array();
    $this->target_tree = array();
    $this->id = 0;
    bw_trace2( $this, "OIK_clone_taxonomies" );
  }
  
  /**
   * Set the taxonomy being processed
   *
   * We only work with one taxonomy at a time
   * 
   * @TODO implement some validation/sanitization
   *
   * @param $string $taxonomy - name of a hierarchical taxonomy
   * @return string - GIGO
   */
  function set_taxonomy( $taxonomy ) {
    //$this->init();
	$taxonomy_exists = taxonomy_exists( $taxonomy );
	if ( $taxonomy_exists ) {
		$this->taxonomy = $taxonomy;
	} else {
		// Leave it as it is.

	}
    return $taxonomy_exists;
  } 
  
  /**
   * Get the terms for the specified source post
 
                        
     [0] => stdClass Object
        (
            [term_id] => 79
            [name] => not started
            [slug] => not-started
            [term_group] => 0
            [term_taxonomy_id] => 79
            [taxonomy] => todo_status
            [description] =>
            [parent] => 0
            [count] => 41
            [filter] => raw
        )
  * @param ID $id - the post ID
  * @return array - the terms attached to the post
  */ 
  function get_terms( $id ) {
    $this->terms = wp_get_object_terms( $id, $this->taxonomy );
    bw_trace2( $this->terms, "this terms" );
    return( $this->terms );
  }
  
  /**
   * Build the source tree for the selected terms
   * 
   * When you want to tell another system about your taxonomies
   * then if it's a hierarchical taxonomy you will need to provide some information about the hierarchy,
   * so that it can be reproduced on the server.
   * 
   *  x | Slug / Name  |
   *  - | ------------ |
   *    |  4.0         |
   *  x |     4.0.1    |
   *  x |  4.1         |
   *  x |    4.1.1     |
   *    |  ggd         |
   *    |    gd        |
   *    |      father  |
   *  x |        son   |
   *
   * 
   * Each term passed to the server will need to indicate all its ancestor's names.
   * The target can then build the required tree, creating each child term, down to the selected terms as required.
   * Each selected term is attached to the post.
   
   * We'll use the source slug or source ID to create the set to pass
   * In the server the matching will have to be by the name within the tree.
   * 
   * Fields passed need to include:
   * - term_id
   * - name
   * - slug
   * - description
   * - parent
   * - checked
   */
  function build_source_tree() {
     $this->source_tree = array();
     $hierarchy_needed = false;
     foreach ( $this->terms as $term_id => $term ) {
       if ( $term->parent != 0 ) {
         $hierarchy_needed = true;
         break;
       }
     }
     if ( $hierarchy_needed ) {
       $this->get_term_hierarchy();
     }
  }
  
  /**
   * Build the term hierarchy for child terms
   *
   * You may think that we can get away with only needing the terms where the parents is not 0.
   * Actually, it's a little more complex.
   * We need all the terms which have children and all the terms which have parents.
   * So, we may as well pass the whole taxonomy and be done with it.
   * 
   * @TODO Reduce the hierarchy to only those terms which are ancestors of the post's terms
   * Only necessary
   * 
   * 
   */
  function get_term_hierarchy() {
	  $children = array();
    //$fields = "name,slug,description,parent,id";
  	$terms = get_terms( $this->taxonomy, array( 'get' => 'all', 'orderby' => 'id') );
  	foreach ( $terms as $term_id => $term ) {
      ///if ( $term->parent ) {
        $children[] = $term;
      //}
  	}
    bw_trace2( $children, "children", false );
    $this->source_tree = $children;
    return(  $children );
  }
  
  /**
   * Get the source tree from the request
   *
   * For a flat taxonomy we don't expect anything in the "tree" part
   * 
   */ 
  function receive_source_tree( $post ) {
    $taxonomies = bw_array_get( $post, "post_taxonomies", null );
    $this_tax = bw_array_get( $taxonomies, $this->taxonomy, null );
    if ( $this_tax ) {
      $this->terms = bw_array_get( $this_tax, "terms", null );
      $this->source_tree = bw_array_get( $this_tax, "tree", null );
    } else {
      bw_trace2( $this->taxonomy, "Missing information for taxonomy" );
    }
  } 
  
  /**
   * Load the complete target taxonomy structure
   *
   * 
   */
  function get_target_tree() {
  	$terms = get_terms( $this->taxonomy, array( 'get' => 'all', 'orderby' => 'id') );
    $this->target_tree = $terms;
    bw_trace2( $this->target_tree, "target_tree", false );
    return(  $terms );
  }
  
  /**
   * Get the target terms
   *                      
   * Load the terms attached to the target
   */
  function get_target_terms( $id ) {
    $this->id = $id; 
    $this->target_terms = wp_get_object_terms( $id, $this->taxonomy );
    bw_trace2( $this->target_terms, "target_terms" );
    return( $this->target_terms );
  }
  
  /**
   * Get the terms in JSON form
   *
   * There are two arrays
   * - terms: the selected terms
   * - tree: all the terms which are child terms
   * If we pass all this information to the server
   * then we can reproduce the hierarchy in the server
   * The tree: may have too many branches... we'll see
   
 {"terms":[{"term_id":79,"name":"not started","slug":"not-started","term_group":0,"term_taxonomy_id":79,"taxonomy":"todo_status","description":"","parent":0,"count":41,"filter":"raw"}],

"tree":[

{"term_id":"242","name":"forever","slug":"forever","term_group":"0","term_taxonomy_id":"303","taxonomy":"todo_status","description":"Abandoned forever","parent":"217","count":"0"},
{"term_id":"243","name":"Draft","slug":"draft","term_group":"0","term_taxonomy_id":"304","taxonomy":"todo_status","description":"Developed draft","parent":"209","count":"0"},
{"term_id":"244","name":"Draft","slug":"draft-documented","term_group":"0","term_taxonomy_id":"305","taxonomy":"todo_status","description":"Documented draft","parent":"162","count":"0"}

]}

We really only need

*/
  
  function get_source_terms() {
    $term_tree_array = array( "terms" => $this->terms
                       , "tree" => $this->source_tree
                       );
   // $taxonomy_array = array( $this->taxonomy => $term_tree_array );
    return( $term_tree_array );
  }
  
  /**
   * Update the target tree structure for any new terms
   *
   * Make sure that the target taxonomy hierarchy can handle all the source terms,
   * taking into account their ancestry.
   *
   */
  function update_target_tree() {
  	//bw_trace2();
    wp_delete_object_term_relationships( $this->id, $this->taxonomy );
    $source_tree = $this->get_keyed_array( $this->source_tree, "term_id" );
    $targets = array();
    foreach ( $this->terms as $key => $term ) {
      $ancestry = $this->build_ancestry( $term, $source_tree ); 
      //print_r( $ancestry ); 
      $target_term_id = $this->create_target_tree( $ancestry );
      bw_trace2( $target_term_id, "target_term_id", false );
      $targets[] = (int) $target_term_id;
    }       
    bw_trace2( $this->taxonomy, "taxonomy", false );
    $result = wp_set_object_terms( $this->id, $targets, $this->taxonomy ); 
    bw_trace2( $result, "result" );
  }
  
  /**
   * Create the target tree
   *
   * Build the target tree for this ancestry.
   * Starting from the top and working our way down, 
   * each term needs to exist
   *  
   * 
   * @param array $ancestry - should contain at least one item
   * @return integer - target term ID of the selected term
   * 
   */
  function create_target_tree( $ancestry ) {
    $current_parent = 0;
    $target_term_id = 0;
    foreach ( $ancestry as $key => $term ) {
      $target = $this->find_term_in_parent( $term, $current_parent );
      if ( $target ) {
        $target_term_id = $target->term_id;
        //$term_taxonomy_id = $target->term_taxonomy_id;
      } else {
        $target_term_id = $this->create_term( $term, $current_parent );
      }
      $current_parent = $target_term_id;
    }
    bw_trace2( $target_term_id, "target_term_id", false );
    //bw_trace2( $term_taxonomy_id, "term_taxonomy_id", false );
    //return( $term_taxonomy_id );
    return( $target_term_id );
  }
    
  /*
   * Find term in current parent 
   * 
   * Search through the target terms looking for a term name that 
   * matches with the same parent.
   * 
   * If we find one then we don't need to create this term
   * if we don't find one then we do.
   * The parent_id is ID of the target term's parent
   * NOT the source term
   *
   */
  function find_term_in_parent( $term, $current_parent ) {
  	//bw_trace2( $this->target_tree, "Target tree" );
    $args = array( "parent" => $current_parent 
                 , "slug" => $term->slug
                 );
    $list = wp_list_filter( $this->target_tree, $args );
    bw_trace2( $list, "List");
    $found = current( $list );
    if ( $found && ( $found->name != $term->name || $found->parent != $current_parent ) ) {
      bw_trace2( $found,"found with mismatch" );
      //gobang();
    } 
    
    bw_trace2( $found, "first term" );
    return( $found );
  }  
  
  /**
   * Build the ancestry for the term
   *
   * @param array - the term we need to find
   * @param array - the term tree, keyed by term_id
   * @return array - array of terms under which this term resides plus the term itself
   */
  function build_ancestry( $term, $source_tree ) {
    $ancestry = array();
    $term_data = $term;
    while ( $term_data->parent ) {
      $ancestry[] = $term_data;
      //bw_trace2( $term_data, "term_data", false );
      $term_data = (object ) $source_tree[ $term_data->parent ];
      //bw_trace2( $term_data, "term_data parent", false );
    }
    $ancestry[] = $term_data;
    bw_trace2( $ancestry, "ancestry" );
    $ancestry = array_reverse( $ancestry );
    return( $ancestry );
  }
  
  /**
   * Return a keyed array of objects using a unique ( we hope ) key name
   *
   * @param array $object_arr - array of stdClass Objects each of which has a field with the $key name
   * @return array - sorted assoc array of objects 
   */
  function get_keyed_array( $object_arr, $key ) {
    $keyed = array();
    if ( count( $object_arr ) ) {
      foreach ( $object_arr as $object ) {
        $value = $object->$key;
        $keyed[$value] = (array) $object;
      }
      ksort( $keyed );
    }  
    return( $keyed ); 
  }
  
  /**
   * Create the term for the taxonomy
   *
   * Use the taxonomy from the class, which MAY be the same as the taxonomy in the source term.
   * This method may allow us to use this logic to duplicate taxonomies in a single installation.
   * 
   * Having created the term we need to add it to the target term tree, so that it can be found by the next child.
   * We don't necessarily assign it to the post; this is only done for the last term in this particular ancestry.
   * 
   * Term field    | Value
   * ------------- | ----------- 
   * [name]        | from source
   * [taxonomy]    | from class taxonomy
   * [description] | from source
   * [parent]      | from target parent
   *
   * Fields that get set for us are:
   * 
   * - [term_id]
   * - [term_taxonomy_id] 
   * - [term_group] 
   * - [slug]       
   * - [count]
   * 
   * @param object $term - the source term
   * @param integer $current_parent - the parent for this term
   * @return integer - the new target term ID
   */ 
  function create_term( $term, $current_parent ) {
    $description = $term->description;
    $args = array( "description" => $description
                 , "parent" => $current_parent
                 );
    bw_trace2( $args, "args" );
    $target_term = wp_insert_term( $term->name, $this->taxonomy, $args );
    bw_trace2( $target_term, "target_term", false );
    $term_id = bw_array_get( $target_term, "term_id", null );
    // Now add this term to the target_tree
    $this->add_target_tree_term( $target_term, $term, $current_parent );
    
    return( $term_id );
  }
  
  /**
   * Add the new term to the target tree
   *
   * Having created a new term we need to record this in the target
   * tree to cater for the possibility of children of this term being created as well.
   * We only need to set the fields we use.
   *
   * @param array $target_term - the result from wp_insert_term
   * @param array $term - the source term
   * @param integer $current_parent - the parent ID for this term
   */
  function add_target_tree_term( $target_term, $term, $current_parent ) {
    $new_term = $term;
    $new_term->term_id = $target_term['term_id'];
    $new_term->taxonomy = $this->taxonomy;
    $new_term->name = $term->name;
    $new_term->parent = $current_parent;
    $this->target_tree[] = $new_term;
  } 

}
