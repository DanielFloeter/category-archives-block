<?php
/**
 * Tests for the features taken over from core/archives (issues #13, #15, #16).
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

	/**
	 * @dataProvider data_group_by
	 */
	public function test_group_by( $group_by, $expected_query, $expected_option ) {
		$this->set_permalink_structure( '' );

		$html = $this->render(
			array(
				'groupBy'           => $group_by,
				'displayAsDropdown' => true,
				'categories'        => $this->news(),
			)
		);

		$this->assertStringContainsString( '>' . $expected_option . '</option>', $html );
		$this->assertStringContainsString( $expected_query, $html );
		$this->assertStringContainsString( 'cat=' . self::$news, $html );
	}

	public function data_group_by() {
		return array(
			'daily'   => array( 'daily', '?m=20210115', 'Select Day' ),
			'weekly'  => array( 'weekly', 'w=', 'Select Week' ),
			'unknown' => array( 'postbypost', '?m=202101', 'Select Month' ),
		);
	}

	public function test_missing_attributes_fall_back_to_monthly_desc() {
		$this->set_permalink_structure( '' );

		$html = $this->render( array() );

		$this->assertMatchesRegularExpression( '/m=202101.*m=202012/s', $html );
	}

	public function test_order_asc() {
		$this->set_permalink_structure( '' );

		$html = $this->render( array( 'order' => 'asc' ) );

		$this->assertMatchesRegularExpression( '/m=202012.*m=202101/s', $html );
	}

	public function test_day_title() {
		$this->go_to( add_query_arg( array( 'm' => '20210115', 'cab_date_title' => 1 ), home_url( '/' ) ) );

		$this->assertTrue( is_day() );
		$this->assertSame( 'Day: <span>January 15, 2021</span>', get_the_archive_title() );
	}

	/**
	 * Weekly links also carry the year, so is_year() is true (issue #18).
	 */
	public function test_week_title() {
		update_option( 'start_of_week', 1 );
		update_option( 'date_format', 'F j, Y' );

		$this->go_to( add_query_arg( array( 'm' => '2021', 'w' => '2', 'cab_date_title' => 1 ), home_url( '/' ) ) );

		$this->assertTrue( is_year() );
		$this->assertSame( 'Week: <span>January 11, 2021 &#8211; January 17, 2021</span>', get_the_archive_title() );
	}

	public function test_week_without_date_title_keeps_core_title() {
		$this->go_to( add_query_arg( array( 'm' => '2021', 'w' => '2' ), home_url( '/' ) ) );

		$this->assertSame( 'Year: <span>2021</span>', get_the_archive_title() );
	}

	public function test_year_title_is_unchanged() {
		$this->go_to( add_query_arg( array( 'm' => '2021', 'cab_date_title' => 1 ), home_url( '/' ) ) );

		$this->assertSame( 'Year: <span>2021</span>', get_the_archive_title() );
	}
}
