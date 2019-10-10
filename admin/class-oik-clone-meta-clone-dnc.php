<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 */

/**
 * Class OIK_clone_meta_clone_dnc
 * New logic for the post meta data _oik_clone_dnc
 *
 * The post meta fields are being extended to include a Do Not Clone ( _oik_clone_dnc ) field.
 * This is a new field which will not exist until a slave is actually marked as Do Not Clone.
 * Values are:
 * - null - same as Yes
 * - Yes=on - it's not to be cloned.
 * - No=0 - it can be cloned.
 *
 * This class will contain the methods to enhance the logic in the original code.
 * Two sets of post meta data will control the cloning.
 * _oik_clone_ids records the status of cloning that's been performed
 * _oik_clone_dnc will indicate whether or not a particular slave is included in the cloning process.
 * So there should be an 'is this to be cloned' method for the given post ID and slave?
 */

class OIK_clone_meta_clone_dnc {

	public $post_id = null;
	public $dncs; // Do Not Clones array for selected post

	function __construct() {
		$this->set_post_id( null );
	}

	function set_post_id( $ID ) {
		$this->post_id = $ID;
	}

	public function get_post_meta( $ID ) {
		$this->set_post_id( $ID );
		$dncs = get_post_meta( $ID, "_oik_clone_dnc", false );
		bw_trace2( $dncs, "Do Not Clones" );
		return $dncs;
	}

	public function update_post_meta( $oik_clone_dnc ) {
		bw_trace2();
		$meta_id = update_post_meta( $this->post_id, '_oik_clone_dnc', $oik_clone_dnc );
		bw_trace2( $meta_id, 'meta_id', false );
	}

	/**
	 * Gets the do not clone info
	 *
	 * Enhances the raw post meta data
	 * @param $ID
	 */

	public function get_dnc_info( $ID ) {
		$this->set_post_id( $ID );
		$oik_clone_dnc = $this->get_post_meta( $ID );
		//print_r( $oik_clone_dnc );
		$oik_clone_dnc = $this->reduce_from_serialized( $oik_clone_dnc );
		$this->dncs = $oik_clone_dnc;
		return $oik_clone_dnc;

	}

	/**
	 * Reduce a serialised array to a simpler version
	 *
	 * @TODO Investigate why we were having problems with serialzed post meta data being cloned
	 * but not being correctly stored on the slave. Appearing as a string that produces warnings
	 * when we tried to run this method.
	 *
	 * @param array $serialized
	 * @return array reduced array
	 */
	static function reduce_from_serialized( $serialized ) {
		bw_trace2();
		$reduced = array();
		if ( $serialized && is_array( $serialized ) ) {
			foreach ( $serialized as $serial ) {
				foreach ( $serial as $key => $value ) {
					$reduced[ $key ] = $value;
				}
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
	function display_cbs( $ID, $slaves ) {
		//$dncs = $this->get_dnc_info( $this->post_id );
		//gob();
		//$cloned = $this->reduce_from_serialized( $clones );
		//bw_trace2( null, null, true, BW_TRACE_DEBUG );
		//$cloned = oik_reduce_from_serialized(  $clones );
		foreach ( $slaves as $key =>  $slave ) {
			$cloned_dnc = $this->is_slave_dnc( $slave );
			oik_clone_display_dnc( $slave, $cloned_dnc );
		}


	}


	/**
	 * @param $dncs
	 * @param $slave
	 *
	 * @return bool
	 */

	function is_slave_dnc( $slave ) {
		$dncs = $this->dncs;
		//print_r( $dncs );
		//echo $slave;
		$flipped = array_flip( $dncs );
		$dnc = array_key_exists( $slave, $flipped );

		return $dnc;
	}




	/**
	 * Sets the Do Not Clone fields in the post meta data
	 * for each of the existing entries
	 * to the value passed in the dnc field, which is an array keyed on the slave URL
	 * ```
	 * [https://s.b/wp52] => on
	 * [https://s.b/wp53] => 0
	 * ```
	 *
	 * We also need to set entries for each $dnc where the slave's value is 'on' but no entry yet exists.
	 */

	function set_dncs( $ID ) {
		$this->set_post_id( $ID );
		$dncs = bw_array_get( $_REQUEST, 'dnc', null );
		bw_trace2( $dncs, 'dnc');
		$oik_clone_dnc = array();
		foreach ( $dncs as $slave => $dnc ) {
			if ( $dnc == 'on' ) {
				//$oik_clone_dnc[] = array( $slave  => array( 'dnc' => $dnc ));
				$oik_clone_dnc[] = $slave;

			}
		}

		bw_trace2( $oik_clone_dnc, 'oik_clone_dnc' );

		$this->update_post_meta( $oik_clone_dnc );
	}


}