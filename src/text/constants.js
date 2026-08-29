/**
 * Kit Text — shared constants.
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
	{ value: '', label: __( 'Default (follow the tag)', 'blockkit' ) },
	{ value: 'display', label: __( 'Display', 'blockkit' ) },
	{ value: 'h1', label: __( 'Heading 1', 'blockkit' ) },
	{ value: 'h2', label: __( 'Heading 2', 'blockkit' ) },
	{ value: 'h3', label: __( 'Heading 3', 'blockkit' ) },
	{ value: 'h4', label: __( 'Heading 4', 'blockkit' ) },
	{ value: 'h5', label: __( 'Heading 5', 'blockkit' ) },
	{ value: 'h6', label: __( 'Heading 6', 'blockkit' ) },
	{ value: 'lead', label: __( 'Lead', 'blockkit' ) },
	{ value: 'body', label: __( 'Body', 'blockkit' ) },
	{ value: 'caption', label: __( 'Caption', 'blockkit' ) },
	{ value: 'eyebrow', label: __( 'Eyebrow', 'blockkit' ) },
];
