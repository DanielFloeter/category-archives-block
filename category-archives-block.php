<?php
/**
 * Plugin Name:     Category Archives Block
 * Plugin URI:      https://wordpress.org/plugins/category-archives-block/
 * Description:     Displays a monthly or yearly archive of posts for one or more specific categories.
 * Version:         0.1.1
 * Author:          TipTopPress
 * Author URI:      http://tiptoppress.com
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     category-archives-block
 *
 * @package         tiptip
 */

namespace categoryArchivesBlock;

const VERSION        = '0.1.1';

/**
 * Renders the `tiptip/category-archives-block` on server.
 *
 * @see WP_Widget_Archives
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with archives added.
 */
function render_category_archives_block( $attributes ) {
	global $attr;

	$attr = $attributes;

	$show_post_count = ! empty( $attributes['showPostCounts'] );

	$class = '';
	
	add_filter( 'getarchives_where', __NAMESPACE__ . '\category_archives_block_where' );
	add_filter( 'getarchives_join', __NAMESPACE__ . '\category_archives_block_join' );
	add_filter( 'get_archives_link', __NAMESPACE__ . '\category_archives_block_archives_url' );

	if ( ! empty( $attributes['displayAsDropdown'] ) ) {

		$class .= ' category-archives-block-dropdown';

		$dropdown_id = esc_attr( uniqid( 'category-archives-block-' ) );
		$title       = __( 'Archives' );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-archives.php */
		$dropdown_args = apply_filters(
			'widget_archives_dropdown_args',
			array(
				'type'            => $attributes['groupBy'],
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
			'format'          => 'custom',
			'show_post_count' => $show_post_count,
			'order'           => $attributes['order'],
		)
	);

	$archives_args['echo'] = 0;
	
	$archives = wp_get_archives( $archives_args );

	remove_filter( 'getarchives_where', __NAMESPACE__ . '\category_archives_block_where' );
	remove_filter( 'getarchives_join', __NAMESPACE__ . '\category_archives_block_join' );
	remove_filter( 'get_archives_link', __NAMESPACE__ . '\category_archives_block_archives_url' );

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
 * Add Url params for categories
 */
function category_archives_block_archives_url( $links ) {
	global $attr;

	if( empty( $attr['categories'] ) ) {
		return $links;
	}

	$pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/';
	preg_match( $pattern, $links, $matches );
	$splitUrl = explode( "?", $matches[2] );

	$i = 0;
	$includeIds = '';
	foreach( $attr['categories'] as $category ) {
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

	return str_replace( $matches[2], $splitUrl[0] . '?cat=' . $includeIds, $links );
}

/**
 * Filter over table term_taxonomy
 */
function category_archives_block_join( $x ) {
    global $wpdb;

    return $x . " INNER JOIN $wpdb->term_relationships" . 
				" ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)" . 
				" INNER JOIN $wpdb->term_taxonomy" . 
				" ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";
}

/**
 * Filter categories
 */
function category_archives_block_where( $x ) {
    global $wpdb, $attr;

	if ( empty( $attr['categories'] ) ) {
		return $x;
	}

	$i = 0;
	$includeIds = '';
	foreach( $attr['categories'] as $category ) {
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
				'displayAsDropdown' => array(
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
			'render_callback' => __NAMESPACE__ . '\render_category_archives_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\category_archives_block_init' );
