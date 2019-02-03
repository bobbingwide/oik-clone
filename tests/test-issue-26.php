<?php // (C) Copyright Bobbing Wide 2019

class Tests_issue_26 extends BW_UnitTestCase {

	/**
	 * Tests that the source URL is converted to the target URL
	 *
	 * With the scheme being maintained?
	 * Tests should work on shortcodes like this
	 * [bw_link example.com]
	 * [bw_link //example.com]
	 * [bw_link http://example.com]
	 * [bw_link https://example.com]
	 * [bw_link example.com/site ]
	 *
	 * and normal links
	 *
	 * <a href="http://example.com">Example</a>
	 *
	 */

	/**
	 * set up logic
	 *
	 * - ensure any database updates are rolled back
	 */
	function setUp() {
		parent::setUp();
		oik_require( "admin/oik-clone-relationships.php", "oik-clone");
	}

	function test_oik_clone_get_schemeless() {
		$schemeless = oik_clone_get_schemeless( "https://example.com");
		$this->assertEquals( $schemeless, "example.com" );
		$schemeless = oik_clone_get_schemeless( "https://s.b/wordpress");
		$this->assertEquals( $schemeless, "s.b/wordpress" );
	}

	function test_oik_clone_get_master_schemeless() {
		$_REQUEST[ 'master'] = "http://example.com";
		$master = oik_clone_get_master_schemeless();
		$this->assertEquals( "example.com", $master );
		$_REQUEST[ 'master'] = "https://example.com";
		$master = oik_clone_get_master_schemeless();
		$this->assertEquals( "example.com", $master );
		unset( $_REQUEST[ 'master'] );
	}

	/**
	 * This basically tests the same logic!
	 */
	function test_oik_clone_get_target_schemeless() {
		$expected = $this->get_target();
		$target_schemeless = oik_clone_get_target_schemeless();
		$this->assertEquals( $expected, $target_schemeless );
	}

	/**
	 * Tests that the source URL is converted to the target URL
	 * With the scheme being maintained?
	 *
	 * Tests should work on URLs inside shortcodes like this
	 * `
	 * [bw_link example.com]
	 * [bw_link //example.com]
	 * [bw_link http://example.com]
	 * [bw_link https://example.com]
	 * [bw_link example.com/site ]
	 * `
	 * and normal links
	 * `
	 * <a href="http://example.com">Example</a>
	 * <a href="https://example.com">Example</a>
	 *
	 * Note: This test doesn't confirm that post_excerpt is converted separately from post_content.
	 * But we trust that to be the case for now.
	 */
	function test_oik_clone_apply_informal_relationship_mapping_urls() {
		$_REQUEST[ 'master'] = "http://example.com";
		$target = $this->get_target();
		$target_ids = [];
		$post = new StdClass();
		$tests = array( [ "blah", "blah"]
			, [ "[bw_link example.com]", "[bw_link $target]" ]
			, [ "[bw_link //example.com]", "[bw_link //$target]" ]
			, [ "[bw_link http://example.com]", "[bw_link http://$target]" ]
			, [ "[bw_link https://example.com]", "[bw_link https://$target]" ]
			, [ "[bw_link example.com/site ]", "[bw_link $target/site ]" ]
			, [ "<a href=\"http://example.com\">Example</a>", "<a href=\"http://$target\">Example</a>" ]
			, [ "<a href=\"https://example.com\">Example</a>", "<a href=\"https://$target\">Example</a>" ]
		);
		foreach ( $tests as $test ) {
			$post->post_content = $test[0];
			$post->post_excerpt = $post->post_content;
			$post = oik_clone_apply_informal_relationship_mapping_urls( $post, $target_ids );
			$this->assertEquals( $test[1], $post->post_content );
			$this->assertEquals( $test[1], $post->post_excerpt );
		}
		unset( $_REQUEST['master']);
	}


	function get_target() {
		$target = site_url();
		$scheme = parse_url( $target, PHP_URL_SCHEME );
		$target = str_replace( $scheme . "://", "", $target );
		return $target;

	}





}
