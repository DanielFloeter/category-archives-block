<?php
/**
 * Tests for the block wrapper markup (issue #11).
 *
 * Every output variant must carry the block wrapper attributes, so theme
 * styles and block supports (custom class name etc.) apply consistently.
 *
 * @package tiptip
 */

class Test_Block_Wrapper extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		$factory->post->create( array( 'post_date' => '2021-01-15 10:00:00' ) );
	}

	/**
	 * Renders the block through the parser, like on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function render( $attributes ) {
		return do_blocks( '<!-- wp:tiptip/category-archives-block ' . wp_json_encode( (object) $attributes ) . ' /-->' );
	}

	/**
	 * Returns the class attribute of the outermost element.
	 *
	 * @param string $html Rendered block.
	 * @return string
	 */
	private function wrapper_class( $html ) {
		$this->assertMatchesRegularExpression( '/^\s*<(?:div|ul)\b[^>]*\bclass="([^"]*)"/', $html );
		preg_match( '/^\s*<(?:div|ul)\b[^>]*\bclass="([^"]*)"/', $html, $matches );
		return $matches[1];
	}

	/**
	 * @dataProvider data_variants
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $own_class  The variant-specific class.
	 */
	public function test_wrapper_has_block_class( $attributes, $own_class ) {
		$class = $this->wrapper_class( $this->render( $attributes ) );

		$this->assertSame( $class, trim( $class ), 'Class attribute has surrounding whitespace.' );
		$classes = explode( ' ', $class );
		$this->assertContains( 'wp-block-tiptip-category-archives-block', $classes );
		$this->assertContains( $own_class, $classes );
	}

	public function data_variants() {
		return array(
			'list'        => array( array(), 'category-archives-block-list' ),
			'dropdown'    => array( array( 'displayAsDropdown' => true ), 'category-archives-block-dropdown' ),
			'empty state' => array(
				array(
					'categories' => array(
						array(
							'id'   => 999999,
							'name' => 'Gone',
						),
					),
				),
				'category-archives-block-list',
			),
		);
	}

	public function test_dropdown_keeps_custom_class_name() {
		$class = $this->wrapper_class(
			$this->render(
				array(
					'displayAsDropdown' => true,
					'className'         => 'my-archive',
				)
			)
		);

		$this->assertContains( 'my-archive', explode( ' ', $class ) );
	}
}
