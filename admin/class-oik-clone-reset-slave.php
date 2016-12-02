<?php // (C) Copyright Bobbing Wide 2016
if ( PHP_SAPI !== "cli" ) { 
	die();
}
/**
 * Reset the slave clone information after local import
 *
 * When a local site has been imported from the live site
 * then we need to reset the clone information for each post
 * to allow easy re-cloning back to the original post.
 * 
 * We need to pass the URL of the slave - the live site that was loaded locally
 * e.g. perform the following commands to update the slave information
 * of the local installation at qw/oikcom of http://oik-plugins.com
 * 
 * ` 
 * cd \apache\htdocs\oikcom\wp-content\plugins\oik-clone\admin
 * oikwp oik-reset-slave.php http://oik-plugins.com
 * `
 * 
 * After running `reset-slave` you may also need to run `reset-ids` for each of the other slave servers
 * 
 */
class OIK_clone_reset_slave {

	public $slave = null;

	/** 
	 * Constructor for OIK_clone_reset_slave
	 * 
	 * Get the slave URL and update all posts that are clonable
	 */ 
	function __construct() {
		oik_require( "includes/bw_posts.inc" );
		$this->get_slave();
		$this->process_post_types();
	}

	/**
	 * Obtain the value for the slave
	 * 
	 * If not specified then die.
	 * If it is specified perhaps we should check it to be a valid URL
	 * or maybe we can determine the slave as the first from the list of slaves
	 */
	function get_slave() {
		$slave = oik_batch_query_value_from_argv( 1, null );
		if ( !$slave ) { 
			echo PHP_EOL;
			echo "Syntax: oikwp oik-clone-reset-slave.php slave" . PHP_EOL ;
			echo "e.g. oikwp oik-clone-reset-slave.php http://oik-plugins.com" . PHP_EOL;
			die( "Try again with the right parameters");
		}
		$this->slave = $slave;
	}

	/**
	 * Convert all clonable post types
	 *
	 * foreach post type
	 *  if clonable
	 *  	convert all posts for the post type
	 */
	function process_post_types() {
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
	 * Convert all posts for the post type
	 *
	 * - fetch all posts for the post type
	 * - for each post, update the _oik_clone_ids post meta for the selected slave
	 *
	 * @param string $post_type
	 */
	function process_all_posts( $post_type ) {
		$atts = array( "post_type" => $post_type
								 , "post_status" => "any"
								 , "numberposts" => -1
								 );
		$posts = bw_get_posts( $atts );
		if ( $posts ) {
			foreach ( $posts as $post )	{
				$this->process_post( $post );
			}
		}
	}

	/**
	 * Update the _oik_clone_ids for the primary slave
	 *
	 * @param object $post the post object
	 */
	function process_post( $post ) {
		echo "Updating post meta clone ID for {$post->ID} {$post->post_title}" . PHP_EOL;
		//delete_post_meta( $post->ID, "_oik_clone_ids" );
		//echo "Inserting new slave";
		$time = strtotime( $post->post_modified_gmt );
		$post_meta = array( $this->slave => array( "id" => $post->ID, "cloned" => $time ) );
		update_post_meta( $post->ID, "_oik_clone_ids", $post_meta );
	}

}


 
