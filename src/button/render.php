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

/*
 * Per-viewport width, from `style.blockkit.width` and its `@tablet` / `@mobile`
 * states.
 *
 * Core writes CSS only for style paths it owns, so this namespaced value
 * survives save and parse but produces nothing by itself. The media queries
 * come from core (`WP_Theme_JSON::get_viewport_media_queries()`) so the bands
 * match every core control on this same block exactly — see
 * includes/class-blockkit-responsive-styles.php.
 *
 * A per-instance class scopes the rules. Two buttons on one page hold
 * different values, so the selector cannot be the block's shared class; and the
 * class is derived from the values themselves, so identical buttons collapse
 * onto one rule instead of emitting a near-duplicate per instance.
 */
$style_attribute  = isset( $attributes['style'] ) && is_array( $attributes['style'] ) ? $attributes['style'] : array();
$responsive_class = '';
$responsive_css   = '';

$width_signature = wp_json_encode(
	array(
		BlockKit_Responsive_Styles::get_state_value( $style_attribute, null, 'blockkit', 'width' ),
		BlockKit_Responsive_Styles::get_state_value( $style_attribute, '@tablet', 'blockkit', 'width' ),
		BlockKit_Responsive_Styles::get_state_value( $style_attribute, '@mobile', 'blockkit', 'width' ),
	)
);

if ( '["","",""]' !== $width_signature ) {
	$responsive_class = 'bk-btn-w-' . substr( md5( (string) $width_signature ), 0, 8 );
	$responsive_css   = BlockKit_Responsive_Styles::build_css(
		$style_attribute,
		'.' . $responsive_class,
		'blockkit',
		'width',
		'width'
	);
}

$wrapper_attributes = get_block_wrapper_attributes(
	'' !== $responsive_class ? array( 'class' => $responsive_class ) : array()
);

/*
 * The <style> tag precedes the element rather than being enqueued, so the rule
 * is present before first paint even when the block renders late (a widget, a
 * template part, a REST-rendered preview).
 *
 * NOT wp_strip_all_tags(). Core's media queries use CSS range syntax —
 * `@media (480px < width <= 782px)` — and strip_tags() reads that `<` as the
 * start of a tag and deletes everything after it, silently dropping every
 * per-viewport rule. Measured: the string above survives only as
 * `.bk{width:200px;}@media (480px < width`.
 *
 * Escaping is the wrong tool here anyway: esc_attr() would mangle the same
 * `<`, and no escaping makes `expression(…)` safe inside a declaration. So
 * every input is allow-listed instead — values by is_safe_length(), the
 * selector generated from an md5, the media query from core itself — and the
 * only thing left to guard is a literal `</` that could close the element
 * early. That cannot arise from any of those three sources; the check is here
 * so that stays true if a future caller passes something else.
 */
if ( '' !== $responsive_css && false === strpos( $responsive_css, '</' ) ) {
	printf( '<style>%s</style>', $responsive_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values allow-listed by BlockKit_Responsive_Styles::is_safe_length(); media queries from WP_Theme_JSON; selector is an md5-derived class.
}

printf(
	'<%1$s %2$s%3$s>%4$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	$rendered_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each value escaped with esc_attr()/esc_url() above.
	wp_kses_post( $text )
);
