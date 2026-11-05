/**
 * Tests for the transform from core/archives (issue #15).
 */
import { describe, expect, it } from 'vitest';

import { fromCoreArchives } from '../transforms';

describe( 'fromCoreArchives', () => {
	it( 'maps the core defaults', () => {
		expect( fromCoreArchives( {} ) ).toEqual( {
			groupBy: 'monthly',
			displayAsDropdown: false,
			showPostCounts: false,
			showLabel: true,
		} );
	} );

	it( 'keeps settings and styles', () => {
		expect(
			fromCoreArchives( {
				type: 'yearly',
				displayAsDropdown: true,
				showPostCounts: true,
				showLabel: false,
				className: 'my-archive',
				align: 'wide',
				textColor: 'primary',
				style: { spacing: { padding: '1em' } },
			} )
		).toEqual( {
			groupBy: 'yearly',
			displayAsDropdown: true,
			showPostCounts: true,
			showLabel: false,
			className: 'my-archive',
			align: 'wide',
			textColor: 'primary',
			style: { spacing: { padding: '1em' } },
		} );
	} );

	it.each( [ 'weekly', 'daily' ] )( 'keeps %s', ( type ) => {
		expect( fromCoreArchives( { type } ).groupBy ).toBe( type );
	} );

	it( 'falls back to monthly for unsupported types', () => {
		expect( fromCoreArchives( { type: 'postbypost' } ).groupBy ).toBe(
			'monthly'
		);
	} );
} );
