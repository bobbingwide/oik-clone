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
 * oikwp class-oik-clone-push.php post_type url=source.domain
 *
 * e.g.
 * oikwp class-oik-clone-push.php blocks.wp-a2z.org block url=blocks.wp.a2z
 *
 */

class OIK_clone_push {

	/**
	 * Post type
	 */
	public $post_type = null;

	public $force_update = false;

	public $dry_run = false;
	/**
	 * Constructor for OIK_clone_push
	 *
	 */
	function __construct() {
		oik_require( "includes/bw_posts.php" );
		oik_require( "admin/class-oik-clone-tree.php", "oik-clone");
		$this->get_post_type();
		$this->get_dry_run();
		$this->force_update( false );
		$this->process_all_posts( $this->post_type );
	}

	function get_post_type() {
		$post_type = oik_batch_query_value_from_argv( 1, null );
		if ( ! $post_type ) {
			echo PHP_EOL;
			echo "Syntax: oikwp class-oik-clone-push.php posttype url=source.domain" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-push.php block url=blocks.wp.a2z" . PHP_EOL;
			die( "Try again with the right parameters.");
		}
		$this->post_type = $post_type;
	}

	function get_dry_run() {
		$dry_run = oik_batch_query_value_from_argv( "dry-run", "n");
		$this->dry_run = bw_validate_torf( $dry_run );
	}

	function force_update( $force = null ) {
		if ( $force !== null ) {
			$this->force_update = $force;
		}
		return $this->force_update;
	}

	/**
	 * Process all cloneable post types
	 *
	 * foreach post type
	 *  if cloneable
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
	 * - for each post clone from source to slaves if it's needed.
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
	 * Maybe push the post from source to slaves
	 *
	 * oik_clone_perform_import has the basic logic but this already knows the target id
	 * we need to determine the target ID in the same way a remote slave does... when the passed target_id is 0.
	 * And we have to ensure that the source post is correctly loaded from the source site.
	 * So we have to reuse the post loading logic!
	 *
	 * @param object $post the post object
	 */
	function process_post( $post ) {
		$prefix = $this->dry_run ? "Dry run:" : "Considering";
		echo "$prefix {$post->ID}: {$post->post_title} - {$post->post_name} ";
		$tree = new OIK_clone_tree( $post->ID, [ "form" => false, "dry-run" => $this->dry_run ] );
		$tree->maybe_clone();
		bw_flush();
	}
}