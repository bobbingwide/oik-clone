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
 * Displaying the "label" is optional. 
 *
 * @param ID $post_id - the ID of the post 
 * @param array $atts - shortcode attributes incl ['id'] matching $post_id
 */
function oik_cloned_display_links( $post_id, $atts ) {
  oik_require( "admin/oik-clone-meta-box.php", "oik-clone" );
  $clones = get_post_meta( $post_id, "_oik_clone_ids", false );
  $cloned = oik_reduce_from_serialized(  $clones );
  if ( count( $cloned ) ) {
		$label = bw_array_get_dcb( $atts, "label", "Clones of: ", "__", "oik-clone" );
		if ( $label !== false ) {
			e( $label );
			alink( null, get_permalink( $post_id ),  get_the_title( $post_id ) );
		}
    oik_require( "shortcodes/oik-list.php" );
    $uo = bw_sl( $atts );
    foreach ( $cloned as $server => $post ) {
			if ( is_array( $post ) ) {
				$cloned_date = bw_array_get( $post, "cloned", null );
				$post = bw_array_get( $post, "id", null );
			} else {
				$cloned_date = null;
			}
      $url = "$server/?p=$post";
      stag( "li" );
      alink( null, $url );
			if ( $cloned_date ) { 
				br();
				e( bw_format_date( $cloned_date, "M j, Y @ G:i" ) );
			}
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
								 , "label" => bw_skv( __( "Clones of: ", "oik-clone" ), "<i>text</i>|false", "Prefix with source link" )
                 );
  return( $syntax );
}








