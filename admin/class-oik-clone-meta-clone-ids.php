<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 */

/**
 * Class OIK_clone_meta_clone_ids
 * New logic for the post meta data _oik_clone_ids
 *
 * This class will contain the methods to replace the functions in the original code.
 * This logic does not deal with _oik_clone_dnc, it's in a separate file.
 */

class OIK_clone_meta_clone_ids {

	public $post_id = null;
	public $clones;
	function __construct() {
		$this->set_post_id( null );
	}

	function set_post_id( $ID ) {
		$this->post_id = $ID;
	}

	public function get_post_meta( $ID ) {
		$this->set_post_id( $ID );
		$clones = get_post_meta( $ID, "_oik_clone_ids", false );
		bw_trace2( $clones, "Clones" );
		return $clones;
	}

	public function update_post_meta( $cloned ) {
		update_post_meta( $this->post_id, '_oik_clone_ids', $cloned );
	}

	/**
	 * Gets the clone info
	 *
	 * Enhances the raw post meta data
	 * @param $ID
	 */
	public function get_clone_info( $ID ) {
		$this->set_post_id( $ID );
		$clones = $this->get_post_meta( $ID );
		$cloned = $this->reduce_from_serialized( $clones );
		return $cloned;
	}

	/**
	 * Reduce a serialised array to a simpler version
	 *
	 * @param array $serialized
	 * @return array reduced array
	 */
	static function reduce_from_serialized( $serialized ) {
		bw_trace2();
		$reduced = array();
		foreach ( $serialized as $serial ) {
			foreach ( $serial as $key => $value ) {
				$reduced[ $key ] = $value;
			}
		}
		bw_trace2( $reduced, "reduced", true ); //, BW_TRACE_DEBUG );
		return $reduced;
	}

	/**
	 * Display the checkboxes for cloning
	 *
	 * For each slave
	 * - see if it's been cloned
	 * - if so, create cb with link, and remove from the clone array
	 * - if not, just create the cb
	 *
	 * For each remaining clone
	 * - create cb with link
	 */
	function display_cbs( $slaves ) {
		$cloned = $this->get_clone_info( $this->post_id );
		bw_trace2( null, null, true, BW_TRACE_DEBUG );
		foreach ( $slaves as $key => $slave ) {
			$cloned_id   = oik_clone_get_slave_id( $cloned, $slave );
			$cloned_date = oik_clone_get_slave_cloned( $cloned, $slave );

			unset( $cloned[ $slave ] );
			oik_clone_display_cb( $slave, $cloned_id, $cloned_date );
		}
		if ( count( $cloned ) ) {
			echo "<br />Previously cloned";
			foreach ( $cloned as $slave => $cloned_item ) {
				$cloned_id   = oik_clone_get_slave_id( $cloned, $slave );
				$cloned_date = oik_clone_get_slave_cloned( $cloned, $slave );
				oik_clone_display_cb( $slave, $cloned_id, $cloned_date );
			}
		}
	}
}