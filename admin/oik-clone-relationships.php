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
 * Apply the mapping to the target post and the post meta
 *
 * By the time we call this we know that we're going to be applying the changes
 * on the server so now is the time to perform the mapping
 * We do hope for our sakes that there aren't any self references in the post_meta data
 * Maybe we should defer mapping the post_meta data until later.
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
  
  
}

 
  

   

