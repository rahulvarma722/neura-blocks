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

/*
 * The PHP twin of src/button/icon.js. Kept in step by hand: the editor renders
 * from JS and the front end from here, so a key added in one has to be added in
 * the other or the icon silently disappears on save.
 */
$icon_paths = array(
	'arrow'    => 'M4 10h9.2l-3.6-3.6L11 5l6 5-6 5-1.4-1.4L13.2 10H4z',
	'chevron'  => 'M7.5 4.5L13 10l-5.5 5.5L6 14l4-4-4-4z',
	'download' => 'M9 3h2v7h3l-4 5-4-5h3zM4 16h12v2H4z',
	'external' => 'M11 3h6v6h-2V6.4l-6.3 6.3-1.4-1.4L13.6 5H11zM4 6h4v2H6v6h6v-2h2v4H4z',
);

$icon_key      = isset( $attributes['icon'] ) ? (string) $attributes['icon'] : '';
$icon_position = isset( $attributes['iconPosition'] ) && 'left' === $attributes['iconPosition'] ? 'left' : 'right';
$icon_markup   = '';

if ( '' !== $icon_key && isset( $icon_paths[ $icon_key ] ) ) {
	// aria-hidden: the icon is decorative, the button text is the accessible
	// name. No width/height attribute — the size comes from CSS so it can be
	// per-viewport.
	$icon_markup = sprintf(
		'<svg class="wp-block-blockkit-button__icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
		esc_attr( $icon_paths[ $icon_key ] )
	);
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

/*
 * Two properties, two very different destinations.
 *
 * `width` is emitted as a real declaration on the block root. `iconSize` is
 * emitted as a CUSTOM PROPERTY on that same root, because the element it sizes
 * is a CHILD and nothing here can select a child: `get_block_wrapper_attributes()`
 * writes to the root, and so does core's own per-viewport CSS unless the block
 * declares feature selectors in block.json.
 *
 * style.scss then spends the variable on `.wp-block-blockkit-button__icon`. The
 * media queries stay on the root, so the icon becomes per-viewport without a
 * single descendant selector being generated here.
 */
$style_properties = array(
	// state key => CSS property emitted.
	'width'    => 'width',
	'iconSize' => '--bk-button-icon-size',
);

$stored_values = array();

foreach ( array_keys( $style_properties ) as $property_key ) {
	foreach ( array( null, '@tablet', '@mobile' ) as $state_key ) {
		$stored_values[] = BlockKit_Responsive_Styles::get_state_value(
			$style_attribute,
			$state_key,
			'blockkit',
			$property_key
		);
	}
}

$width_signature = wp_json_encode( $stored_values );

if ( '' !== implode( '', $stored_values ) ) {
	$responsive_class = 'bk-btn-' . substr( md5( (string) $width_signature ), 0, 8 );

	foreach ( $style_properties as $property_key => $css_property ) {
		$responsive_css .= BlockKit_Responsive_Styles::build_css(
			$style_attribute,
			'.' . $responsive_class,
			'blockkit',
			$property_key,
			$css_property
		);
	}
}

$wrapper_classes = array_filter(
	array(
		$responsive_class,
		// Mirrors the editor, so the icon sits on the same side in both.
		'' !== $icon_markup && 'left' === $icon_position ? 'has-icon-left' : '',
	)
);

$wrapper_attributes = get_block_wrapper_attributes(
	$wrapper_classes ? array( 'class' => implode( ' ', $wrapper_classes ) ) : array()
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
	'<%1$s %2$s%3$s><span class="wp-block-blockkit-button__text">%4$s</span>%5$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes().
	$rendered_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each value escaped with esc_attr()/esc_url() above.
	wp_kses_post( $text ),
	$icon_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from a fixed path map, with esc_attr() on the value.
);
