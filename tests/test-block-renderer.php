<?php
/**
 * Tests for the editor preview request (issue #6).
 *
 * The editor now sends the attributes by POST and stores only { id, name }
 * per category. The server must render both that shape and the full REST
 * objects that blocks saved by older versions still contain.
 *
 * @package tiptip
 */

class Test_Block_Renderer extends WP_UnitTestCase {

	const ROUTE = '/wp/v2/block-renderer/tiptip/category-archives-block';

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

	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
	}

	/**
	 * Sends a preview request the way ServerSideRender does.
	 *
	 * @param string $method     GET or POST.
	 * @param array  $attributes Block attributes.
	 * @return WP_REST_Response
	 */
	private function render( $method, $attributes ) {
		$request = new WP_REST_Request( $method, self::ROUTE );
		$request->set_query_params( array( 'context' => 'edit' ) );

		if ( 'POST' === $method ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( array( 'attributes' => $attributes ) ) );
		} else {
			$request->set_query_params(
				array(
					'context'    => 'edit',
					'attributes' => $attributes,
				)
			);
		}

		return rest_get_server()->dispatch( $request );
	}

	private function assert_only_news( WP_REST_Response $response ) {
		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$rendered = $response->get_data()['rendered'];
		$this->assertStringContainsString( 'January 2021', $rendered );
		$this->assertStringNotContainsString( 'December 2020', $rendered );
		$this->assertStringContainsString( '?cat=' . self::$news, $rendered );
	}

	public function test_renderer_accepts_post_with_id_and_name() {
		$this->assert_only_news(
			$this->render(
				'POST',
				array(
					'categories' => array(
						array(
							'id'   => self::$news,
							'name' => 'News',
						),
					),
				)
			)
		);
	}

	public function test_renderer_accepts_get_with_id_and_name() {
		$this->assert_only_news(
			$this->render(
				'GET',
				array(
					'categories' => array(
						array(
							'id'   => self::$news,
							'name' => 'News',
						),
					),
				)
			)
		);
	}

	/**
	 * Blocks saved before the fix hold the full REST category objects.
	 */
	public function test_renderer_accepts_post_with_legacy_rest_objects() {
		$this->assert_only_news(
			$this->render(
				'POST',
				array(
					'categories' => array(
						array(
							'id'          => self::$news,
							'count'       => 1,
							'description' => '',
							'link'        => 'http://example.org/category/news/',
							'name'        => 'News',
							'slug'        => 'news',
							'taxonomy'    => 'category',
							'parent'      => 0,
							'meta'        => array(),
							'_links'      => array(
								'self' => array( array( 'href' => 'http://example.org/wp-json/wp/v2/categories/' . self::$news ) ),
							),
						),
					),
				)
			)
		);
	}

	public function test_renderer_without_categories_shows_all_months() {
		$response = $this->render( 'POST', array() );

		$this->assertSame( 200, $response->get_status() );
		$rendered = $response->get_data()['rendered'];
		$this->assertStringContainsString( 'January 2021', $rendered );
		$this->assertStringContainsString( 'December 2020', $rendered );
	}
}
