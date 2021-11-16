<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package oik-clone
 *
 */#
/**
 * Load the comments for this post
 *
 * We want an array of all the comments for this post in a sensible format

 * comment_count should contain the count of comments
 * comment_status indicates whether or not comments are open or closed.
 *
 * @param ID $post_id - the ID of the post for
 * @param post $post - the post object
 * @return array - arary of comments
 *
 */
function oik_clone_load_comments( $post_id, $post ) {
    $comments = [];
    bw_trace2();

    if ( $post->comment_count > 0 ) {

        // get_comments returns an array of WP_Comment objects

        $comment_objects = get_comments( [ 'post_id' => $post_id ]);
        // we only need a few of these fields in an array of arrays
        $comments = oik_clone_get_comments_fields( $comment_objects );
    }
    bw_trace2( $comments, "comments", false );

    return $comments;
}

/**
 * Gets the comments as an array of comments arrays.
 *
 * @param $comment_objects
 * @return array
 */
function oik_clone_get_comments_fields( $comment_objects ) {
    $comments = [];
    foreach ( $comment_objects as $comment_object ) {
        $comments[] = oik_clone_get_comment_fields( $comment_object);
    }
    return $comments;
}

/**
 * Gets the relevant comment fields from object to array.
 *
 * @param $comment_object
 * @return array
 */
function oik_clone_get_comment_fields( $comment_object ) {
    $comment = array();
    $comment['comment_ID'] = $comment_object->comment_ID;
    $comment['comment_post_ID'] = $comment_object->comment_post_ID;
    $comment['comment_author'] = $comment_object->comment_author;
    $comment['comment_author_email'] = $comment_object->comment_author_email;
    $comment['comment_author_url'] = $comment_object->comment_author_url;
    $comment['comment_author_IP'] = $comment_object->comment_author_IP;
    $comment['comment_date'] = $comment_object->comment_date;
    $comment['comment_date_gmt'] = $comment_object->comment_date_gmt;
    $comment['comment_content'] = $comment_object->comment_content;
    $comment['comment_karma'] = $comment_object->comment_karma;
    $comment['comment_approved'] = $comment_object->comment_approved;
    $comment['comment_agent'] = $comment_object->comment_agent;
    $comment['comment_type'] = $comment_object->comment_type;
    $comment['comment_parent'] = $comment_object->comment_parent;
    $comment['user_id'] = $comment_object->user_id;
    return $comment;
}

/**
 * Updates the comments on the target post.
 *
 * Deletes all the comments then recreates.
 * Note: This logic should cater for:
 * - No comments field being passed
 * - Empty comments field
 * - Zero comments
 * - One or more comments - but not hierarchical comments
 *
 * @param $post
 */
function oik_clone_update_comments( $post, $target_id ) {
    if ( property_exists( $post, 'comments' ) && $post->comments && count( $post->comments ) ) {
        oik_clone_delete_comments( $target_id );
        oik_clone_insert_comments( $post, $target_id );
    }
}

/**
 * Deletes the existing comments associated with the post.
 *
 * @param $target_id
 */
function oik_clone_delete_comments( $target_id ) {
    $comment_objects = get_comments( [ 'post_id' => $target_id ]);
    foreach ( $comments_objects as $comment_object ) {
        wp_delete_comment( $comment_object->comment_ID, true );
    }
}

/**
 * Inserts new comments to the selected post.
 *
 * @param $post
 * @param $target_id
 */
function oik_clone_insert_comments( $post, $target_id ) {
    foreach ( $post->comments as $comment ) {
        $commentdata = oik_clone_get_comment_fields( $comment );
        $commentdata['comment_post_ID'] = $target_id;
        $new_id = wp_insert_comment( $commentdata );
        bw_trace2( $new_id, "New comment ID", false, BW_TRACE_DEBUG );
    }
}