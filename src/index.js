
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './style.scss';

import Edit from './edit';
import save from './save';

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
		'Displays a monthly or yearly archive of posts for one or more specific categories.',
		'category-archives-block'
	),

	keywords: [
		__( 'monthly', 'category-archives-block' ),
		__( 'yearly', 'category-archives-block' ),
		__( 'category', 'category-archives-block' ),
		__( 'archive', 'category-archives-block' ),
		__( 'archives', 'category-archives-block' ),
		__( 'tiptoppress', 'category-archives-block' ),
		__( 'dropdown', 'category-archives-block' ),
		__( 'post count', 'category-archives-block' ),
		__( 'month', 'category-archives-block' ),
		__( 'year', 'category-archives-block' ),
		__( 'date', 'category-archives-block' ),
		__( 'posts', 'category-archives-block' ),
		__( 'blog', 'category-archives-block' ),
		__( 'history', 'category-archives-block' ),
		__( 'timeline', 'category-archives-block' ),
		__( 'taxonomy', 'category-archives-block' ),
		__( 'widget', 'category-archives-block' ),
	],

	example: {
		attributes: {
			values:
				'<ul><a>January 2021</a><a>December 2020</a><a>November 2020</a><a>October 2020</a></ul>',
			showPostCounts: 
				true,
		},
	},

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save: () => {
		const blockProps = useBlockProps.save();
		return <div { ...blockProps }> Hello in Save.</div>;
	},
} );
