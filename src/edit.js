/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	RadioControl,
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
		groupBy,
		order,
		orderBy,
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
							{ label: 'Month', value: 'monthly' },
							{ label: 'Year', value: 'yearly' },
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
					<ToggleControl
						label={ __( 'Show post counts' ) }
						checked={ showPostCounts }
						onChange={ () =>
							setAttributes( {
								showPostCounts: ! showPostCounts,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Adapt Title on Result Page' ) }
						checked={ showMonthOrYear }
						onChange={ () =>
							setAttributes( {
								showMonthOrYear: ! showMonthOrYear,
							} )
						}
					/>
					<QueryControls
						{ ...{ order, orderBy } }
						onOrderChange={ ( value ) =>
							setAttributes( { order: value } )
						}
						onOrderByChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
						categorySuggestions={ categorySuggestions }
						onCategoryChange={ selectCategories }
						selectedCategories={ categories }
					/>
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
