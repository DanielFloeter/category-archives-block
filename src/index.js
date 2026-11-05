import { registerBlockType, createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import './style.scss';

import Edit from './edit';
import deprecated from './deprecated';
import { fromCoreArchives } from './transforms';

import metadata from './../block.json';
const { name } = metadata;

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
registerBlockType( name, {
	...metadata,

	/**
	 * This is a short description for your block, can be translated with `i18n` functions.
	 * It will be shown in the Block Tab in the Settings Sidebar.
	 */
	description: __(
		'Displays a yearly, monthly, weekly or daily archive of posts for one or more specific categories.',
		'category-archives-block'
	),

	keywords: [
		__( 'monthly', 'category-archives-block' ),
		__( 'yearly', 'category-archives-block' ),
		__( 'weekly', 'category-archives-block' ),
		__( 'daily', 'category-archives-block' ),
		__( 'category', 'category-archives-block' ),
		__( 'archive', 'category-archives-block' ),
		__( 'archives', 'category-archives-block' ),
		__( 'tiptoppress', 'category-archives-block' ),
		__( 'dropdown', 'category-archives-block' ),
		__( 'post count', 'category-archives-block' ),
		__( 'month', 'category-archives-block' ),
		__( 'year', 'category-archives-block' ),
		__( 'week', 'category-archives-block' ),
		__( 'day', 'category-archives-block' ),
		__( 'date', 'category-archives-block' ),
		__( 'posts', 'category-archives-block' ),
		__( 'blog', 'category-archives-block' ),
		__( 'history', 'category-archives-block' ),
		__( 'timeline', 'category-archives-block' ),
		__( 'taxonomy', 'category-archives-block' ),
		__( 'widget', 'category-archives-block' ),
	],

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	// Rendered on the server, see render_category_archives_block().
	save: () => null,

	deprecated,

	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/archives' ],
				transform: ( attributes ) =>
					createBlock( name, fromCoreArchives( attributes ) ),
			},
		],
	},
} );
