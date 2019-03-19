<?php

if ( PHP_SAPI !== "cli" ) {
	die();
}
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * Batch interface to pull content from one MultiSite site to another
 *
 * In WordPress MultiSite environment there are times when we want to clone content from one site to another.
 * e.g. In wp-a2z.org we need to copy all the "block" posts and their attachments from the core subdomain to blocks.
 * In other sites we may want to develop posts in a play area then publish them in a live site.
 * In the first example a pulling appears to be the easiest - as it's just a batch interface to the MultiSite pull.
 * In the second example pushing might be more appropriate.

 * Syntax
 * cd \apache\htdocs\wp-a2z\wp-content\plugins\oik-clone\admin
 * oikwp class-oik-clone-pull.php source.domain post_type url=target.domain
 *
 *
 */

class OIK_clone_pull {
	/**
	 * Source domain
	 *
	 */
	public $source_domain = null;

	/**
	 * We need the source ID of the domain we're pulling from.
	 * The blog ID is what we use in a simple network of blog.
	 * The site_ID is when there are multiple sites in a Network.
	 */
	public $source_blog_id = null;

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
		oik_require( "admin/oik-clone-clone.php", "oik-clone" );
		oik_require( "admin/oik-clone-actions.php", "oik-clone" );
		oik_require( "admin/oik-clone-ms.php", "oik-clone" );
		oik_require( "admin/oik-clone-relationships.php", "oik-clone" );

		$this->get_source_domain();
		$this->get_source_blog_id();
		$this->get_post_type();
		add_filter( "oik_clone_load_source", [ $this, "load_ms_source" ], 10, 2 );
		kses_remove_filters();

		$this->force_update( true );
		$this->process_all_posts( $this->post_type );
	}

	/**
	 * Obtain the value for the source domain
	 *
	 * If not specified then die.
	 */
	function get_source_domain() {
		$source_domain = oik_batch_query_value_from_argv( 1, null );
		if ( !$source_domain ) {
			echo PHP_EOL;
			echo "Syntax: oikwp class-oik-clone-pull.php source.domain posttype url=target.domain" . PHP_EOL ;
			echo "e.g. oikwp class-oik-clone-pull.php core.wp.a2z block url=blocks.wp.a2z" . PHP_EOL;
			die( "Try again with the right parameters.");
		}
		$this->source_domain = $source_domain;
	}

	function get_post_type() {
		$post_type = oik_batch_query_value_from_argv( 2, null );
		if ( ! $post_type ) {
			die( "Try again" );
		}
		$this->post_type = $post_type;
	}

	/**
	 * Sets the source blog ID for use both here and in oik-clone-ms.php
	 */
	function get_source_blog_id() {
		$source_blog = $this->source_domain;
		if ( is_integer( $source_blog )) {
			$site = get_site( $source_blog );
			print_r( $site );
			if  ( $site ) {
				$this->source_blog_id = $site->blog_id;
			}
		} else {
			$sites = get_sites();
			//print_r( $sites );
			foreach ( $sites as $site ) {
				if ( $site->domain === $source_blog ) {
					$this->source_blog_id = $site->blog_id;
				} else {
					//echo $site->blog_id . "," . $site->domain . "," . $source_blog;
				}
			}


		}
		echo $this->source_domain;
		echo PHP_EOL;
		echo $this->source_blog_id;
		echo PHP_EOL;
		$_REQUEST[ '_oik_ms_source' ] = $this->source_blog_id;



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
	 *  	pull all posts for the post type
	 */
	function process_post_type() {
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
	 * - fetch all posts for the post type
	 * - for each post clone from source to target
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
		switch_to_blog( $this->source_blog_id );
		$posts = bw_get_posts( $atts );
		restore_current_blog();
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
	 * Pull the post from source to target
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

		echo "Cloning {$post->ID} {$post->post_title} {$post->post_name} " . PHP_EOL;
		$target_id = 0;
		$target_id = oik_clone_determine_target_id( $post->ID, 0, $post );
		//echo "eh?";
		echo "Target: " . $target_id;
		echo PHP_EOL;

		if ( $target_id ) {
			$target = get_post( $target_id );
			if ( $post->post_modified_gmt > $target->post_modified_gmt || $this->force_update() ) {
				//add_filter( "oik_clone_load_source", "oik_clone_load_ms_source", 10, 2 );
				oik_clone_perform_import( $post->ID, $target_id );
			} else {
				echo "Not updating existing post!" . PHP_EOL;
			}
		} else {
			$target_id = oik_clone_perform_import( $post->ID, $target_id );
			echo "Created: " . $target_id . PHP_EOL;
		}

	}

	/**
	 * Implements "oik_clone_load_source" for oik-clone pull logic
	 *
	 * @param post|null $post - the source post object
	 * @param ID $source the source post ID
	 * @return object
	 */
	function load_ms_source( $post, $source ) {
		//print_r( $source );
		echo "Loading source: " . $source;
		echo PHP_EOL;
		$post = oik_clone_load_ms_post( $this->source_blog_id, $source );

		echo "Loaded source: " . $source;
		echo PHP_EOL;

		//print_r( $post );
		//gob();


		return $post ;
	}


}