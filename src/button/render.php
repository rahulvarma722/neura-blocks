<?php
/**
 * Front-end markup for blockkit/button.
 *
 * The anchor IS the block root: get_block_wrapper_attributes() is spread
 * onto it, so every class and inline style core generates from the
 * block's `supports` lands on the element the user actually clicks.
 *
 * This differs deliberately from core/button, which nests
 * `.wp-block-button__link` inside a `.wp-block-button` div and moves the
 * styling to the inner link with __experimentalSkipSerialization. Core
 * needs the extra wrapper so a width setting can size the flex item
 * independently of the link. Until this block has a width control, the
 * wrapper would be an empty div that only splits the clickable area from
 * the padded area.
 *
 * @package BlockKit
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$text = isset( $attributes['text'] ) ? $attributes['text'] : '';

// An empty button is invisible on the front end but still occupies a
// flex slot, so render nothing at all.
if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
	return;
}

$url         = isset( $attributes['url'] ) ? trim( $attributes['url'] ) : '';
$link_target = isset( $attributes['linkTarget'] ) ? $attributes['linkTarget'] : '';
$rel         = isset( $attributes['rel'] ) ? trim( $attributes['rel'] ) : '';
$title       = isset( $attributes['title'] ) ? $attributes['title'] : '';
$tag_name    = isset( $attributes['tagName'] ) && 'button' === $attributes['tagName'] ? 'button' : 'a';

// Without a URL there is nothing to link to, so render a button element
// rather than an anchor with no href — which is not keyboard focusable.
if ( '' === $url ) {
	$tag_name = 'button';
}

$extra_attributes = array();

if ( 'a' === $tag_name ) {
	$extra_attributes['href'] = esc_url( $url );

	if ( '' !== $link_target ) {
		$extra_attributes['target'] = $link_target;

		/*
		 * target="_blank" without noopener lets the opened page reach
		 * back through window.opener. Modern browsers imply it, but the
		 * attribute is still the portable guarantee.
		 */
		if ( '_blank' === $link_target && '' === $rel ) {
			$rel = 'noreferrer noopener';
		}
	}

	if ( '' !== $rel ) {
		$extra_attributes['rel'] = $rel;
	}
} else {
	$extra_attributes['type'] = 'button';
}

if ( '' !== $title ) {
	$extra_attributes['title'] = $title;
}

$rendered_attributes = '';
foreach ( $extra_attributes as $name => $value ) {
	$rendered_attributes .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
}

printf(
	'<%1$s %2$s%3$s>%4$s</%1$s>',
	esc_attr( $tag_name ),
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	$rendered_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each value escaped with esc_attr()/esc_url() above.
	wp_kses_post( $text )
);
