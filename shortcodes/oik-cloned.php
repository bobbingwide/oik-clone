<?php // (C) Copyright Bobbing Wide 2015

/**
 * Implement [cloned] shortcode for oik-clone
 *
 * The cloned shortcode will display the same sort of content as the Clone on update meta box
 * only listing the targets where the cloning has been done.
 * 
 * The links will probably be ugly initially
 * 
 *
 * @TODO If used in a widget then it should only work with the page is_single(). How can we tell? What will the filter be?
 * 
 */
function oik_cloned( $atts=null, $content=null, $tag=null ) {
  $id = bw_array_get_from( $atts, "id,0", null ); 
  if ( null == $id ) {
    $id = bw_current_post_id();
  }
  if ( $id ) {
    $atts['id'] = $id;
    oik_cloned_display_links( $id, $atts );    
  } else {
    bw_trace2( "Missing post ID" );
  }
  return( bw_ret() );
}

/**
 * Display the links to cloned content
 * 
 * We don't care about the post type's publicize setting 
 * Just look at the post meta data
 * We don't even need to know the current slaves?
 *
 * @param ID $post_id - the ID of the post 
 * @param array $atts - shortcode attributes incl ['id'] matching $post_id
 */
function oik_cloned_display_links( $post_id, $atts ) {
  oik_require( "admin/oik-clone-meta-box.php", "oik-clone" );
  $clones = get_post_meta( $post_id, "_oik_clone_ids", false );
  $cloned = oik_reduce_from_serialized(  $clones );
  if ( count( $cloned ) ) {
    oik_require( "shortcodes/oik-list.php" );
    //p( "Clones of:" );
    alink( null, get_permalink( $post_id ), "Clones of: " . get_the_title( $post_id ) );
    $uo = bw_sl( $atts );
    foreach ( $cloned as $server => $post ) {
      $url = "$server/?p=$post";
      stag( "li" );
      alink( null, $url );
      etag( "li" );
    }
    bw_el( $uo );
  }
}

/*
 * Help hook for [cloned] shortcode 
 */
function cloned__help( $shortcode="cloned" ) {
  return( "Display clones of this content" );
}


/**
 * Syntax hook for [cloned] shortcode
 */
function cloned__syntax( $shortcode="cloned" ) {
  $syntax = array( "ID,0" => bw_skv( null, "<i>ID</i>", "Post ID" )
                 , "uo" => bw_skv( "u", "o|d", "List type" ) 
                 );
  return( $syntax );
}








