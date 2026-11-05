/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @param {Object} attributes Block attributes.
 * @param {string} className  Block class name.
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#save
 *
 * @return {Element} Element to render.
 */
export default function save( attributes, className ) {
	return <div className={ className }>{ attributes.message }</div>;
}
