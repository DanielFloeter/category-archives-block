<?php
/**
 * Plugin Name:     Category Archives Block
 * Plugin URI:      https://wordpress.org/plugins/category-archives-block/
 * Description:     Displays a monthly or yearly archive of posts for one specific category.
 * Version:         0.1.0
 * Author:          TipTopPress
 * Author URI:      http://tiptoppress.com
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     category-archives-block
 *
 * @package         tiptip
 */


/**
 * Renders the `tiptip/category-archives-block` on server.
 *
 * @see WP_Widget_Archives
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with archives added.
 */
function render_block_category_archive( $attributes ) {
	$show_post_count = ! empty( $attributes['showPostCounts'] );

	$class = '';
	
	add_filter( 'getarchives_where', function( $x ) use ( $attributes ) {
			return custom_archive_by_category_where( $x, $attributes );
		}
	);
	add_filter( 'getarchives_join', 'custom_archive_by_category_join' );

	if ( ! empty( $attributes['displayAsDropdown'] ) ) {

		$class .= ' category-archives-block-dropdown';

		$dropdown_id = esc_attr( uniqid( 'category-archives-block-' ) );
		$title       = __( 'Archives' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-archives.php */
		$dropdown_args = apply_filters(
			'widget_archives_dropdown_args',
			array(
				'type'            => 'monthly',
				'format'          => 'option',
				'show_post_count' => $show_post_count,
				'order'           => $attributes['order'],
			)
		);

		$dropdown_args['echo'] = 0;

		$archives = wp_get_archives( $dropdown_args );

		switch ( $dropdown_args['type'] ) {
			case 'yearly':
				$label = __( 'Select Year' );
				break;
			case 'monthly':
				$label = __( 'Select Month' );
				break;
			case 'daily':
				$label = __( 'Select Day' );
				break;
			case 'weekly':
				$label = __( 'Select Week' );
				break;
			default:
				$label = __( 'Select Post' );
				break;
		}

		$label = esc_html( $label );

		$block_content = '<label class="screen-reader-text" for="' . $dropdown_id . '">' . $title . '</label>
	<select id="' . $dropdown_id . '" name="archive-dropdown" onchange="document.location.href=this.options[this.selectedIndex].value;">
	<option value="">' . $label . '</option>' . $archives . '</select>';

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( $class ),
			$block_content
		);
	}

	$class .= ' category-archives-block-list';

	/** This filter is documented in wp-includes/widgets/class-wp-widget-archives.php */
	$archives_args = apply_filters(
		'widget_archives_args',
		array(
			'type'            => $attributes['groupBy'],
			'show_post_count' => $show_post_count,
			'order'           => $attributes['order'],
		)
	);

	$archives_args['echo'] = 0;
	
	$archives = wp_get_archives( $archives_args );

	remove_filter( 'getarchives_where', 'custom_archive_by_category_where' );
	remove_filter( 'getarchives_join', 'custom_archive_by_category_join' );

	$classnames = esc_attr( $class );

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	if ( empty( $archives ) ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			__( 'No archives to show.' )
		);
	}

	return sprintf(
		'<ul %1$s>%2$s</ul>',
		$wrapper_attributes,
		$archives
	);
}

/**
 * Filter over table term_taxonomy
 */
function custom_archive_by_category_join( $x ) {
    global $wpdb;
    return $x . " INNER JOIN $wpdb->term_relationships" . 
				" ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)" . 
				" INNER JOIN $wpdb->term_taxonomy" . 
				" ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";
}

/**
 * Filter categories
 */
function custom_archive_by_category_where( $x, $categories ) {
    global $wpdb;

	$i = 0;
	$includeIds = '';
	foreach( $categories['categories'] as $category ) {
		$current_term = get_term_by( 'id', $category['id'], 'category' );
		if (is_wp_error( $current_term ) ) {
			return $x;
		}
		if( $i !== 0 ) { 
			$includeIds .= ','; 
		}
		$includeIds .= $current_term->term_id;
		$i++;
	}
	return $x . " AND $wpdb->term_taxonomy.taxonomy = 'category' AND $wpdb->term_taxonomy.term_id IN ( $includeIds )";
}

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function category_archives_block_init() {
	$dir = __DIR__;

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "tiptip/category-archives-block" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'tiptip-category-archives-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'tiptip-category-archives-block-editor', 'category-archives-block' );

	$editor_css = 'build/style-index.css';
	wp_register_style(
		'tiptip-category-archives-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'tiptip-category-archives-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type(
		'tiptip/category-archives-block',
		array(
			'editor_script' => 'tiptip-category-archives-block-editor',
			'editor_style'  => 'tiptip-category-archives-block-editor',
			'style'         => 'tiptip-category-archives-block',
			'attributes'    => array(
				'showPostCounts' => array(
				'type'           => 'boolean',
				'default'        => false,
				),
				'groupBy' => array(
				'type'    => 'string',
				'default' => 'monthly',
				),
				'order' => array(
					'type'    => 'string',
					'default' => 'desc',
				),
				'orderBy' => array(
					'type'    => 'string',
					'default' => 'date',
				),
				'categorySuggestions' => array(
					'type'    => 'array',
					'default' => [],
				),
				'selectCategories' => array(
					'type'    => 'array',
					'default' => '',
				),
				'categories' => array(
					'type'    => 'array',
					'default' => [],
				),
			),
			'render_callback' => 'render_block_category_archive',
		)
	);
}
add_action( 'init', 'category_archives_block_init' );
