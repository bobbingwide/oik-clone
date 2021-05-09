<?php
/**
* @copyright (C) Copyright Bobbing Wide 2019
*/

/**
 * Updates the slave's target date.
 *
 * @param $master_id
 * @param $slave_url
 * @param $slave_id
 * @param $post_modified_gmt
 */
function oik_clone_update_slave_target_date( $master_id, $slave_url, $slave_id, $post_modified_gmt ) {
	$url = "$slave_url/wp-admin/admin-ajax.php";
	$body = array( "action" => "oik_clone_update_slave_target",
					"master" => get_site_url(),
					"oik_apikey" => oik_clone_get_apikey(),
					"master_id" => $master_id,
					"slave_id" => $slave_id,
					"modified" => $post_modified_gmt
			);
	$args = array( "body" => $body
				, 'timeout' => 30
				);
	$result = oik_remote::bw_remote_post( $url, $args );
	bw_trace2( $result );

	$result = oik_remote::bw_json_decode( $result );
	//print_r( $result );

}

function oik_clone_lazy_update_slave_target_date() {
	$slave_id = bw_array_get( $_REQUEST, 'slave_id', null );
	$master = bw_array_get( $_REQUEST, 'master', null );
	$master_id = bw_array_get( $_REQUEST, 'master_id', null );
	$modified = bw_array_get( $_REQUEST, 'modified', null );
	oik_require( "admin/oik-save-post.php", "oik-clone" );
	$post_meta = oik_clone_update_slave_target( $slave_id, $master, $master_id, $modified );



}
