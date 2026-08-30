/**
 * Text — shared constants.
 */

import { __ } from '@wordpress/i18n';

/**
 * Visual style presets, independent of the HTML tag.
 *
 * THE POINT OF THIS BLOCK. Core couples a heading's level to its appearance:
 * pick `h2` and you get h2's size. That forces a choice between a correct
 * document outline and the design you want, and the outline usually loses.
 *
 * Each preset resolves to a CSS custom property in style.scss, which prefers
 * the ACTIVE THEME's font-size preset and falls back to a sensible value. So a
 * theme with a proper type scale drives the look, and a theme without one still
 * renders something reasonable.
 *
 * `value` becomes a `has-style-<value>` class. Keep the values stable — they
 * are written into post content.
 */
export const STYLE_PRESETS = [
	{ value: '', label: __( 'Default (follow the tag)', 'neura-blocks' ) },
	{ value: 'display', label: __( 'Display', 'neura-blocks' ) },
	{ value: 'h1', label: __( 'Heading 1', 'neura-blocks' ) },
	{ value: 'h2', label: __( 'Heading 2', 'neura-blocks' ) },
	{ value: 'h3', label: __( 'Heading 3', 'neura-blocks' ) },
	{ value: 'h4', label: __( 'Heading 4', 'neura-blocks' ) },
	{ value: 'h5', label: __( 'Heading 5', 'neura-blocks' ) },
	{ value: 'h6', label: __( 'Heading 6', 'neura-blocks' ) },
	{ value: 'lead', label: __( 'Lead', 'neura-blocks' ) },
	{ value: 'body', label: __( 'Body', 'neura-blocks' ) },
	{ value: 'caption', label: __( 'Caption', 'neura-blocks' ) },
	{ value: 'eyebrow', label: __( 'Eyebrow', 'neura-blocks' ) },
];
