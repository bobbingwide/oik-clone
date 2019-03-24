<?php

if ( PHP_SAPI !== "cli" ) {
	die();
}
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * Batch interface to push content from one MultiSite site to another
 *
 * In WordPress MultiSite environment there are times when we want to clone content from one site to another.
 * class-oik-clone-push can be used to pull content from one site to another.
 * We now need to be able to push the cloned content to a remote site.
 *
 * In the scenario for oik-clone-pull we copied all the "block" posts and their attachments from the core subdomain to blocks.
 * Now we need to push these from the local install to the remote system at blocks.wp-a2z.org
 * For core there were 70 blocks and associated attachments.
 * So it's reasonable to want a batch routine.
 * The future use case is for any other cloning site where we want to batch update all the changes that have been made in the local system.
 *

 * Syntax
 * cd \apache\htdocs\wp-a2z\wp-content\plugins\oik-clone\admin
 * oikwp class-oik-clone-push.php target.domain post_type url=source.domain
 *
 * e.g.
 * oikwp class-oik-clone-push.php blocks.wp-a2z.org block url=blocks.wp.a2z
 *
 */

class OIK_clone_push {
	/**
	 * Target domain
	 *
	 */
	public $target_domain = null;



	/**
	 * Post type
	 */
	public $post_type = null;

	public $force_update = false;
	/**
	 * Constructor for OIK_clone_pull
	 *
	 * Determine the source site and clone the selected post type
	 * @TODO Only clone the posts that need updating.
	 *
	 */
	function __construct() {
		oik_require( "includes/bw_posts.php" );
		//oik_require( "admin/oik-clone-clone.php", "oik-clone" );
		//oik_require( "admin/oik-clone-actions.php", "oik-clone" );
		//oik_require( "admin/oik-clone-ms.php", "oik-clone" );
		//oik_require( "admin/oik-clone-relationships.php", "oik-clone" );

		$this->get_target_domain();

		$this->get_post_type();
		//add_filter( "oik_clone_load_source", [ $this, "load_ms_source" ], 10, 2 );
		//kses_remove_filters();

		$this->force_update( false );
		$this->process_all_posts( $this->post_type );
	}

	/**
	 * Obtain the value for the source domain
	 *
	 * If not specified then die.
	 */
	function get_target_domain() {
		$target_domain = oik_batch_query_value_from_argv( 1, null );
		if ( !$target_domain ) {
			echo PHP_EOL;
			echo "Syntax: oikwp class-oik-clone-push.php target.domain posttype url=source.domain" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-push.php blocks.wp-a2z.org block url=blocks.wp.a2z" . PHP_EOL;
			die( "Try again with the right parameters.");
		}
		$this->target_domain = $target_domain;
	}

	function get_post_type() {
		$post_type = oik_batch_query_value_from_argv( 2, null );
		if ( ! $post_type ) {
			die( "Try again" );
		}
		$this->post_type = $post_type;
	}

	function force_update( $force = null ) {
		if ( $force !== null ) {
			$this->force_update = $force;
		}
		return $this->force_update;
	}

	/**
	 * Process all clonable post types
	 *
	 * foreach post type
	 *  if clonable
	 *  	push all posts for the post type
	 */
	function process_post_types() {
		gob( "Not yet implemented");
		$post_types = get_post_types();
		//print_r( $post_types );
		foreach ( $post_types as $post_type ) {
			$supports = post_type_supports( $post_type, "clone" );
			if ( $supports ) {
				echo "Processing: $post_type" . PHP_EOL;
				$this->process_all_posts( $post_type );
			}	else {
				echo "Skipping: $post_type" . PHP_EOL;
			}
		}
	}

	/**
	 * Clone all posts for the post type
	 *
	 * - fetch all posts for the post type. Note: "any" includes draft!
	 *
	 * - for each post clone from source to target if it needs it.
	 *
	 *
	 * @param string $post_type
	 */
	function process_all_posts( $post_type ) {
		$atts = array( "post_type" => $post_type
		, "post_status" => "any"
		, "numberposts" => -1
		, "post_parent" => "."
		);

		$posts = bw_get_posts( $atts );

		if ( $posts ) {
			$count = count( $posts );
			echo "Processing: " . $count;
			echo PHP_EOL;
			foreach ( $posts as $post )	{
				$this->process_post( $post );
			}
		}
	}

	/**
	 * push the post from source to target
	 * oik_clone_perform_import has the basic logic but this already knows the target id
	 * we need to determine the target ID in the same way a remote slave does... when the passed target_id is 0.
	 * And we have to ensure that the source post is correctly loaded from the source site.
	 * So we have to reuse the post loading logic!
	 *
	 *
	 *
	 *
	 * @param object $post the post object
	 */
	function process_post( $post ) {
		//echo $this->source_domain;
		//echo $this->source_blog_id ;
		echo "Considering: {$post->ID}: {$post->post_title} - {$post->post_name} ";

		$this->check_if_cloning_necessary( $post );
		bw_flush();



	}

	/**
	 * Checks if cloning of the post from master to the selected slave is necessary
	 *
	 * There is logic in the "cloned" shortcode that does this.
	 *
	 * @param $post
	 * @return bool true if cloning is necessary
	 */
	function check_if_cloning_necessary( $post ) {
		oik_require( "admin/class-oik-clone-tree.php", "oik-clone");
		$tree = new OIK_clone_tree( $post->ID, [ "form" => false ] );

		$tree->maybe_clone();
		//$tree->display_nodes();
		//$node = new OIK_clone_tree_node( $post->ID );
		//$node->get_post();
		//$status = $node->clone_status();
		//echo $status . PHP_EOL;
		return false;
	}

	/**
	 * Checks that the slave has not been changed
	 *
	 * @param $post
	 * @return bool true if the slave has not been changed
	 */

	function check_slave_has_not_been_changed( $post ) {
		return true; // slave has not been changed
	}

	function clone_post( $post ) {
		echo "Cloning $post to {$this->target_domain}" . PHP_EOL;

	}




}