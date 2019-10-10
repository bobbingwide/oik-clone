<?php // (C) Copyright Bobbing Wide 2019

class Tests_issue_38 extends BW_UnitTestCase {

	/**
	 * Tests that we can find IDs from WordPress block's attributes
	 * Does this by direct invocation of the filter function attached to 'oik_clone_build_list'
	 *
	 * Then tests that we can map them to target IDs in the slave
	 * and reform the blocks back into the original content.
	 *
	 * It should be possible to append the test data sets to each other in a variety of ways.
	 *
	 */

	/**
	 * set up logic
	 *
	 * - ensure any database updates are rolled back
	 */
	function setUp() : void {
		parent::setUp();
		oik_require( 'admin/class-oik-clone-block-relationships.php', 'oik-clone' );
	}

	/**
	 * We need an example block with an ID, productID attribute
	 *
	 * How do I load test input from a file? tests\data ?
	 */
	function get_test1() {
		$test1 = '<!-- wp:cover {"url":"https://core.wp-a2z.org/wp-content/uploads/sites/2/2016/07/wordpress-core-icon-256x256.jpg","id":15086,"className":"aligncenter"} -->
	<div class="wp-block-cover has-background-dim aligncenter" style="background-image:url(https://core.wp-a2z.org/wp-content/uploads/sites/2/2016/07/wordpress-core-icon-256x256.jpg)"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
	<p class="has-text-align-center has-large-font-size">WordPress core</p>
	<!-- /wp:paragraph --></div></div>
	<!-- /wp:cover -->';
		return $test1;
	}

	/**
	 * Returns a classic block.
	 * @return string
	 */
	function get_test2() {
		$test2 = 'A classic block doesn\'t need any HTML at all. ';
		$test2 .= '<p>But we can have some. And [shortcodes] too</p>';
		return $test2;

	}

	/**
	 * Returns a block with no attributes
	 */
	function get_test3() {
		$test3 = '<!-- wp:paragraph -->
<p>This is a block that has no attributes.</p>
<!-- /wp:paragraph -->';
		return $test3;
	}


	/**
	 * Here's an example of the structure of the block.
	 *
	 * Array
	(
	[blockName] => core/cover
	[attrs] => Array
	(
	[url] => https://core.wp-a2z.org/wp-content/uploads/sites/2/2016/07/wordpress-core-icon-256x256.jpg
	[id] => 15086
	[className] => aligncenter
	)

	[innerBlocks] => Array
	(
	[0] => Array
	(
	[blockName] => core/paragraph
	[attrs] => Array
	(
	[align] => center
	[placeholder] => Write title…
	[fontSize] => large
	)

	[innerBlocks] => Array
	(
	)

	[innerHTML] =>
	<p class="has-text-align-center has-large-font-size">WordPress core</p>

	[innerContent] => Array
	(
	[0] =>
	<p class="has-text-align-center has-large-font-size">WordPress core</p>

	)

	)

	)

	[innerHTML] =>
	<div class="wp-block-cover has-background-dim aligncenter" style="background-image:url(https://core.wp-a2z.org/wp-content/uploads/sites/2/2016/07/wordpress-core-icon-256x256.jpg)"><div class="wp-block-cover__inner-container"></div></div>

	[innerContent] => Array
	(
	[0] =>
	<div class="wp-block-cover has-background-dim aligncenter" style="background-image:url(https://core.wp-a2z.org/wp-content/uploads/sites/2/2016/07/wordpress-core-icon-256x256.jpg)"><div class="wp-block-cover__inner-container">
	[1] =>
	[2] => </div></div>

	)

	)
	 */

	function test_parse_and_reform() {
		$test1 = $this->get_test1();
		$test2 = $this->get_test2();
		$test3 = $this->get_test3();
		$tests = array( $test1, $test2, $test3 );
		foreach ( $tests as $content ) {
			$oik_clone_block_relationships = new OIK_clone_block_relationships();
			$blocks                        = $oik_clone_block_relationships->parse_blocks( $content );
			//print_r( $blocks );
			$reformed = $oik_clone_block_relationships->reform_blocks( $blocks );
			//echo "Start:" . PHP_EOL;
			//echo $reformed;
			//echo PHP_EOL;
			//echo "End";
			$this->assertEquals( $content, $reformed );
		}
	}

	function test_filter_block_attributes() {

		$oik_clone_block_relationships = new OIK_clone_block_relationships();

		$post = new stdClass;
		$test1 = $this->get_test1();
		$test2 = $this->get_test2();
		$test3 = $this->get_test3();

		$tests = array( [ "blah", []]
		, [ $test1, [15086] ]
		, [ $test2, [] ]
		, [ $test3, [] ]
		);
		foreach ( $tests as $test ) {
			$IDs = [];
			$post->post_content = $test[0];
			$IDs = $oik_clone_block_relationships->filter_block_attributes( $IDs, $post );
			$this->assertEquals( $IDs, $test[1] );
		}
	}

	function test_mapping() {
		$oik_clone_block_relationships = new OIK_clone_block_relationships();
		$post = new stdClass;
		$test1 = $this->get_test1();
		$test2 = $this->get_test2();
		$test3 = $this->get_test3();

		$tests = array( [ "blah", [], [] ]
		, [ $test1, [15086], [68051] ]
		, [ $test2, [], [] ]
		, [ $test3, [], [] ]
		);
		$mapping = array( '15086' => array( 'id' => 68051, 'cloned' => 1570724987 ) );
		foreach ( $tests as $test ) {
			$IDs = [];
			$post->post_content = $test[0];
			$IDs = $oik_clone_block_relationships->filter_block_attributes( $IDs, $post );
			$this->assertEquals( $IDs, $test[1] );
			$oik_clone_block_relationships->apply_mapping( $mapping );
			$mapped_IDs = $oik_clone_block_relationships->find_IDs();
			//print_r( $mapped_IDs );
			$this->assertEquals( $mapped_IDs, $test[2]);

		}



	}

	/**
	 * Test the parse map and reform
	 * on a round trip confirming that we find the IDs after the mapping
	 */

	function test_parse_map_reform() {

		$oik_clone_block_relationships = new OIK_clone_block_relationships();

		$post = new stdClass;
		$test1 = $this->get_test1();
		$test2 = $this->get_test2();
		$test3 = $this->get_test3();

		$tests = array( [ "blah", [], [] ]
		, [ $test1, [15086], [68051] ]
		, [ $test2, [], [] ]
		, [ $test3, [], [] ]
		);

		$mapping = array( '15086' => array( 'id' => 68051, 'cloned' => 1570724987 ) );
		$reverse_mapping = array( '68051' => array( 'id' => 15086, 'cloned' => 'ignored') );

		foreach ( $tests as $test ) {

			$post->post_content = $test[0];
			$IDs = [];
			$IDs = $oik_clone_block_relationships->filter_block_attributes( $IDs, $post );
			$this->assertEquals( $IDs, $test[1] );
			$oik_clone_block_relationships->apply_mapping( $mapping );
			$mapped_IDs = $oik_clone_block_relationships->find_IDs();
			//print_r( $mapped_IDs );
			$this->assertEquals( $mapped_IDs, $test[2]);

			$target_content = $oik_clone_block_relationships->reform_blocks();
			$post->post_content = $target_content;
			$IDs = [];
			$IDs = $oik_clone_block_relationships->filter_block_attributes( $IDs, $post );
			$oik_clone_block_relationships->apply_mapping( $reverse_mapping );
			$reverse_mapped_IDs = $oik_clone_block_relationships->find_IDs();
			$this->assertEquals( $reverse_mapped_IDs, $test[1] );

			$round_trip_content = $oik_clone_block_relationships->reform_blocks();

			$this->assertEquals( $test[0], $round_trip_content );

		}


	}




}