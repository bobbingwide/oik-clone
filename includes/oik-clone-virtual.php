<?php // (C) Copyright Bobbing Wide 2015

/**
 * 
 * 
 */
function oik_clone_cloned( $args ) {
	bw_trace2();
	bw_backtrace();

	//p( "wahey $args" );
	oik_require( "shortcodes/oik-cloned.php", "oik-clone" );
	$result = oik_cloned( array( "label" => false ) );
	
	
	return( $result );
}
