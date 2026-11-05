/**
 * Block deprecations.
 */
import { createElement } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Versions up to 1.0.4 saved placeholder markup from the starter template,
 * although the block is rendered on the server. Since 1.0.5 save() returns
 * null; this entry keeps those blocks valid and drops the markup on the next
 * save.
 */
const v1 = {
	attributes: {
		showMonthOrYear: { type: 'boolean', default: false },
		showPostCounts: { type: 'boolean', default: false },
		displayAsDropdown: { type: 'boolean', default: false },
		groupBy: { type: 'string', default: 'monthly' },
		order: { type: 'string', default: 'desc' },
		orderBy: { type: 'string', default: 'date' },
		categorySuggestions: { type: 'array', default: [] },
		selectCategories: { type: 'array', default: '' },
		categories: { type: 'array', default: [] },
	},
	supports: {
		align: true,
		html: false,
		spacing: { margin: true, padding: true },
		color: { background: true, link: true, text: true },
		typography: {
			fontSize: true,
			lineHeight: true,
			__experimentalFontFamily: true,
			__experimentalFontWeight: true,
			__experimentalFontStyle: true,
			__experimentalTextTransform: true,
			__experimentalTextDecoration: true,
			__experimentalLetterSpacing: true,
		},
	},
	migrate( { orderBy, categorySuggestions, selectCategories, ...rest } ) {
		return rest;
	},
	save() {
		const blockProps = useBlockProps.save();
		return createElement( 'div', blockProps, ' Hello in Save.' );
	},
};

export default [ v1 ];
