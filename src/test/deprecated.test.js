// @vitest-environment happy-dom
/**
 * Tests for the save() deprecation (issue #16).
 *
 * Blocks saved up to 1.0.4 contain placeholder markup. They must still parse
 * as valid blocks and lose the attributes that were removed.
 */
import { beforeAll, describe, expect, it } from 'vitest';
import {
	registerBlockType,
	parse,
	serialize,
	getBlockType,
} from '@wordpress/blocks';
// Registers the block supports hooks that add classes on save.
import '@wordpress/block-editor';

import metadata from '../../block.json';
import deprecated from '../deprecated';

const { name } = metadata;

beforeAll( () => {
	if ( ! getBlockType( name ) ) {
		registerBlockType( name, {
			...metadata,
			edit: () => null,
			save: () => null,
			deprecated,
		} );
	}
} );

describe( 'deprecated save', () => {
	it( 'migrates a block saved by 1.0.4', () => {
		const [ block ] = parse(
			'<!-- wp:tiptip/category-archives-block {"groupBy":"yearly","orderBy":"title","selectCategories":[],"align":"wide"} -->\n' +
				'<div class="wp-block-tiptip-category-archives-block alignwide"> Hello in Save.</div>\n' +
				'<!-- /wp:tiptip/category-archives-block -->'
		);

		expect( block.isValid ).toBe( true );
		expect( block.attributes.groupBy ).toBe( 'yearly' );
		expect( block.attributes.align ).toBe( 'wide' );
		expect( block.attributes ).not.toHaveProperty( 'orderBy' );
		expect( block.attributes ).not.toHaveProperty( 'selectCategories' );
		expect( serialize( block ) ).toBe(
			'<!-- wp:tiptip/category-archives-block {"groupBy":"yearly","align":"wide"} /-->'
		);
	} );

	it( 'parses a block saved by this version', () => {
		const [ block ] = parse(
			'<!-- wp:tiptip/category-archives-block {"showLabel":true} /-->'
		);

		expect( block.isValid ).toBe( true );
		expect( block.attributes.showLabel ).toBe( true );
	} );
} );
