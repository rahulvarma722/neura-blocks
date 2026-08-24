/**
 * Buttons — block registration.
 *
 * The name comes from block.json rather than a literal, so the namespace
 * lives in exactly one place per block.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,

	/**
	 * Dynamic block: nothing is written to post content. The front-end
	 * markup comes from render.php.
	 */
	save: () => null,
} );
