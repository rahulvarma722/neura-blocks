/**
 * Kit Text — block registration.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,

	/**
	 * Dynamic block: the front-end markup comes from render.php.
	 *
	 * `content` is declared with `source: "rich-text"` and no selector, so core
	 * stores it as a block ATTRIBUTE in the comment delimiter rather than as
	 * markup in post content. That is what makes returning null safe here —
	 * unlike a container block, there are no inner blocks to lose.
	 *
	 * @return {null} Nothing.
	 */
	save: () => null,
} );
