<?php
/**
 * @package oik-clone
 * @copyright (C) Copyright Bobbing Wide 2022
 */
if ( PHP_SAPI !== "cli" ) {
	echo "Unclone_post must be run from the command line.";
	die();
}

$ID = oik_batch_query_value_from_argv( 1, 0 );
if ( $ID ) {
	$post = get_post( $ID );
	if ( $post ) {
		echo "Deleting clone information for:" . PHP_EOL;
		echo "ID:" . $ID . PHP_EOL;
		echo "Post title:" . $post->post_title . PHP_EOL;

		unclone_post( $ID );
	}
} else {
	echo "Syntax oikwp unclone_post.php ID url=blocks.wp.a2z";
}

function unclone_post( $ID) {
	delete_post_meta( $ID, '_oik_clone_ids');
	delete_post_meta( $ID, '_oik_clone_dnc');
}