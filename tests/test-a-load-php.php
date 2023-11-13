<?php

/**
 * @package oik-clone
 * @copyright (C) Copyright Bobbing Wide 2023
 *
 * Unit tests to load all the files for PHP 8.2, except batch ones
 */

class Tests_load_libs extends BW_UnitTestCase
{

	/**
	 * set up logic
	 *
	 * - ensure any database updates are rolled back
	 * - we need oik-googlemap to load the functions we're testing
	 */
	function setUp(): void
	{
		parent::setUp();

	}

	function test_load_admin_php() {
		oik_require( "admin/class-bw-list-table.php" );

		$files = glob( 'admin/*.php');
		//print_r( $files );

		foreach ( $files as $file ) {
			switch ( $file ) {
				case 'admin/oik-clone-pull.php':
				case 'admin/oik-clone-push.php':
				case 'admin/oik-clone-reconcile-batch.php':
				case 'admin/oik-clone-reset-ids.php':
				case 'admin/oik-clone-reset-slave.php':
				case 'admin/oik-clone-wp-api.php':
					break;

				default:
					oik_require( $file, 'oik-clone');
			}

		}
		$this->assertTrue( true );


	}

	function test_load_includes() {
		$exclusions = [ ];
		$this->load_dir_files( 'includes', $exclusions );
		$this->assertTrue( true );

	}

	function test_load_shortcodes() {
		$exclusions = [ ];
		$this->load_dir_files( 'shortcodes', $exclusions );
		$this->assertTrue( true );

	}


	function load_dir_files( $dir, $excludes=[] ) {
		$files = glob( "$dir/*.php");
		//print_r( $files );

		foreach ( $files as $file ) {
			if ( !in_array( $file, $excludes ) ) {
				oik_require( $file, 'oik-clone');
			}
			//oik_require( $file, 'oik-clone');
		}
	}

	function test_load_plugin() {
		oik_require( 'oik-clone.php', 'oik-clone');
		$this->assertTrue( true );

	}

}