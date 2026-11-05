/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	RadioControl,
	SelectControl,
	QueryControls,
	Disabled,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Internal dependencies
 */
import {
	getRendererProps,
	needsNormalization,
	normalizeCategories,
	tokensToCategories,
} from './categories';

/**
 * Module Constants
 */
const CATEGORIES_LIST_QUERY = {
	per_page: -1,
	_fields: 'id,name',
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		showMonthOrYear,
		showPostCounts,
		displayAsDropdown,
		showLabel,
		groupBy,
		order,
		categories,
	} = attributes;
	const [ categoriesList, setCategoriesList ] = useState( [] );
	const categorySuggestions = categoriesList.reduce(
		( accumulator, category ) => ( {
			...accumulator,
			[ category.name ]: category,
		} ),
		{}
	);
	const selectCategories = ( tokens ) => {
		const selected = tokensToCategories( tokens, categorySuggestions );
		// Ignore tokens that were typed but not picked from the suggestions.
		if ( selected === null ) {
			return;
		}
		setAttributes( { categories: selected } );
	};

	// Blocks saved by older versions store the full REST category objects; trim
	// them to { id, name } so the saved post content shrinks as well.
	useEffect( () => {
		if ( needsNormalization( categories ) ) {
			setAttributes( { categories: normalizeCategories( categories ) } );
		}
	}, [ categories, setAttributes ] );

	const isStillMounted = useRef();

	useEffect( () => {
		isStillMounted.current = true;

		apiFetch( {
			path: addQueryArgs( `/wp/v2/categories`, CATEGORIES_LIST_QUERY ),
		} )
			.then( ( data ) => {
				if ( isStillMounted.current ) {
					setCategoriesList( data );
				}
			} )
			.catch( () => {
				if ( isStillMounted.current ) {
					setCategoriesList( [] );
				}
			} );

		return () => {
			isStillMounted.current = false;
		};
	}, [] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Archives settings' ) }>
					<RadioControl
						label={ __( 'Group by' ) }
						selected={ groupBy }
						options={ [
							{ label: __( 'Year' ), value: 'yearly' },
							{ label: __( 'Month' ), value: 'monthly' },
							{ label: __( 'Week' ), value: 'weekly' },
							{ label: __( 'Day' ), value: 'daily' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								groupBy: value,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Display as dropdown' ) }
						checked={ displayAsDropdown }
						onChange={ () =>
							setAttributes( {
								displayAsDropdown: ! displayAsDropdown,
							} )
						}
					/>
					{ displayAsDropdown && (
						<ToggleControl
							label={ __( 'Show label' ) }
							checked={ showLabel }
							onChange={ () =>
								setAttributes( {
									showLabel: ! showLabel,
								} )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Show post counts' ) }
						checked={ showPostCounts }
						onChange={ () =>
							setAttributes( {
								showPostCounts: ! showPostCounts,
							} )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Order' ) }
						value={ order }
						options={ [
							{ label: __( 'Newest to oldest' ), value: 'desc' },
							{ label: __( 'Oldest to newest' ), value: 'asc' },
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
					<QueryControls
						categorySuggestions={ categorySuggestions }
						onCategoryChange={ selectCategories }
						selectedCategories={ categories }
					/>
					{ categories?.length > 0 && (
						<ToggleControl
							label={ __(
								'Show date in archive title',
								'category-archives-block'
							) }
							help={ __(
								'The archive page title shows the date (e.g. "Month: May 2026") instead of the category. Not for weekly archives.',
								'category-archives-block'
							) }
							checked={ showMonthOrYear }
							onChange={ () =>
								setAttributes( {
									showMonthOrYear: ! showMonthOrYear,
								} )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender { ...getRendererProps( attributes ) } />
				</Disabled>
			</div>
		</>
	);
}
