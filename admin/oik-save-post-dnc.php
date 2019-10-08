<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 */

/**
 * Set the Do Not Clone values for the current post's slaves
 *
 * When a post is "published" we set the Do Not Clone flag for each potential slave .
 *
 * This function is supported for post types which support 'clone'
 *
 * We need to ignore draft posts and newly created posts.
 *
 * Special logic may be required for "deleted" posts
 *
 * @param ID $id - the ID of the post being saved
 * @param post $post - the post object
 * @param bool $update - true for an update, probably never false.
 */
function oik_clone_lazy_save_post_dnc( $id, $post, $update ) {
	bw_trace2();
	$post_type = $post->post_type;
	switch ( $post->post_status ) {
		case 'auto-draft':
		case 'private':
			break;

		case 'inherit':
			/**
			 * Attachments are dealt with separately with different filters. See 'edit_attachment'
			 * We don't expect any other post type to have a post status of 'inherit'
			 */
			break;

		case 'draft':
		case 'future':
		case 'publish':
			// Belt and braces tests - we know that the dnc array is not empty
			// and that the post type supports clone

			//if ( post_type_supports( $post->post_type, "clone" ) ) {
				oik_require( 'admin/class-oik-clone-meta-clone-dnc.php', 'oik-clone');
				$clone_meta = new OIK_clone_meta_clone_dnc();
				$clone_meta->set_dncs( $id );
			//} else {
			//	// Post type does not support 'clone' so we don't do anything
			//}
			break;

		case 'trash':
			// One day we may also trash the slaves
			break;

		default:
			bw_trace2( $post->post_status, "post_status" );
			//gobang();
			break;
	}
}