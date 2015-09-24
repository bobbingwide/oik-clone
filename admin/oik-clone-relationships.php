<?php // (C) Copyright Bobbing Wide 2015

/**
 * Support for cloning relationship information between sites
 *
 *
 * When a post is cloned from one site to another then the chances are that it will get a different ID on the target site.
 * So what do you do if a post has relationships to another post?
 *
 * You need to be able to point to the correct post on the target site.
 *
 * 
 * oik-clone achieves this by storing the target post ID on post meta data keyed '_oik_clone_ids' 
 * 
 * It's stored as a serialized array, where the key of each array element is the server URL
 * a:3:{s:19:"http://qw/wordpress";i:29884;s:24:"http://oik-plugins.co.uk";i:8145;s:21:"http://oik-plugins.uk";i:16318;}
 *
 * ie. 
 * `Array
 * (
 *  [http://qw/wordpress] => 29884
 *  [http://oik-plugins.co.uk] => 8145
 *  [http://oik-plugins.uk] => 16318
 * )`
 * 
 *
 * The simplest example of a relationship with another post is the post->post_parent relationship
 * Whenever we clone a post which has a parent we need to be able to tell the server the ID that the server should use.
 *
 * We will achieve this by passing a simple array of source to target IDs.
 * e.g. for post 16440, which originally had no parent when cloned  to 29936,
 * but was then given one, the array we pass to qw/wordpress may consist of:
 * 
 * source | target | which is
 * ------ | ------ | ------------------------------------------------
 * 16440  | 29936  | A simple mapping of the current post - it may be easier to use than accessing target
 * 4941   | 0      | Post parent - not known to have been cloned
 * 16362  | 0      | Featured image ( _thumbnail_id )
 * 2384   | 0      | Plugin reference ( _plugin_ref ) - a field of type "noderef"
 *
 * The server needs to ensure that we don't create a post with the wrong parent. 
 * Otherwise we might end up with the post being created as a revision of the wrong post altogether.
 *
 * 
 *
 * Since there can be multiple slaves we may need to build the array in three stages:
 * 1. Find the complete array of post IDs to map 
 * 2. Load the complete set of target post meta data
 * 3. For each server, load the mapping specific to the target
 * 
 * There will be cases where a post has not been cloned to the server.
 * The solution will need to cater for this without falling over in a big heap.
 * We need to avoid falling into the trap of cyclic dependency checking.
 *
 * ie. We can't have the attempting to perform reasoning like this
 * You can't create post A, you need to create post B first, but that has relationship to post C, which refers back to A.
 * 
 * We'll achieve this by simply allowing the source post to not know the target ID
 * 
 * Whether or not we get the target to look up the post and return the mapping is something we'll have to consider.
 * 
 * Note: This solution ignores Users and Comments
 *  
 */
function oik_clone_relationships( $post ) {
  oik_require( "admin/class-oik-clone-relationships.php", "oik-clone" );
  $relationships = new OIK_clone_relationships();
  $relationships->build_list( $post );
  $relationships->load_slave_ids();
  return( $relationships );
}

/**
 * Implement "oik_clone_build_list" filter for informal relationships
 *
 * The post_content and post_excerpt may contain references to other posts by ID
 * we need to map these to the target IDs
 * 
 * The client will create the mapping, and the server will apply it
 * 
 * @param array $source_ids - the currently known IDs to be mapped
 * @param object $post - the post object 
 * @return array - an updated array of source IDs
 *
 */
function oik_clone_build_list_informal_relationships( $source_ids, $post ) {
  oik_require( "admin/class-oik-clone-informal-relationships-source.php", "oik-clone" );
  $content_informal_relationships = new OIK_clone_informal_relationships_source( $source_ids );
  $content_informal_relationships->find_ids( $post->post_content );
  $content_informal_relationships->find_ids( $post->post_excerpt );
  return( $content_informal_relationships->source_ids );
}

/**
 * Apply the mapping to the target post and the post meta
 *
 * By the time we call this we know that we're going to be applying the changes
 * on the server so now is the time to perform the mapping.
 * 
 * If there are any self-references in the post_meta data we need
 * to ensure that this is correctly mapped.
 * So we defer the mapping of post_meta data until after the post is created.
 * Similar argument for the post_parent. 
 * 
 */
function oik_clone_apply_mapping( $post ) {
  oik_require( "admin/class-oik-clone-mapping.php", "oik-clone" );
  
  $mapping = new OIK_clone_mapping();
  $mapping->load_mapping();
  $mapping->apply_post_parent_mapping( $post );
  // @TODO better code not using global
  global $oik_clone_mapping;
  $oik_clone_mapping =  $mapping;
  
  // Now apply filters to apply the informal relationship mapping to the post
  
  add_filter( "oik_clone_apply_informal_mapping", "oik_clone_apply_informal_relationship_mapping", 10, 2 );
  add_filter( "oik_clone_apply_informal_mapping", "oik_clone_apply_informal_relationship_mapping_urls", 11, 2 );
  $post = apply_filters( "oik_clone_apply_informal_mapping", $post, $mapping->mapping );
  return( $post );
}

/**
 *
 * Implement "oik_clone_apply_informal_mapping" to the target post IDs
 *
 * At the target we apply the mapping to the content when
 * - we have a candidate post ID
 * - there is a known mapping in table
 * - the ID passes the tests
 *  
 * @param object $post - the post object 
 * @param array $target_ids - the mapping from source to target IDs
 * @return object - the updated post object 
 * 
 */
function oik_clone_apply_informal_relationship_mapping( $post, $target_ids ) {
  oik_require( "admin/class-oik-clone-informal-relationships-target.php", "oik-clone" );
  $target_informal_relationships = new OIK_clone_informal_relationships_target( $target_ids );
  //$target_informal_relationships->set_mapping( $target_ids );
  $post->post_content = $target_informal_relationships->map_ids( $post->post_content );
  $post->post_excerpt = $target_informal_relationships->map_ids( $post->post_excerpt );
  return( $post );
}

/**
 * Implement "oik_clone_apply_informal_mapping" to the target post URLs 
 *
 * Ignore the target_ids
 *
 * For each of the fields in the post that may contain the source ( master ) root URL
 * replace it with the target ( slave ) URL
 *
 * We need to cater for a number of combinations:
 * - links
 * - references in shortcodes e.g.
 *
 * `
 * [bw_link example.com]
 * [bw_link //example.com]
 * [bw_link http://example.com]
 * [bw_link https://example.com]
 * [bw_link example.com/site ]
 * `
 * 
 * Do we need to care about delimeters?
 * 
 * @param object $post - the target post object
 * @param array $target_ids - which we ignore for this filter
 * @return object - the updated post object
 * 
 *
 */
function oik_clone_apply_informal_relationship_mapping_urls( $post, $target_ids ) {
  $master = bw_array_get( $_REQUEST, "master", null );
  $source = str_replace( "http://", "",  $master );
  $target = site_url('', 'http' );
  $target = str_replace( "http://", "",  $target );
  $post->post_content = str_replace( $source, $target, $post->post_content );
  $post->post_excerpt = str_replace( $source, $target, $post->post_excerpt );
  bw_trace2();
  return( $post );
}

/**
 * Apply mapping and other filters to all post_meta data
 * 
 * Things we need to do:
 * - update post relationships to use the correct target post
 * - remove post meta data that we don't want to create
 * 
 * @param object $post - the complete post including the post_meta field 
 * 
 */
function oik_clone_filter_all_post_meta( $post ) {
  global $oik_clone_mapping;
  $mapping = $oik_clone_mapping;
  $post_meta = (array) $post->post_meta;
  bw_trace2( $post_meta, "post_meta", true );
  foreach ( $post_meta as $key=> $meta ) {
    //$meta = apply_filters( "
    
    bw_trace2( $meta, $key, false );
    if ( $mapping->is_relationship_field( $key, $meta ) ) {
      $meta = $mapping->apply_mapping( $meta ); 
    } 
    $filtered_post_meta[ $key ] = $meta;
  }
  $post->post_meta = $filtered_post_meta;
}

/**
 * Implement "oik_clone_filter_post_meta" for relationship fields
 * 
 */
function oik_clone_filter_post_meta( $meta, $key ) {
  return( $meta );
}

 
  

   

