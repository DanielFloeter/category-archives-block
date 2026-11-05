<?php
/**
 * Plugin Name:     Category Archives Block
 * Plugin URI:      https://wordpress.org/plugins/category-archives-block/
 * Description:     Displays a monthly or yearly archive of posts for one or more specific categories.
 * Version:         1.0.4
 * Requires at least: 6.6
 * Requires PHP:    7.0
 * Author:          TipTopPress
 * Author URI:      http://tiptoppress.com
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     category-archives-block
 *
 * @package         tiptip
 */

namespace categoryArchivesBlock;

const VERSION        = '1.0.4';

/**
 * Query arg on the archive links that asks for the month or year as the
 * archive page title (the "showMonthOrYear" setting of the linking block).
 */
const DATE_TITLE_QUERY_VAR = 'cab_date_title';

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
	$show_post_count = ! empty( $attributes['showPostCounts'] );
	$is_dropdown     = ! empty( $attributes['displayAsDropdown'] );

	$filters = array();

	if ( ! empty( $attributes['categories'] ) ) {
		$category_ids = get_category_ids( $attributes['categories'] );
		$date_title   = ! empty( $attributes['showMonthOrYear'] );

		$filters['getarchives_where'] = function ( $where ) use ( $category_ids ) {
			return category_archives_block_where( $where, $category_ids );
		};
		$filters['get_archives_link'] = function ( $link ) use ( $category_ids, $date_title ) {
			return category_archives_block_archives_url( $link, $category_ids, $date_title );
		};
	}

	if ( $is_dropdown ) {

		$dropdown_id = wp_unique_id( 'category-archives-block-' );
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

		$archives = get_filtered_archives( $dropdown_args, $filters );

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

		$label_class = 'category-archives-block__label';
		if ( empty( $attributes['showLabel'] ) ) {
			$label_class .= ' screen-reader-text';
		}

		$block_content = '<label for="' . esc_attr( $dropdown_id ) . '" class="' . esc_attr( $label_class ) . '">' . esc_html( $title ) . '</label>
	<select id="' . esc_attr( $dropdown_id ) . '" name="archive-dropdown">
	<option value="">' . esc_html( $label ) . '</option>' . $archives . '</select>';

		$block_content .= build_dropdown_script( $dropdown_id );

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( array( 'class' => 'category-archives-block-dropdown' ) ),
			$block_content
		);
	}

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

	$archives = get_filtered_archives( $archives_args, $filters );

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'category-archives-block-list' ) );

	if ( empty( $archives ) ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			esc_html__( 'No archives to show.' )
		);
	}

	return sprintf(
		'<ul %1$s>%2$s</ul>',
		$wrapper_attributes,
		$archives
	);
}

/**
 * Builds the script that opens the archive picked in the dropdown.
 *
 * Like core/archives: navigation waits briefly and skips Escape, so keyboard
 * users can move through the options without leaving the page, and the empty
 * placeholder option does nothing.
 *
 * @param string $dropdown_id ID of the select element.
 *
 * @return string The inline script tag.
 */
function build_dropdown_script( $dropdown_id ) {
	$script = <<<'JS'
( ( dropdownId ) => {
	const dropdown = document.getElementById( dropdownId );
	if ( ! dropdown ) {
		return;
	}
	dropdown.addEventListener( 'keyup', ( event ) => {
		if ( 'Escape' === event.key ) {
			dropdown.dataset.lastkey = 'escape';
		} else {
			delete dropdown.dataset.lastkey;
		}
	} );
	dropdown.addEventListener( 'click', () => {
		delete dropdown.dataset.lastkey;
	} );
	dropdown.addEventListener( 'change', () => {
		setTimeout( () => {
			if ( 'escape' === dropdown.dataset.lastkey ) {
				return;
			}
			if ( dropdown.value ) {
				location.href = dropdown.value;
			}
		}, 250 );
	} );
} )
JS;

	return wp_get_inline_script_tag(
		$script . '( ' . wp_json_encode( $dropdown_id, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) . ' );' .
		"\n//# sourceURL=" . rawurlencode( __FUNCTION__ )
	);
}

/**
 * Resolves the selected categories to the IDs of those that still exist.
 *
 * Categories deleted after they were selected in the block are skipped.
 *
 * @param array $categories The block's "categories" attribute.
 *
 * @return int[] Term IDs of the existing categories.
 */
function get_category_ids( $categories ) {
	$ids = array();

	foreach ( (array) $categories as $category ) {
		$id = is_array( $category ) && isset( $category['id'] ) ? absint( $category['id'] ) : 0;
		if ( ! $id ) {
			continue;
		}

		$term = get_term( $id, 'category' );
		if ( $term instanceof \WP_Term ) {
			$ids[] = (int) $term->term_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Runs wp_get_archives() with the given filters, and only for that call.
 *
 * @param array               $args    Arguments for wp_get_archives().
 * @param array<string,mixed> $filters Callbacks keyed by hook name.
 *
 * @return string|void The archives markup.
 */
function get_filtered_archives( $args, $filters ) {
	foreach ( $filters as $hook => $callback ) {
		add_filter( $hook, $callback );
	}

	try {
		return wp_get_archives( $args );
	} finally {
		foreach ( $filters as $hook => $callback ) {
			remove_filter( $hook, $callback );
		}
	}
}

/**
 * Points an archive link (list item or dropdown option) at the selected
 * categories, keeping the date part of the URL.
 *
 * @param string $link         Archive link HTML.
 * @param int[]  $category_ids Term IDs of the selected categories.
 * @param bool   $date_title   Whether the archive page shows the month or year as title.
 *
 * @return string The archive link HTML.
 */
function category_archives_block_archives_url( $link, $category_ids, $date_title = false ) {
	$pattern = '/\b(?:href|value)=(["\'])(.*?)\1/';

	if ( empty( $category_ids ) || ! preg_match( $pattern, $link, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $link;
	}

	list( $old_url, $offset ) = $matches[2];

	$args = array( 'cat' => implode( ',', $category_ids ) );
	if ( $date_title ) {
		$args[ DATE_TITLE_QUERY_VAR ] = 1;
	}

	// The URL in the markup is escaped (& as &#038;), add_query_arg() needs it raw.
	$url = esc_url( add_query_arg( $args, html_entity_decode( $old_url, ENT_QUOTES ) ) );

	return substr_replace( $link, $url, $offset, strlen( $old_url ) );
}

/**
 * Filter categories
 *
 * Uses a subquery instead of joining the term tables, so a post that is in
 * several of the selected categories is counted and listed only once.
 *
 * @param string $where        WHERE clause of the archives query.
 * @param int[]  $category_ids Term IDs of the selected categories. Empty when
 *                             none of them exists any more.
 *
 * @return string The WHERE clause.
 */
function category_archives_block_where( $where, $category_ids ) {
	global $wpdb;

	// Every selected category is gone: show nothing rather than all posts.
	if ( empty( $category_ids ) ) {
		return $where . ' AND 1 = 0';
	}

	$include_ids = implode( ',', array_map( 'intval', $category_ids ) );

	return $where . " AND $wpdb->posts.ID IN (" .
		" SELECT tr.object_id FROM $wpdb->term_relationships AS tr" .
		" INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id" .
		" WHERE tt.taxonomy = 'category' AND tt.term_id IN ( $include_ids )" .
		' )';
}

/**
 * Makes the date title query arg known to WP.
 */
function category_archives_query_vars( $vars ) {
	$vars[] = DATE_TITLE_QUERY_VAR;
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\category_archives_query_vars' );

/**
 * Page Title shows Month or Year on result page
 */
function category_archives_archive_page_title( $title ) {
	$prefix = '';

	if ( get_query_var( DATE_TITLE_QUERY_VAR ) ) {
		if ( is_archive() ) {
			if ( is_year() ) {
				$title  = get_the_date( _x( 'Y', 'yearly archives date format' ) );
				$prefix = _x( 'Year:', 'date archive title prefix' );
			} elseif ( is_month() ) {
				$title  = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
				$prefix = _x( 'Month:', 'date archive title prefix' );
			}
			$prefix = apply_filters( 'get_the_archive_title_prefix', $prefix );
			if ( $prefix ) {
				$title = sprintf(
					/* translators: 1: Title prefix. 2: Title. */
					_x( '%1$s %2$s', 'archive title' ),
					$prefix,
					'<span>' . $title . '</span>'
				);
			}
		}
	}

	return $title;
}
add_filter( 'get_the_archive_title', __NAMESPACE__ . '\category_archives_archive_page_title', 5 );

/**
 * Removes the global option that versions up to 1.0.4 wrote on every render.
 * The setting now travels with each block's archive links.
 */
function category_archives_delete_legacy_option() {
	if ( false !== get_option( 'category_archives_showMonthOrYear' ) ) {
		delete_option( 'category_archives_showMonthOrYear' );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\category_archives_delete_legacy_option' );

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

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => __NAMESPACE__ . '\render_category_archives_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\category_archives_block_init' );
