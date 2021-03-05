/**
 * WordPress dependencies
 */
import { PanelBody, ToggleControl, RadioControl, Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { showPostCounts, groupBy } = attributes;

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
						onChange={ ( groupBy ) =>
							setAttributes( {
								groupBy,
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
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="tiptip/category-archives-block"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
