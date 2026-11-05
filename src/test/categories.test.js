/**
 * Tests for the category handling of the editor (issue #6).
 *
 * The block used to store the complete REST category objects in its
 * `categories` attribute and ServerSideRender sent all attributes as GET query
 * arguments. That made the block-renderer URL grow by ~2,000 characters per
 * category, which servers reject (400/414 or a WAF block), so the editor showed
 * "The response is not a valid JSON response".
 */
import { describe, expect, it } from 'vitest';
import { addQueryArgs } from '@wordpress/url';

import {
	normalizeCategories,
	needsNormalization,
	tokensToCategories,
	getRendererProps,
} from '../categories';

/**
 * A category as /wp/v2/categories returns it to a logged-in editor.
 *
 * @param {number} id Category ID.
 * @return {Object} REST category object.
 */
const restCategory = ( id ) => ( {
	id,
	count: 6,
	description: 'Toutes les actualités de notre association.',
	link: `https://www.example.fr/category/categorie-${ id }/`,
	name: `Catégorie ${ id }`,
	slug: `categorie-${ id }`,
	taxonomy: 'category',
	parent: 0,
	meta: [],
	_links: {
		self: [
			{
				href: `https://www.example.fr/wp-json/wp/v2/categories/${ id }`,
				targetHints: {
					allow: [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ],
				},
			},
		],
		collection: [
			{ href: 'https://www.example.fr/wp-json/wp/v2/categories' },
		],
		about: [
			{
				href: 'https://www.example.fr/wp-json/wp/v2/taxonomies/category',
			},
		],
		'wp:post_type': [
			{
				href: `https://www.example.fr/wp-json/wp/v2/posts?categories=${ id }`,
			},
		],
		curies: [
			{ name: 'wp', href: 'https://api.w.org/{rel}', templated: true },
		],
	},
} );

const BLOCK = 'tiptip/category-archives-block';

describe( 'normalizeCategories', () => {
	it( 'keeps only id and name of REST category objects', () => {
		expect(
			normalizeCategories( [ restCategory( 3 ), restCategory( 7 ) ] )
		).toEqual( [
			{ id: 3, name: 'Catégorie 3' },
			{ id: 7, name: 'Catégorie 7' },
		] );
	} );

	it( 'leaves already normalized categories unchanged', () => {
		const categories = [ { id: 3, name: 'News' } ];
		expect( normalizeCategories( categories ) ).toEqual( categories );
	} );

	it( 'drops entries without a usable id', () => {
		expect(
			normalizeCategories( [
				{ id: 3, name: 'News' },
				null,
				'News',
				{ name: 'No id' },
				{ id: 0, name: 'Zero' },
			] )
		).toEqual( [ { id: 3, name: 'News' } ] );
	} );

	it( 'returns an empty array for missing or invalid input', () => {
		expect( normalizeCategories( undefined ) ).toEqual( [] );
		expect( normalizeCategories( '' ) ).toEqual( [] );
		expect( normalizeCategories( null ) ).toEqual( [] );
	} );
} );

describe( 'needsNormalization', () => {
	it( 'is true for blocks saved with full REST objects', () => {
		expect( needsNormalization( [ restCategory( 3 ) ] ) ).toBe( true );
	} );

	it( 'is false for normalized or empty categories', () => {
		expect( needsNormalization( [ { id: 3, name: 'News' } ] ) ).toBe(
			false
		);
		expect( needsNormalization( [] ) ).toBe( false );
		expect( needsNormalization( undefined ) ).toBe( false );
	} );
} );

describe( 'tokensToCategories', () => {
	const suggestions = {
		'Catégorie 3': restCategory( 3 ),
		'Catégorie 7': restCategory( 7 ),
	};

	it( 'turns newly typed names into normalized categories', () => {
		expect(
			tokensToCategories(
				[ { id: 3, name: 'Catégorie 3' }, 'Catégorie 7' ],
				suggestions
			)
		).toEqual( [
			{ id: 3, name: 'Catégorie 3' },
			{ id: 7, name: 'Catégorie 7' },
		] );
	} );

	it( 'never stores the full REST object of a picked suggestion', () => {
		const [ category ] = tokensToCategories( [ 'Catégorie 3' ], suggestions );
		expect( Object.keys( category ).sort() ).toEqual( [ 'id', 'name' ] );
	} );

	it( 'returns null when a token is not a known category', () => {
		expect( tokensToCategories( [ 'Unknown' ], suggestions ) ).toBeNull();
	} );
} );

describe( 'getRendererProps', () => {
	it( 'renders through POST so attributes stay out of the URL', () => {
		expect( getRendererProps( {} ) ).toMatchObject( {
			block: BLOCK,
			httpMethod: 'POST',
		} );
	} );

	it( 'sends normalized categories even for legacy attributes', () => {
		const { attributes } = getRendererProps( {
			groupBy: 'yearly',
			categories: [ restCategory( 3 ) ],
		} );
		expect( attributes ).toEqual( {
			groupBy: 'yearly',
			categories: [ { id: 3, name: 'Catégorie 3' } ],
		} );
	} );

	it( 'keeps even a GET fallback URL short with many categories', () => {
		const categories = Array.from( { length: 20 }, ( _, i ) =>
			restCategory( i + 1 )
		);
		const { attributes } = getRendererProps( { categories } );
		const path = addQueryArgs( `/wp/v2/block-renderer/${ BLOCK }`, {
			context: 'edit',
			attributes,
		} );
		// Apache's and nginx's default request-line limit is 8 KB; many
		// shared hosts allow far less.
		expect( path.length ).toBeLessThan( 4096 );
	} );
} );
