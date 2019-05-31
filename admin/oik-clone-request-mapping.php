<?php // (C) Copyright Bobbing Wide 2016-2019

/**
 * Return oik-clone mapping of selected post type
 * 
 * Load all the posts of the selected type and which have clones.
 * For each entry which has a mapping for the chosen master
 * create a mapping constructed as:
 * 
 * key    | value 
 * ------ | ---------
 * id     | post ID of the master post
 * slave  | post ID of the slave post
 * cloned | UNIX timestamp of the most recently cloned master 
 * name   | post name of the slave post	- to check we've got the right master ID
 * modified | post modified gmt of the slave post
 *  
 * @return array of mapping 
 */
function oik_clone_lazy_request_mapping() {
	bw_trace2( $_REQUEST, "_REQUEST" );
	$post_type = bw_array_get( $_REQUEST, "post_type" );
	$master = bw_array_get( $_REQUEST, "master" );
	$atts = array( "post_type" => $post_type
							 , "numberposts" => -1
							 , 'post_parent' => 'ignore'
							 );
	oik_require( "includes/bw_posts.php" );							 
	$posts = bw_get_posts( $atts ); 
	$mapping = array();
	foreach ( $posts as $post ) {
		$id = null;
		$cloned = 0;
		$clones = get_post_meta( $post->ID, "_oik_clone_ids", false );
		bw_trace2( $clones, "clones" );
		if ( $clones ) {
			$clones = bw_array_get( $clones, 0, array() );
			bw_trace2( $clones, "clones" );
			$data   = bw_array_get( $clones, $master, null );
			$cloned = 0;
			if ( $data ) {
				bw_trace2( $data, "data", false );
				if ( is_array( $data ) ) {
					$id = bw_array_get( $data, "id", null );
					if ( is_array( $id ) ) {
						$id = bw_array_get( $id, "id", null );
					}
					$cloned = bw_array_get( $data, "cloned", 0 );
				} else {
					$id = $data;
				}

			}
		}
		if ( $id ) {
			$mapping[] = array(
				"id"       => $id,
				"slave"    => $post->ID,
				"cloned"   => $cloned,
				"name"     => $post->post_name,
				"modified" => $post->post_modified_gmt
			);
		} else {
			$mapping[] = array(
				'id' => null,
				'slave' => $post->ID,
				'cloned' => $cloned,
				'name' => $post->post_name,
				'modified' => $post->post_modified_gmt

			);
		}
	}
	return( $mapping );
}
