<?php
/**
 * Front-end markup for blockkit/text.
 *
 * @package BlockKit
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

/*
 * A file-level `use` in a file with no `namespace` declaration.
 *
 * `use` is lexical and per-file: it aliases for THIS file regardless of the
 * scope the file is required from. Core requires render templates from inside a
 * closure in wp-includes/blocks.php, which is global scope, and the alias still
 * applies here.
 */
use BlockKit\Block_Render;
use BlockKit\Text_Tags;

/*
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * These variables are not global. register_block_type_from_metadata() wraps this
 * template in a static closure and requires it from inside that closure
 * (wp-includes/blocks.php:629-631), so every assignment below is
 * function-scoped. PHPCS sees a file whose top level assigns variables and
 * cannot know that; core's own block templates trip the same sniff.
 */

$text = Block_Render::text( $attributes, 'content' );

// Empty text would render an empty element that still takes vertical space and
// still announces itself to a screen reader.
if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
	return;
}

/*
 * THE TAG IS VALIDATED AGAINST all(), NOT enabled().
 *
 * Those are different lists on purpose. `enabled()` is what the editor OFFERS;
 * `all()` is what the renderer ACCEPTS. If a site switches `blockquote` off
 * after publishing posts that use it, those posts must keep rendering as
 * blockquote — turning a tag off stops new uses, it does not rewrite existing
 * content into something else.
 *
 * Text_Tags::all() applies its own never-permitted list, so no filter or stored
 * value can get `script` or `iframe` through here.
 */
$tag_name = Block_Render::text( $attributes, 'tagName', 'p' );
$tag_name = Text_Tags::is_valid( $tag_name ) ? strtolower( $tag_name ) : 'p';

/*
 * The visual preset is emitted as a CLASS, never an inline style.
 *
 * style.scss resolves each preset against the active theme's font-size presets
 * with a fallback, so a theme with a real type scale drives the look and a theme
 * without one still renders sensibly. An inline font-size would beat the theme
 * forever and could not be overridden at all.
 */
$style_as = Block_Render::one_of(
	Block_Render::text( $attributes, 'styleAs' ),
	array( 'display', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'lead', 'body', 'caption', 'eyebrow' )
);

$classes = array_filter(
	array(
		'' !== $style_as ? 'has-style-' . $style_as : '',
	)
);

$wrapper_attributes = get_block_wrapper_attributes(
	$classes ? array( 'class' => implode( ' ', $classes ) ) : array()
);

printf(
	'<%1$s %2$s>%3$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() applied per value by get_block_wrapper_attributes(); the string is attribute markup, so escaping it again would corrupt it.
	wp_kses_post( $text )
);
