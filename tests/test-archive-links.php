<?php
/**
 * Tests for the archive links and post counts (issue #9).
 *
 * @package tiptip
 */

class Test_Archive_Links extends WP_UnitTestCase {

	const BLOCK = 'tiptip/category-archives-block';

	/** @var int */
	private static $news;

	/** @var int */
	private static $events;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$news   = $factory->category->create( array( 'name' => 'News' ) );
		self::$events = $factory->category->create( array( 'name' => 'Events' ) );

		$factory->post->create(
			array(
				'post_date'     => '2021-01-15 10:00:00',
				'post_category' => array( self::$news ),
			)
		);
		$factory->post->create(
			array(
				'post_date'     => '2020-12-15 10:00:00',
				'post_category' => array( self::$events ),
			)
		);
		// In both categories.
		$factory->post->create(
			array(
				'post_date'     => '2021-02-15 10:00:00',
				'post_category' => array( self::$news, self::$events ),
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

	private static function categories( ...$ids ) {
		return array_map(
			function ( $id ) {
				return array(
					'id'   => $id,
					'name' => 'Category ' . $id,
				);
			},
			$ids
		);
	}

	/**
	 * Returns the URL of the archive link (href or option value) for a label.
	 */
	private function url_for( $label, $rendered ) {
		$this->assertMatchesRegularExpression(
			'/(?:href|value)=([\'"])([^\'"]*)\1[^>]*>\s*' . preg_quote( $label, '/' ) . '/',
			$rendered,
			"No link for $label"
		);
		preg_match( '/(?:href|value)=([\'"])([^\'"]*)\1[^>]*>\s*' . preg_quote( $label, '/' ) . '/', $rendered, $m );
		return html_entity_decode( $m[2] );
	}

	private function assert_url( $url, $path, array $query ) {
		$this->assertSame( $path, (string) wp_parse_url( $url, PHP_URL_PATH ), $url );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $actual );
		ksort( $actual );
		ksort( $query );
		$this->assertSame( $query, $actual, $url );
	}

	/*
	 * 1: plain permalinks keep the date.
	 */

	public function test_plain_permalink_keeps_month() {
		$this->set_permalink_structure( '' );

		$url = $this->url_for( 'January 2021', $this->render( array( 'categories' => self::categories( self::$news ) ) ) );

		$this->assert_url( $url, '/', array( 'm' => '202101', 'cat' => (string) self::$news ) );
	}

	public function test_plain_permalink_keeps_year() {
		$this->set_permalink_structure( '' );

		$rendered = $this->render(
			array(
				'groupBy'    => 'yearly',
				'categories' => self::categories( self::$news ),
			)
		);

		$this->assert_url( $this->url_for( '2021', $rendered ), '/', array( 'm' => '2021', 'cat' => (string) self::$news ) );
	}

	public function test_pretty_permalink_link() {
		$this->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		$rendered = $this->render(
			array(
				'showMonthOrYear' => true,
				'categories'      => self::categories( self::$news, self::$events ),
			)
		);

		$this->assert_url(
			$this->url_for( 'January 2021', $rendered ),
			'/2021/01/',
			array(
				'cat'            => self::$news . ',' . self::$events,
				'cab_date_title' => '1',
			)
		);
	}

	/*
	 * 2: dropdown options point at the selected categories.
	 */

	public function test_dropdown_options_carry_categories() {
		$this->set_permalink_structure( '' );

		$rendered = $this->render(
			array(
				'displayAsDropdown' => true,
				'showMonthOrYear'   => true,
				'categories'        => self::categories( self::$news ),
			)
		);

		$this->assert_url(
			$this->url_for( 'January 2021', $rendered ),
			'/',
			array(
				'm'              => '202101',
				'cat'            => (string) self::$news,
				'cab_date_title' => '1',
			)
		);
	}

	public function test_dropdown_without_categories_is_unchanged() {
		$this->set_permalink_structure( '' );

		$rendered = $this->render( array( 'displayAsDropdown' => true ) );

		$this->assert_url( $this->url_for( 'January 2021', $rendered ), '/', array( 'm' => '202101' ) );
	}

	/*
	 * 3: a post in several selected categories counts once.
	 */

	/**
	 * @dataProvider data_display_modes
	 */
	public function test_post_in_two_selected_categories_counts_once( $dropdown ) {
		$rendered = $this->render(
			array(
				'displayAsDropdown' => $dropdown,
				'showPostCounts'    => true,
				'categories'        => self::categories( self::$news, self::$events ),
			)
		);

		$this->assertMatchesRegularExpression( '/February 2021\s*(?:<\/a>)?&nbsp;\(1\)/', $rendered );
		$this->assertMatchesRegularExpression( '/January 2021\s*(?:<\/a>)?&nbsp;\(1\)/', $rendered );
		$this->assertMatchesRegularExpression( '/December 2020\s*(?:<\/a>)?&nbsp;\(1\)/', $rendered );
	}

	public function data_display_modes() {
		return array(
			'list'     => array( false ),
			'dropdown' => array( true ),
		);
	}

	public function test_post_by_post_lists_each_post_once() {
		$rendered = $this->render(
			array(
				'groupBy'    => 'postbypost',
				'categories' => self::categories( self::$news, self::$events ),
			)
		);

		$this->assertSame( 3, substr_count( $rendered, '<li>' ), $rendered );
	}
}
