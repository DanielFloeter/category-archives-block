<?php
/**
 * Tests for the features taken over from core/archives (issue #13).
 *
 * @package tiptip
 */

class Test_Core_Parity extends WP_UnitTestCase {

	const BLOCK = 'tiptip/category-archives-block';

	/** @var int */
	private static $news;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$news = $factory->category->create( array( 'name' => 'News' ) );

		$factory->post->create(
			array(
				'post_date'     => '2021-01-15 10:00:00',
				'post_category' => array( self::$news ),
			)
		);
		$factory->post->create(
			array(
				'post_date'     => '2020-12-14 10:00:00',
				'post_category' => array( self::$news ),
			)
		);
	}

	private function render( array $attrs ) {
		return render_block(
			array(
				'blockName'    => self::BLOCK,
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	private function news() {
		return array(
			array(
				'id'   => self::$news,
				'name' => 'News',
			),
		);
	}

	public function test_dropdown_has_no_inline_handler_but_a_script() {
		$html = $this->render( array( 'displayAsDropdown' => true ) );

		$this->assertStringNotContainsString( 'onchange', $html );
		$this->assertMatchesRegularExpression( '/<select id="([^"]+)"/', $html );
		preg_match( '/<select id="([^"]+)"/', $html, $matches );
		$this->assertStringContainsString( '<script', $html );
		$this->assertStringContainsString( wp_json_encode( $matches[1] ), $html );
		$this->assertStringContainsString( "'escape' === dropdown.dataset.lastkey", $html );
	}

	public function test_dropdown_ids_are_unique() {
		preg_match( '/<select id="([^"]+)"/', $this->render( array( 'displayAsDropdown' => true ) ), $first );
		preg_match( '/<select id="([^"]+)"/', $this->render( array( 'displayAsDropdown' => true ) ), $second );

		$this->assertNotSame( $first[1], $second[1] );
		$this->assertStringStartsWith( 'category-archives-block-', $first[1] );
	}

	public function test_label_is_hidden_by_default() {
		$html = $this->render( array( 'displayAsDropdown' => true ) );

		$this->assertStringContainsString( 'class="category-archives-block__label screen-reader-text"', $html );
	}

	public function test_label_can_be_shown() {
		$html = $this->render(
			array(
				'displayAsDropdown' => true,
				'showLabel'         => true,
			)
		);

		$this->assertStringContainsString( 'class="category-archives-block__label"', $html );
	}
}
