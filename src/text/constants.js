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

/**
 * Human labels for tag names.
 *
 * The vocabulary itself lives in PHP — includes/class-text-tags.php — because
 * the renderer has to validate against it and a site can extend it through a
 * setting. This is only the display text, so an unknown tag still renders in
 * the dropdown as its own name rather than disappearing.
 */
export const TAG_LABELS = {
	h1: __( 'Heading 1', 'blockkit' ),
	h2: __( 'Heading 2', 'blockkit' ),
	h3: __( 'Heading 3', 'blockkit' ),
	h4: __( 'Heading 4', 'blockkit' ),
	h5: __( 'Heading 5', 'blockkit' ),
	h6: __( 'Heading 6', 'blockkit' ),
	p: __( 'Paragraph', 'blockkit' ),
	span: __( 'Span (inline)', 'blockkit' ),
	div: __( 'Div', 'blockkit' ),
	blockquote: __( 'Blockquote', 'blockkit' ),
	q: __( 'Inline quote', 'blockkit' ),
	cite: __( 'Citation', 'blockkit' ),
	abbr: __( 'Abbreviation', 'blockkit' ),
	dfn: __( 'Definition', 'blockkit' ),
	mark: __( 'Highlight', 'blockkit' ),
	small: __( 'Small print', 'blockkit' ),
	time: __( 'Time', 'blockkit' ),
	address: __( 'Address', 'blockkit' ),
	strong: __( 'Strong', 'blockkit' ),
	em: __( 'Emphasis', 'blockkit' ),
	s: __( 'Strikethrough', 'blockkit' ),
	del: __( 'Deleted', 'blockkit' ),
	ins: __( 'Inserted', 'blockkit' ),
	sub: __( 'Subscript', 'blockkit' ),
	sup: __( 'Superscript', 'blockkit' ),
	figcaption: __( 'Figure caption', 'blockkit' ),
	caption: __( 'Table caption', 'blockkit' ),
	legend: __( 'Fieldset legend', 'blockkit' ),
	label: __( 'Label', 'blockkit' ),
	summary: __( 'Summary', 'blockkit' ),
	li: __( 'List item', 'blockkit' ),
	dt: __( 'Definition term', 'blockkit' ),
	dd: __( 'Definition description', 'blockkit' ),
	kbd: __( 'Keyboard input', 'blockkit' ),
	samp: __( 'Sample output', 'blockkit' ),
	var: __( 'Variable', 'blockkit' ),
	output: __( 'Output', 'blockkit' ),
};

/**
 * Tags offered when the localised list is unavailable.
 *
 * Mirrors Text_Tags::DEFAULT_TAGS. Only reachable if the editor script loads
 * without its localised data, which should not happen — but a dropdown with no
 * options is a worse failure than a short one.
 */
export const FALLBACK_TAGS = [
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	'p',
	'span',
	'div',
];

/**
 * Tags that are headings, for the outline guardrail.
 */
export const HEADING_TAGS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
