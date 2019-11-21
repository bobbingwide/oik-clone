<?php // (C) Copyright Bobbing Wide 2015, 2019

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
	oik_require( 'shortcodes/oik-clone.php', 'oik-clone');
	$id = oik_clone_maybe_get_current_post_id( $atts );

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
	oik_require( "admin/class-oik-clone-meta-clone-ids.php", "oik-clone" );
	$clone_meta = new OIK_clone_meta_clone_ids();
	$cloned = $clone_meta->get_clone_info( $post_id );

  //$clones = get_post_meta( $post_id, "_oik_clone_ids", false );
  //$cloned = oik_reduce_from_serialized(  $clones );





	if ( count( $cloned ) ) {
		$label = bw_array_get_dcb( $atts, "label", "Clones of: ", "__", "oik-clone" );
		if ( $label !== false ) {
			e( $label );
			alink( null, get_permalink( $post_id ),  get_the_title( $post_id ) );
		}
    oik_require( "shortcodes/oik-list.php" );
    $uo = bw_sl( $atts );
    foreach ( $cloned as $server => $post ) {
			bw_trace2( $post, "post", false, BW_TRACE_DEBUG );
			if ( is_array( $post ) ) {
				$cloned_date = bw_array_get( $post, "cloned", null );
				$post = bw_array_get( $post, "id", null );
				if ( is_array( $post ) ) {
					$post = bw_array_get( $post, "id", null );
					if ( null == $post ) {
						// Oh good, only two levels of nesting.
					} else {
						// It could still be an array. We need to stop!
						$post=0;
					}
				} else {
					//print_r( $post );
					//bw_trace2( $post, "post now?", false );

				}
			} else {
				$cloned_date = null;
			}
	    //bw_trace2( $post, "post now?", false );
      $url = "$server/?p=$post";
      stag( "li" );
      alink( null, $url );
			if ( $cloned_date ) { 
				br();
				e( bw_format_date( $cloned_date, "M j, Y @ G:i" ) );
			}
			oik_cloned_display_dnc(  $post_id, $server );
      etag( "li" );
    }
    bw_el( $uo );
  }
}

/**
 * @param string $shortcode
 *
 * @return string
 */
function oik_cloned_display_dnc( $post_id, $server ) {
	static $clone_meta_dnc = null;
	if ( null === $clone_meta_dnc ) {
		oik_require( "admin/class-oik-clone-meta-clone-dnc.php", "oik-clone" );
		$clone_meta_dnc = new OIK_clone_meta_clone_dnc();
		$clone_meta_dnc->get_dnc_info( $post_id );
	}
	if ( $clone_meta_dnc->is_slave_dnc( $server ) ) {
		
		e( ' - Do Not Clone');
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








