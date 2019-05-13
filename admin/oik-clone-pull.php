<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 * @package oik-clone
 *
 */

/**
 * Performs a clone by pulling into the master from the selected slave.
 *
 * @param string $slave_url of the slave server
 * @param object $master_post - the post being updated
 * @param object $mapping - the slave mapping
 */
function oik_clone_master_pull( $slave_url, $master_post, $mapping ) {
	$url = "$slave_url/wp-admin/admin-ajax.php";
	$body = array( "action" => "oik_clone_pull",
				    "master" => get_site_url(),
				   "oik_apikey" => oik_clone_get_apikey(),
				   "id" => $mapping->slave
				);
	$args = array( "body" => $body
	, 'timeout' => 30
	);
	$result = oik_remote::bw_remote_post( $url, $args );
	bw_trace2( $result );

	$result = oik_remote::bw_json_decode( $result );
	//print_r( $result );

	$post = $result->payload;
	//print_r( $post );

	$_REQUEST['mapping'] = $result->mapping;
	$_REQUEST['master'] = $slave_url;
	if ( is_object( $post )) {
		oik_require( "admin/oik-clone-clone.php", "oik-clone" );
		//print_r( $mapping );
		$target_id = oik_clone_attempt_import( $mapping->slave, $master_post->ID, $post );
		// If the import worked the clone date becomes the modified time which which will be different from the slave'd clone date.
		// So we reset it. Next reconciliation will push the change back again.
		// @TODO Update the server's clone date rather than fiddle the date back to the original $master_post->post_modified_gmt ?
		if ( $target_id ) {
			oik_clone_update_slave_target( $target_id, $slave_url, $mapping->slave, $master_post->post_modified_gmt );
		}
	} else {
		$target_id = null;
		gob();
	}

	/** So now we need to update the slave to reflect the fact that the master has been reconciled with it.
	 * This is getting to be a lot harder than I first envisaged.
	 *
	 */

	return $target_id;
}

/**
 * Return the post to be pulled as the payload to import
 *
 * The request contains the ID of the server post to be pulled
 *
 * @return  array object $payload and object j
 */
function oik_clone_lazy_pull() {
	p( "Processing pull");
	$post_id = bw_array_get( $_REQUEST, "id", null );
	$master = bw_array_get( $_REQUEST, "master", null );
	p( "Master: $master");
	$payload = null;
	$jmapping = null;

	oik_require( "admin/oik-clone-actions.php", "oik-clone" );
	oik_require( "admin/oik-clone-relationships.php", "oik-clone" );
	add_filter( "oik_clone_build_list", "oik_clone_build_list_informal_relationships", 11, 2 );


	$payload = oik_clone_load_post( $post_id );
	$relationships = oik_clone_relationships( $payload );
	$mapping = $relationships->mapping( $master );
	$jmapping = json_encode( $mapping );


	return ["payload" => $payload, "mapping" => $jmapping ] ;
}


/**
 * Reply with a JSON message
 *
 * The requester is expecting something useful in the result; the 'slave' post ID
 * we also return any other rubbish created using bw_echo() as a field called 'narrative'
 *
 * This should make it easier for the client to check what it receives.
 * This logic doesn't handle fatal errors or other plain text / HTML that's been accidentally produced
 *
 * @param ID $target_id - the ID of the post created/updated
 */
function oik_clone_return_payload_with_json( $payload_relationships ) {
	$narrative = bw_ret();
	$result = array( "narrative" => $narrative
					, "payload" => $payload_relationships['payload']
					, "mapping" => $payload_relationships['mapping']
					);
	$json = json_encode( $result );
	e( $json );
	bw_trace2( $json, "json" );
}