/**
 * Category handling for the editor.
 *
 * The block stores only { id, name } per category. Blocks saved by older
 * versions hold the complete REST category objects, which made the
 * preview request URL too long for many servers (issue #6).
 */

export const BLOCK_NAME = 'tiptip/category-archives-block';

/**
 * Reduces a list of categories to { id, name } entries with a valid id.
 *
 * @param {Array} categories Categories as stored or as returned by the REST API.
 * @return {Array<{id: number, name: string}>} Normalized categories.
 */
export function normalizeCategories( categories ) {
	if ( ! Array.isArray( categories ) ) {
		return [];
	}

	return categories
		.filter(
			( category ) =>
				category &&
				typeof category === 'object' &&
				Number.isInteger( Number( category.id ) ) &&
				Number( category.id ) > 0
		)
		.map( ( { id, name } ) => ( { id: Number( id ), name } ) );
}

/**
 * Whether stored categories carry more than { id, name } and should be
 * rewritten.
 *
 * @param {Array} categories Stored categories.
 * @return {boolean} True if any entry has extra fields.
 */
export function needsNormalization( categories ) {
	if ( ! Array.isArray( categories ) ) {
		return false;
	}

	return categories.some(
		( category ) =>
			! category ||
			typeof category !== 'object' ||
			Object.keys( category ).some(
				( key ) => key !== 'id' && key !== 'name'
			)
	);
}

/**
 * Turns the tokens of the category field into normalized categories.
 *
 * Already selected categories arrive as objects, newly picked ones as their
 * name.
 *
 * @param {Array<Object|string>} tokens      Tokens from the category field.
 * @param {Object}               suggestions Categories keyed by name.
 * @return {Array<{id: number, name: string}>|null} Categories, or null if a
 *                                                  token is not a known category.
 */
export function tokensToCategories( tokens, suggestions ) {
	const categories = tokens.map( ( token ) =>
		typeof token === 'string' ? suggestions[ token ] ?? null : token
	);

	if ( categories.includes( null ) ) {
		return null;
	}

	return normalizeCategories( categories );
}

/**
 * Props for ServerSideRender.
 *
 * POST keeps the attributes out of the URL; the categories are normalized in
 * case the block was saved by an older version.
 *
 * @param {Object} attributes Block attributes.
 * @return {Object} ServerSideRender props.
 */
export function getRendererProps( attributes ) {
	const rendererAttributes = { ...attributes };

	if ( 'categories' in rendererAttributes ) {
		rendererAttributes.categories = normalizeCategories(
			rendererAttributes.categories
		);
	}

	return {
		block: BLOCK_NAME,
		attributes: rendererAttributes,
		httpMethod: 'POST',
	};
}
