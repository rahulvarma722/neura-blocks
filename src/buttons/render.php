<?php
/**
 * Front-end markup for blockkit/buttons.
 *
 * A container: the only job is to emit the wrapper and print whatever
 * the inner blocks rendered. Layout classes (the flex container and its
 * blockGap) are added by wp_render_layout_support_flag() on the
 * render_block filter, after this file runs — there is nothing to do
 * here for them.
 *
 * @package BlockKit
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// An empty container would render as a stray, invisible wrapper.
if ( '' === trim( (string) $content ) ) {
	return;
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks are rendered and escaped by their own render callbacks.
);
