<?php // (C) Copyright Bobbing Wide 2016

if ( PHP_SAPI !== "cli" ) { 
	die();
}

/**
 * Class: OIK_clone_reset_ids
 * 
 * 
 * To reset the cloned target IDs from a slave server
 * Note: We don't expect there to be much in common with OIK_clone_reset_slave
 * so we're not presently inheriting from a common class.
 * Some methods may look similar.
 */
class OIK_clone_reset_ids {

	public $slave; 
	public $apikey;
	public $post_type;
	public $mapping;

	/**
	 * Constructor for OIK_clone_reset_ids
	 * 
	 * Controls the resetting of the _oik_clone_id
	 */
	function __construct() {
		oik_require( "includes/bw_posts.inc" );
		$this->get_slave();
		gob();
		$this->process_post_types();
		
		$this->get_slave();
		
		
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
			echo "Syntax oikwp oik-clone-reset-ids.php slave" . PHP_EOL ;
			echo "e.g. oikwp oik-clone-reset-ids.php http://oik-plugins.co.uk" . PHP_EOL;
			die( "try again with the right parameters");
		}
		$this->slave = $slave;
	}
	
	/**
	 * 
	 */
	function request_ids() {
		
	}


}
