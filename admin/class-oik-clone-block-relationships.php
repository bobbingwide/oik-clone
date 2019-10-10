<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * Manage post relationships in blocks.
 * We need to be able to identify post IDs that have to be mapped to the correct target post ID
 * and then, when the mapping has been found apply that mapping.
 */

class OIK_clone_block_relationships {

	private $blocks; /* Parsed content */
	private $IDs; /* Array of related IDs */

	function __construct() {
		$this->blocks = null;
		$this->IDs = [];
	}

	/**
	 * Parses the content using the core block parser
	 *
	 * @param $content
	 * @return array|null
	 */
	function parse_blocks( $content ) {
		$this->blocks = parse_blocks( $content );
		bw_trace2( $this->blocks, 'blocks' );
		return $this->blocks;
	}

	function filter_block_attributes( $source_IDs, $post ) {
		$this->IDs = $source_IDs;
		$content = $post->post_content;
		$blocks = $this->parse_blocks( $content );
		$this->find_IDs();
		$this->IDs = array_unique( $this->IDs );
		sort( $this->IDs );
		bw_trace2( $this->IDs, "IDs", false, BW_TRACE_DEBUG );
		return $this->IDs;
	}

	function find_ids() {
		$this->IDs = [];
		$blocks = $this->blocks;
		foreach ( $blocks as $key => $block ) {
			$this->find_blocks_ids( $block );
		}
		return $this->IDs;
	}

	function find_blocks_ids( $block ) {
		$this->find_attrs_ids( $block );
		foreach ( $block['innerBlocks'] as $innerBlock ) {
			$this->find_blocks_ids( $innerBlock );
		}
	}

	/**
	 * Finds the attributes which are post IDs
	 *
	 * A very quick and dirty routine to find post IDs.
	 * It assumes that any value that's numeric is a post ID
	 *
	 * @TODO Cater for CSV separated IDs
	 *
	 * @TODO Check the key name is in a defined set.
	 *
	 * @param $block
	 */
	function find_attrs_ids( $block ) {
		foreach ( $block['attrs'] as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$this->add_id( $value );
			}
		}
	}

	function add_id( $ID ) {
		$this->IDs[] = $ID;
	}

	/**
	 * The do_blocks function doesn't reform the blocks to be the same as the source.
	 * What method can we use to rebuild them?
	 *
	 * <!-- wp:cover {"url":"https://blocks.wp.a2z/wp-content/uploads/sites/10/2019/02/B7A4A34B-4393-4327-813B-C9CECF166F0D.jpeg","id":614,"className":"aligncenter"} -->
	<div class="wp-block-cover has-background-dim aligncenter" style="background-image:url(https://blocks.wp.a2z/wp-content/uploads/sites/10/2019/02/B7A4A34B-4393-4327-813B-C9CECF166F0D.jpeg)"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
	<p class="has-text-align-center has-large-font-size">WordPress core</p>
	<!-- /wp:paragraph --></div></div>
	<!-- /wp:cover -->
	 */
	function reform_blocks( $blocks=null ) {
		$blocks = $blocks ?? $this->blocks;
		$output = '';
		foreach ( $blocks as $block ) {
			$output .= $this->reform_block( $block );
		}
		return $output;
	}

	function reform_block( $block=null ) {
		//print_r( $block );

		$block_content = '';
		$index         = 0;
		$block_content .= $this->reform_html_comment( $block );

		foreach ( $block['innerContent'] as $chunk ) {
			$block_content .= is_string( $chunk ) ? $chunk : $this->reform_block( $block['innerBlocks'][ $index ++ ] );
		}

		$block_content .= $this->end_html_comment( $block );
		return $block_content;
	}

	function blockName( $block ) {
		$blockName = $block['blockName'];
		$blockName = str_replace( 'core/', '', $blockName );
		return $blockName;
	}

	function reform_html_comment( $block ) {
		$output = null;
		if ( isset( $block['blockName'])) {
			$output .= '<!-- wp:';
			$output .= $this->blockName( $block );
			if ( isset( $block['attrs'] ) && count( $block['attrs' ] ) ) {
				$output .= ' ';
				$output .= json_encode( $block['attrs'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

			}
			//$output .= ' {';
			//$output .= $this->reform_attrs( $block['attrs'] );
			$output .= ' -->';
		}
		return $output;
	}

	function end_html_comment( $block ) {
		$output = null;
		if ( isset( $block['blockName'])) {
			$output .= '<!-- /wp:';
			$output .= $this->blockName( $block );
			$output .= ' -->';
		}
		return $output;
	}

	/**
	 * Applies the mapping between post IDs in all the blocks' attributes
	 * @param $mapping
	 */
	function apply_mapping( $mapping ) {
		$this->save_mapping( $mapping );
		$this->map_ids();

	}

	/**
	 * Simplifies the mapping to an associative array of source_ID to target_ID
	 * @param $mapping
	 */
	function save_mapping( $mapping ) {
		$this->mapping = [];
		foreach ( $mapping as $source_ID => $target ) {
			$target_ID = bw_array_get( $target, 'id', null );
			if ( $target_ID ) {
				$this->mapping[ $source_ID ] = $target_ID;
			}
		}
		//print_r( $this->mapping );
	}

	function get_target( $source_ID ) {
		//echo "Getting: " . $source_ID;
		$target_ID = bw_array_get( $this->mapping, $source_ID, $source_ID );
		//echo "Got: " . $target_ID;
		return $target_ID;
	}

	/**
	 * Map each attr value from the source_ID to target_ID
	 *
	 * We should only touch each attr once.
	 * @TODO Cater for attrs which are CSVs of IDs
	 *
	 * @return array
	 */
	function map_ids() {
		//$blocks = $this->blocks;
		foreach ( $this->blocks as $key => $block ) {
			$this->map_blocks_ids( $this->blocks[ $key ] );
		}
		//echo "Blocks";
		//print_r( $blocks );
		//echo "This blocks";
		//print_r( $this->blocks );
	}

	function map_blocks_ids( &$block ) {
		$this->map_attrs_ids( $block );
		foreach ( $block['innerBlocks'] as $key => $innerBlock ) {
			$this->map_blocks_ids( $block['innerBlocks'][$key] );
		}
	}

	/**
     * Maps the attrs which contain post IDs
     *
     * A very quick and dirty routine to map post IDs.
     * It assumes that any value that's numeric is a post ID
	 *
     * @TODO See find_attrs_ids for TODO's to do
     * @param $block
     */
	function map_attrs_ids( &$block ) {
		foreach ( $block['attrs'] as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$block['attrs'][ $key ] = $this->get_target( $value );
			}
		}
		//print_r( $block );
	}

}