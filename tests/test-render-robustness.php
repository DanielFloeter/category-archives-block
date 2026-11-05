<?php
/**
 * Tests for the robustness fixes in the PHP render code (issue #8).
 *
 * @package tiptip
 */

class Test_Render_Robustness extends WP_UnitTestCase {

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
	}

	/**
	 * Renders the block the way the front end does, block.json defaults included.
	 */
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

	private static function category( $id ) {
		return array(
			'id'   => $id,
			'name' => 'Category ' . $id,
		);
	}

	/**
	 * Returns an ID that belonged to a category which has been deleted since.
	 */
	private function deleted_category_id() {
		$id = self::factory()->category->create( array( 'name' => 'Gone' ) );
		wp_delete_term( $id, 'category' );
		return $id;
	}

	private function assert_no_db_error() {
		global $wpdb;
		$this->assertSame( '', $wpdb->last_error );
	}

	/*
	 * 1 + 2: deleted categories.
	 */

	public function test_deleted_category_is_skipped_in_list() {
		$rendered = $this->render(
			array(
				'categories' => array( self::category( self::$news ), self::category( $this->deleted_category_id() ) ),
			)
		);

		$this->assert_no_db_error();
		$this->assertStringContainsString( 'January 2021', $rendered );
		$this->assertStringNotContainsString( 'December 2020', $rendered );
		$this->assertMatchesRegularExpression( '/\?cat=' . self::$news . '[\'"]/', $rendered );
	}

	public function test_deleted_category_is_skipped_in_dropdown() {
		$rendered = $this->render(
			array(
				'displayAsDropdown' => true,
				'categories'        => array( self::category( $this->deleted_category_id() ), self::category( self::$news ) ),
			)
		);

		$this->assert_no_db_error();
		$this->assertStringContainsString( 'January 2021', $rendered );
		$this->assertStringNotContainsString( 'December 2020', $rendered );
	}

	/**
	 * When every selected category is gone, the block must not silently fall
	 * back to the archive of all posts.
	 */
	public function test_only_deleted_categories_show_no_archives() {
		$rendered = $this->render(
			array(
				'categories' => array( self::category( $this->deleted_category_id() ) ),
			)
		);

		$this->assert_no_db_error();
		$this->assertStringNotContainsString( 'January 2021', $rendered );
		$this->assertStringNotContainsString( 'December 2020', $rendered );
		$this->assertStringContainsString( 'No archives to show.', $rendered );
	}

	/*
	 * 3: filters must not leak into later wp_get_archives() calls.
	 */

	public function data_display_modes() {
		return array(
			'list'     => array( false ),
			'dropdown' => array( true ),
		);
	}

	/**
	 * @dataProvider data_display_modes
	 */
	public function test_filters_are_removed_after_render( $dropdown ) {
		$this->render(
			array(
				'displayAsDropdown' => $dropdown,
				'categories'        => array( self::category( self::$news ) ),
			)
		);

		$this->assertFalse( has_filter( 'getarchives_where' ), 'getarchives_where' );
		$this->assertFalse( has_filter( 'getarchives_join' ), 'getarchives_join' );
		$this->assertFalse( has_filter( 'get_archives_link' ), 'get_archives_link' );

		$archives = wp_get_archives( array( 'echo' => 0 ) );
		$this->assertStringContainsString( 'January 2021', $archives );
		$this->assertStringContainsString( 'December 2020', $archives );
	}

	/*
	 * 4: no option write on render, archive title is decided per block.
	 */

	public function test_render_does_not_write_options() {
		$writes = 0;
		$count  = function ( $value ) use ( &$writes ) {
			$writes++;
			return $value;
		};
		add_filter( 'pre_update_option_category_archives_showMonthOrYear', $count );

		$this->render( array( 'showMonthOrYear' => true ) );
		$this->render( array( 'showMonthOrYear' => false ) );

		remove_filter( 'pre_update_option_category_archives_showMonthOrYear', $count );
		$this->assertSame( 0, $writes );
	}

	public function test_links_carry_date_title_flag_only_when_enabled() {
		$categories = array( self::category( self::$news ) );

		$with    = $this->render( array( 'showMonthOrYear' => true, 'categories' => $categories ) );
		$without = $this->render( array( 'showMonthOrYear' => false, 'categories' => $categories ) );

		$this->assertStringContainsString( 'cab_date_title=1', $with );
		$this->assertStringNotContainsString( 'cab_date_title', $without );
	}

	/**
	 * Two blocks with different settings on one site must not decide each
	 * other's archive title, whatever order they rendered in.
	 */
	public function test_archive_title_follows_link_not_render_order() {
		$this->render( array( 'showMonthOrYear' => true ) );

		$this->go_to( home_url( '/?m=202101&cat=' . self::$news ) );
		$this->assertStringContainsString( 'Category:', get_the_archive_title() );

		$this->render( array( 'showMonthOrYear' => false ) );

		$this->go_to( home_url( '/?m=202101&cat=' . self::$news . '&cab_date_title=1' ) );
		$title = get_the_archive_title();
		$this->assertStringContainsString( 'Month:', $title );
		$this->assertStringContainsString( 'January 2021', $title );
	}

	/*
	 * 5: no generic global.
	 */

	public function test_render_does_not_touch_global_attr() {
		$GLOBALS['attr'] = 'owned by someone else';

		$this->render( array( 'categories' => array( self::category( self::$news ) ) ) );

		$this->assertSame( 'owned by someone else', $GLOBALS['attr'] );
		unset( $GLOBALS['attr'] );
	}
}
