/**
 * Icon — block registration.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,

	/**
	 * Dynamic block: the front-end markup comes from render.php, which resolves
	 * the icon through core's wp_get_icon().
	 *
	 * Safe to return null because every attribute is a plain scalar stored in
	 * the block delimiter — none declares a `source`, so there is nothing that
	 * needs saved markup to parse from, and there are no inner blocks to lose.
	 * See docs/BLOCKS.md for both traps.
	 *
	 * @return {null} Nothing.
	 */
	save: () => null,
} );
