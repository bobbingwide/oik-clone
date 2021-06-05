<?php // (C) Copyright Bobbing Wide 2015

/**
 * Build the post tree for a post
 *
 */
function oik_clone_build_tree( $id, $atts ) {
	oik_require( "admin/class-oik-clone-tree.php", "oik-clone" );
	oik_clone_tree_filters( $atts );
																																 
	$tree = new OIK_clone_tree( $id, $atts );
	return( $tree );
}

/**
 * Add the filters we'll need
 *
 * @TODO At present we can't tell between formal and informal relationships
 * so some of the informal relationships will be false positives
 * We can either make this more obvious... by indicating how the relationship was determined
 * or improve the informal relationship checking
 * or just live with it 
 *
 * @TODO We could also make this an option on the tree building - using a value in $atts
 * 
 * @param array $atts - shortcode attributes
 */
function oik_clone_tree_filters( $atts ) {
	static $add_filters = true;
	if ( $add_filters ) {
		add_filter( "oik_clone_build_list", "oik_clone_build_list_informal_relationships", 11, 2 );
		$add_filters = false;
	}
}

/**
 * Display the node tree
 * 
 * @param object $tree - the tree of nodes to be displayed
 */
function oik_clone_display_tree( $tree ) {
	$tree->display();
}

/**
 * Perform cloning if it seems acceptable
 *
 * @param integer $id The post being displayed
 * @param array $atts Shortcode atts
 * 
 */
function oik_clone_maybe_perform_clones( $id, $atts ) {
	$clone = bw_array_get( $_REQUEST, "_oik_clone_submit_$id", null );
	if ( $clone ) {
		oik_require( "bobbforms.inc" );
		$clone = bw_verify_nonce( "_oik_clone_form", "_oik_clone_form$id" );
		if ( $clone ) {
			$clone = bw_array_get( $_REQUEST, "clone", array() );
			$clone_ids = array_keys( $clone, "on" );
			if ( count( $clone_ids ) ) {
			  //p( "I should do some cloning now" );
				//p( implode( $clone_ids ) );
				bw_trace2( $clone_ids, "clone_ids", false, BW_TRACE_DEBUG );
				$clone = oik_clone_build_tree( $id, $atts );
				$clone->clone_these( $clone_ids );
				$clone->reclone_these( $clone_ids );
			}
	
		
		}
	}
	return( $clone );
	
} 

/**
 * Display the clone form 
 * 
 * 
 */
function oik_clone_display_form( $tree, $id, $atts ) {
  $class = bw_array_get( $atts, "class", "bw_clone_form" );
  sdiv( $class );
  oik_require( "bobbforms.inc" );
	bw_form();
	$tree->display();
	e( wp_nonce_field( "_oik_clone_form", "_oik_clone_form$id", false, false ) );
	br();
	e( isubmit( "_oik_clone_submit_$id", "Clone" ) );
	etag( "form" );
	ediv();
}
 

/**
 * Implement [clone] shortcode for oik-clone
 *
 * This shortcode displays the clone tree for a particular post.
 * Its purpose is to enable the user to see what might need to be cloned.
 * 
 * I imagine it's going to be quite a big table
 * and will somehow need filtering 
 * - to reduce it to the "status" of cloning to a particular server 
 * - or to exclude the parents of "related" posts
 * - or to include or exclude formal and informal relationships.
 *
 * @TODO We also have to take into account the fact that some post types aren't clonable
 *
 * @param array $atts shortcode parameters
 * @param string $content optional content 
 * @param string $tag invoking shortcode
 * @return string information showing the "tree" for the given post
 */
function oik_clone( $atts=null, $content=null, $tag=null ) {

	if ( !defined( "OIK_APIKEY" )  ) return '';

	$form = current_user_can( "publish_pages" ); 
	if ( $form ) {
		$form = bw_array_get( $atts, "form", "y" );
		$form = bw_validate_torf( $form );
	}
	$atts['form'] = $form;
	$id = oik_clone_maybe_get_current_post_id( $atts );
  if ( $id ) {
    $atts['id'] = $id;
		if ( $form ) {
			oik_clone_maybe_perform_clones( $id, $atts );
		}
    $tree = oik_clone_build_tree( $id, $atts );
		if ( $form ) {
			oik_clone_display_form( $tree, $id, $atts );   
		} else {
			oik_clone_display_tree( $tree ); 
		}
  } else {
    bw_trace2( "Missing post ID", null, true, BW_TRACE_WARNING );
  }
	return( bw_ret() );
}

/**
 * Returns the current post ID when it's sensible
 *
 * @param array $atts - which may have the id= parameter or passed positionally
 *
 */

function oik_clone_maybe_get_current_post_id( $atts ) {
	$id = bw_array_get_from( $atts, "id,0", null );
	if ( null == $id ) {
		if ( is_single() || is_singular() ) {
			$id = bw_current_post_id();
		} else {
			bw_trace2();
		}
	}
	return $id;

}



/*
 * Help hook for [clone] shortcode 
 */
function clone__help( $shortcode="clone" ) {
  return( "Display the clone tree/form for a post" );
}


/**
 * Syntax hook for [clone] shortcode
 */
function clone__syntax( $shortcode="clone" ) {
  $syntax = array( "ID,0" => bw_skv( null, "<i>ID</i>", "Post ID" )
                 , "uo" => bw_skv( "u", "o|d", "List type" ) 
								 , "form" => bw_skv( "y", "n", "Display 'Clone' form if authorised" )	
								 , "order" => bw_skv( "y", "n", "Display in suggested cloning order" )
                 );
  return( $syntax );
}
