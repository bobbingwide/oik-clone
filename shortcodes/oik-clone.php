<?php // (C) Copyright Bobbing Wide 2015

/**
 * Build the post tree for a post
 *
 */
function oik_clone_build_tree( $id, $atts ) {
	oik_require( "admin/class-oik-clone-tree.php", "oik-clone" );
	$tree = new OIK_clone_tree( $id, $atts );
	return( $tree );
}

/**
 * Display the node tree
 */
function oik_clone_display_tree( $tree ) {
	bw_trace2();
	$tree->display();
	$tree->display_ordered(); 
} 
 

/**
 * Implement [clone] shortcode for oik-clone
 *
 * This shortcode displays the clone tree for a particular post
 * Its purpose is to enable the user to see what might need to be cloned
 * I imagine it's going to be quite a big table
 * and will somehow need filtering to reduce it to the "status" of cloning 
 * to a particular server or to exclude the parents of "related" posts
 * including both formal and informal relationships.
 *
 *
 * @param array $atts shortcode parameters
 * @param string $content optional content 
 * @param string $tag invoking shortcode
 * @return string information showing the "tree" for the given post
 */
function oik_clone( $atts=null, $content=null, $tag=null ) {
	$id = bw_array_get_from( $atts, "id,0", null );
  if ( null == $id ) {
    $id = bw_current_post_id();
  }
  if ( $id ) {
    $atts['id'] = $id;
    $tree =oik_clone_build_tree( $id, $atts );
		oik_clone_display_tree( $tree, $id, $atts );    
  } else {
    bw_trace2( "Missing post ID", null, true, BW_TRACE_WARNING );
  }
	return( bw_ret() );
}


/*
 * Help hook for [clone] shortcode 
 */
function clone__help( $shortcode="clone" ) {
  return( "Display the clone tree for a post" );
}


/**
 * Syntax hook for [clone] shortcode
 */
function clone__syntax( $shortcode="clone" ) {
  $syntax = array( "ID,0" => bw_skv( null, "<i>ID</i>", "Post ID" )
                 , "uo" => bw_skv( "u", "o|d", "List type" ) 
                 );
  return( $syntax );
}
