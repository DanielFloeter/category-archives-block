/**
 * Conversion from the core Archives block.
 */

const GROUP_BY_TYPES = [ 'yearly', 'monthly', 'weekly', 'daily' ];

/**
 * Maps core/archives attributes to this block's attributes.
 *
 * Types this block does not support (e.g. "postbypost") fall back to
 * "monthly".
 *
 * @param {Object} attributes core/archives attributes.
 * @return {Object} Category Archives block attributes.
 */
export function fromCoreArchives( attributes = {} ) {
	const {
		type,
		displayAsDropdown = false,
		showPostCounts = false,
		showLabel = true,
		className,
		align,
		anchor,
		style,
		backgroundColor,
		textColor,
		gradient,
		fontSize,
		fontFamily,
		borderColor,
	} = attributes;

	const presentational = Object.fromEntries(
		Object.entries( {
			className,
			align,
			anchor,
			style,
			backgroundColor,
			textColor,
			gradient,
			fontSize,
			fontFamily,
			borderColor,
		} ).filter( ( [ , value ] ) => value !== undefined )
	);

	return {
		...presentational,
		groupBy: GROUP_BY_TYPES.includes( type ) ? type : 'monthly',
		displayAsDropdown: !! displayAsDropdown,
		showPostCounts: !! showPostCounts,
		showLabel: !! showLabel,
	};
}
