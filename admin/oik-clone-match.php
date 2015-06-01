<?php // (C) Copyright Bobbing Wide 2014, 2015

/**
 * Match a post based on the post ID
 *
 * This filter is ideal when the site we're working on was cloned from the source site
 * as the post IDs will match exactly. 
 * 
 * We would also expect the slugs and titles to be the same.
 * The matched array should be keyed by the post ID of the local post we've found
 *
 * @TODO It should also contain other information about the 'match' - to indicate why we should want to copy it. 
 * This should probably include: 
 * - the RAW post_content 
 * - the last updated date
 * 
 */
function oik_clone_match_post_by_ID( $matched, $data ) {
  $post = get_post( $data->ID );
  if ( $post ) {
    $matched = oik_clone_add_to_matched( $matched, array( $post ), $data->ID, "ID" );
  }
  return( $matched );
}

/**
 * Match a post based on the post's slug
 *
 * This filter will find posts which have the same slug.
 * Assumes that bw_get_posts() is accessible.
 * Note: Even though the "slug" field is called "post_name" the query arg we need to use is "name".
 * Ifyou use "post_name" and numberposts is -1 you can run out of memory. 
 * 
 */
function oik_clone_match_post_by_slug( $matched, $data ) {
  //bw_trace2( $data );
  $args = array( "name" => $data->post_name 
               , "post_type" => "any" 
               , "numberposts" => 1
               );
  //gobang();
  $posts = bw_get_posts( $args );
  if ( $posts ) {
    $matched = oik_clone_add_to_matched( $matched, $posts, $data->ID, "slug" );
  }
  return( $matched );
}

/**
 * Match a post based on the post's title
 *
 * This filter will find posts which have the same title
 * Assumes that bw_get_posts() is accessible.
 *
 * Here we'll also match the post type
 * BUT note that this does not allow for post type switching...
 * BUT that should match on the GUID
 * 
 */
function oik_clone_match_post_by_title( $matched, $data ) {
  //bw_trace2( $data );
  $args = array( "post_type" => $data->post_type
               , "numberposts" => -1
               );
  $post_title = esc_sql( $data->post_title );
  oik_clone_match_add_filter_field( "AND post_title = '" . $post_title . "'" );            
  $posts = bw_get_posts( $args );
  if ( $posts ) {
    $matched = oik_clone_add_to_matched( $matched, $posts, $data->ID, "title" );
  }
  return( $matched );
}



/**
 * Match a post based on the post's content
 *
 * This filter will find posts which have the same title
 * Assumes that bw_get_posts() is accessible.
 *
 * Note: This needs a load of escaping to sanitize the post_content
 * AND it's very unlikely to find an exact match.
 *
 * Here we'll also match the post type
 * BUT note that this does not allow for post type switching...
 * BUT that should match on the GUID
 * 
 */
function oik_clone_match_post_by_content( $matched, $data ) {
  //bw_trace2( $data );
  $args = array( "post_type" => $data->post_type
               , "numberposts" => -1
               );
  oik_clone_match_add_filter_field( "AND post_content = '" . $data->post_content . "'" );            
  $posts = bw_get_posts( $args );
  if ( $posts ) {
    $matched = oik_clone_add_to_matched( $matched, $posts, $data->ID, "content" );
  }
  return( $matched );
}


/**
 * Add a filter to allow searching by guid
 *
 * WordPress does not normally allow us to access posts by the GUID
 * but we need this to find if we've previously cloned this content; as an exact clone. 
 * 
 * @param string $filter - an additional where clause such as "AND guid = 'http://qw/wpms/phphants/?page_id=1648'"
 * 
 * 
 */
function oik_clone_match_add_filter_field( $filter ) {
  global $bw_filter; 
  if ( !isset( $bw_filter ) ) {
    add_filter( "posts_where", "oik_clone_match_filter_where" );
    $bw_filter = null ;
  } 
  $bw_filter .= " ";
  $bw_filter .= $filter;
  bw_trace2();
  
  //p( "Added: $filter"  );
  //p( "Filter is now: $bw_filter" );
}

/**
 * Implement "posts_where" for oik-clone match by GUID
 * 
 * During get_posts execution WordPress applies a number of filters to build the query to perform
 * Here we add our clauses to the WHERE clause
 *
 * @param string $where - the current where clause
 * @return string - an updated where clause
 */
function oik_clone_match_filter_where( $where = '' ) {
  global $bw_filter;
  if ( isset( $bw_filter ) ) {
    $where .= $bw_filter;
    unset( $GLOBALS['bw_filter'] );
    bw_trace2( $bw_filter, "unset bw_filter" );
    
  }  
  bw_trace2();
  return( $where );
}
  
/**
 * Match a post based on the post's GUID
 *
 * There may be more than one post with the same GUID.
 * This is possible if the same source post has been imported multiple times.
 * Note: Don't load any posts with the same ID as the current post as this will list duplicates in "Self" processing
 *
 * @param array $matches - the posts that match. Note: posts may exist more than once due to multiple matches
 * @param object $data - the "post" object to match against
 * @return array $matches
 *                                          
 * 
 */
function oik_clone_match_post_by_GUID( $matched, $data ) {
  oik_require( "includes/bw_posts.inc" );
  //p( "Matching {$data->ID} by GUID" );
  $args = array( "numberposts" => -1 
               , "post_type" => "any" 
               //, "exclude" => $data->ID
               );
  oik_clone_match_add_filter_field( "AND guid = '" . $data->guid . "'" );            
  $posts = bw_get_posts( $args );
  if ( $posts ) {
    $matched = oik_clone_add_to_matched( $matched, $posts, $data->ID, "guid" );
  }  
  return( $matched );
} 

/**
 * Add the found posts to the matched array
 *
 * The matched array is keyed by the post ID of the matched posts. 
 * If the post already exists we update the matched string to append the $method
 * If not then we add the new post.
 * 
 * @param array $matched - array of matched posts
 * @param array $posts - array of newly found matches
 * @param string $ID - ID of the post we're attempting to match against.
 * Even if this ID is the same it doesn't mean it's the same post. It depends on the source location.
 * @param string $method - the matching method used
 * @return array - the updated matched array 
 */
function oik_clone_add_to_matched( $matched, $posts, $ID, $method ) {
  foreach ( $posts as $post ) {
    $existing = bw_array_get( $matched, $post->ID, null );
    if ( $existing ) {
      $existing->matched .= ", $method";
    } else {
      $post->matched = $method; 
      $post->source = $ID;
      $post->target = $post->ID; 
      $matched[ $post->ID ] = $post; 
    }  
  }
  return( $matched );
}

/**
 * Define the filters to use for oik clone matching
 * 
 * Add the default filters to be used for performing matching
 * then give other routines the opportunity to add their own...
 * ...which allows for them to lazy load the functions.
 *
 * Allow plugins to define the filters they'll us for matching posts.
 * Any routine that implements this action hook should attach their own filters
 * that hook into "oik_clone_match_post"
 *  
 */
function oik_clone_match_filters() { 
  do_action( "oik_clone_match_filters" );
}

/**
 * Match the returned posts to ones in our site
 *
 * Here we add the filters that we want to use
 * then perform the matching for each post returned
 * The results are used to update the array of posts
 *
 * @param array $posts - array of posts from the target
 * @param array $fields - 
function oik_clone_match_posts( $posts, $fields ) {
  oik_clone_match_filters();
  foreach ( $posts as $key => $data ) {
    $ID = $data['ID'];
    $matched_posts = oik_clone_match_post( $ID, $data, $fields );
    $posts[$key]["matched_posts"] = $matched_posts;
  }
  return( $posts );

}
    * 
 */


