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

/*
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * These variables are NOT global, despite what the sniff reports.
 *
 * `register_block_type_from_metadata()` wraps a block's render template in a
 * static closure and requires it from inside that closure —
 * `wp-includes/blocks.php:629-631`:
 *
 *     $settings['render_callback'] = static function ( $attributes, $content, $block ) use ( $template_path ) {
 *         ob_start();
 *         require $template_path;
 *         return ob_get_clean();
 *     };
 *
 * So every assignment below is function-scoped and disappears when the
 * callback returns. PHPCS is a static analyser: it sees a .php file whose
 * top level assigns variables and cannot know the file is only ever reached
 * through that closure, so it assumes global scope. Core's own block
 * templates trip the same sniff for the same reason.
 *
 * Prefixing these to `$blockkit_text`, `$blockkit_url` and so on would satisfy
 * the sniff while making the file harder to read and implying a lifetime these
 * variables do not have. Disabled at file level instead, since it applies to
 * every line rather than to any one of them.
 */

$text = isset( $attributes['text'] ) ? $attributes['text'] : '';

// An empty button is invisible on the front end but still occupies a
// flex slot, so render nothing at all.
if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
	return;
}

/*
 * SANITISE ON THE WAY IN, ESCAPE ON THE WAY OUT.
 *
 * Every value below comes out of post content, which means it was written by
 * someone with edit rights but is still untrusted at render time: post content
 * is long-lived, portable between sites, and reachable by anything that can
 * write a post. Escaping alone would make these values safe to PRINT while
 * still letting nonsense through — `target="totally-arbitrary"` is harmless to
 * a parser and meaningless to a browser. So each one is narrowed to the set of
 * values that actually mean something here, and the escaping is then left to
 * get_block_wrapper_attributes(), which esc_attr()s every value it is given
 * (wp-includes/class-wp-block-supports.php:265).
 */
$url = isset( $attributes['url'] ) ? trim( (string) $attributes['url'] ) : '';

/*
 * `target` has exactly four meaningful values. An allow-list rather than
 * esc_attr() alone, because an unrecognised target is treated by browsers as a
 * NAMED BROWSING CONTEXT — so a stray value silently opens the link in a new
 * window named after it and keeps reusing that window, which is neither what
 * the editor asked for nor something the user can see in the UI.
 */
$allowed_targets = array( '_blank', '_self', '_parent', '_top' );
$link_target     = isset( $attributes['linkTarget'] ) ? (string) $attributes['linkTarget'] : '';
$link_target     = in_array( $link_target, $allowed_targets, true ) ? $link_target : '';

/*
 * `rel` is a space-separated token list drawn from a FIXED vocabulary, so it
 * gets a real allow-list rather than a character filter.
 *
 * Stripping disallowed characters is not enough: `noopener"><script>` reduces
 * to `noopenerscriptalert1script`, which is safe to print but is a garbage
 * token that means nothing to a browser and tells a reader nothing about what
 * was intended. Matching whole tokens against the HTML link-type registry
 * keeps every value that has an effect and discards the rest entirely.
 */
$allowed_rel = array(
	'alternate',
	'author',
	'bookmark',
	'external',
	'help',
	'license',
	'me',
	'next',
	'nofollow',
	'noopener',
	'noreferrer',
	'opener',
	'prev',
	'privacy-policy',
	'search',
	'sponsored',
	'tag',
	'terms-of-service',
	'ugc',
);

$rel = isset( $attributes['rel'] ) ? strtolower( trim( (string) $attributes['rel'] ) ) : '';
$rel = '' === $rel
	? ''
	: implode(
		' ',
		array_unique(
			array_intersect(
				(array) preg_split( '/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY ),
				$allowed_rel
			)
		)
	);

/*
 * A plain-text tooltip: strip tags and control characters, keep the text.
 *
 * Named $link_title, not $title. `$title` is a real WordPress global — admin
 * pages and the template loader both use it — and assigning to it is a hard
 * error under WordPress.WP.GlobalVariablesOverride. Nothing is actually
 * clobbered here, because this file runs inside a closure and the assignment is
 * function-scoped, but a name that only stays safe because of where the file
 * happens to be required is not worth keeping.
 */
$link_title = isset( $attributes['title'] ) ? sanitize_text_field( (string) $attributes['title'] ) : '';

$tag_name = isset( $attributes['tagName'] ) && 'button' === $attributes['tagName'] ? 'button' : 'a';

/*
 * Escape FIRST, then decide the tag from the result.
 *
 * esc_url() does not just escape: it drops the value entirely when the scheme
 * is not allow-listed, so `javascript:alert(1)` comes back as ''. Testing the
 * RAW value for emptiness therefore passes a hostile URL straight through the
 * `a` branch and emits `<a href="">` — an anchor with no destination, which is
 * exactly the case the branch below exists to avoid. Testing the escaped value
 * closes that gap, and a stripped URL is treated the same as no URL at all.
 */
$safe_url = '' === $url ? '' : esc_url( $url );

// Without a usable URL there is nothing to link to, so render a button element
// rather than an anchor with no href — which is not keyboard focusable.
if ( '' === $safe_url ) {
	$tag_name = 'button';
}

$extra_attributes = array();

if ( 'a' === $tag_name ) {
	$extra_attributes['href'] = $safe_url;

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

if ( '' !== $link_title ) {
	$extra_attributes['title'] = $link_title;
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
	/*
	 * aria-hidden: the icon is decorative, the button text is the accessible
	 * name. No width/height attribute — the size comes from CSS so it can be
	 * per-viewport.
	 *
	 * esc_attr() on the path, and the SVG itself is a literal — NOT wp_kses().
	 *
	 * wp_kses() cannot express inline SVG. It lowercases every attribute name
	 * before matching, so `viewBox` becomes `viewbox` no matter what the
	 * allow-list says, and an allow-list written as `viewBox` matches nothing and
	 * drops the attribute outright. Verified both ways against this WordPress
	 * build.
	 *
	 * `viewbox` does survive in practice, because an HTML parser runs its "adjust
	 * SVG attributes" step and maps it back. But viewBox is the attribute that
	 * makes the icon scale, and leaning on parser error-correction to keep it
	 * working is a poor trade for defence-in-depth on a value that cannot vary:
	 * $icon_key had to match a key of the four-entry $icon_paths map above to get
	 * here, so the interpolated string is one of four literals in this file.
	 *
	 * esc_attr() on that value is therefore the whole of the escaping story, and
	 * the printf() below carries a phpcs:ignore because the sniff sees a variable
	 * rather than the escaping that produced it.
	 */
	$icon_markup = sprintf(
		'<svg class="wp-block-blockkit-button__icon" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
		esc_attr( $icon_paths[ $icon_key ] )
	);
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

	/*
	 * The class is derived from the STORED values, but the CSS is built only
	 * from values that pass is_safe_length(). Those two sets differ whenever a
	 * value is present but unusable, and the block then carried a scoping class
	 * with no rule anywhere to match it. Harmless to render, but it is dead
	 * markup that invites the question "what styles this?" — so drop the class
	 * when nothing was emitted for it.
	 */
	if ( '' === $responsive_css ) {
		$responsive_class = '';
	}
}

$wrapper_classes = array_filter(
	array(
		$responsive_class,
		// Mirrors the editor, so the icon sits on the same side in both.
		'' !== $icon_markup && 'left' === $icon_position ? 'has-icon-left' : '',
	)
);

/*
 * href / target / rel / title / type go through here too, rather than being
 * concatenated into a string of their own.
 *
 * get_block_wrapper_attributes() takes ARBITRARY extra attributes, merges them
 * with everything the block's `supports` generated, and esc_attr()s every value
 * on the way out (wp-includes/class-wp-block-supports.php:265). Handing them
 * over means one escaping path instead of two, and the printf() below no longer
 * needs a phpcs:ignore claiming an escape the sniff cannot see.
 *
 * `href` is still run through esc_url() first: esc_attr() escapes characters
 * but knows nothing about schemes, so it would happily print
 * `javascript:alert(1)` as a perfectly well-formed attribute. The two are not
 * interchangeable and the URL needs both.
 */
$wrapper_attributes = get_block_wrapper_attributes(
	array_merge(
		$wrapper_classes ? array( 'class' => implode( ' ', $wrapper_classes ) ) : array(),
		$extra_attributes
	)
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

/*
 * The label is filtered with wp_kses() against an explicit inline-formatting
 * list rather than wp_kses_post().
 *
 * wp_kses_post() is the right filter for POST CONTENT — it permits everything
 * a post body may legitimately contain, which includes <img>, <iframe> and
 * <a>. None of those belong in a button label, and a nested <a> inside this
 * element would be invalid HTML that browsers recover from unpredictably.
 * edit.js sets `allowedFormats={ [] }`, so the editor cannot produce any
 * markup here at all; this list is what survives if that is ever relaxed to
 * basic formatting, and nothing wider can slip through in the meantime.
 */
$allowed_label_html = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
	's'      => array(),
	'sub'    => array(),
	'sup'    => array(),
	'code'   => array(),
	'br'     => array(),
);

printf(
	'<%1$s %2$s><span class="wp-block-blockkit-button__text">%3$s</span>%4$s</%1$s>',
	esc_attr( $tag_name ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() applied per value by get_block_wrapper_attributes(); the string is attribute markup, so escaping it again would corrupt it.
	wp_kses( $text, $allowed_label_html ),
	$icon_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Literal SVG template; the only interpolated value is esc_attr()'d above and comes from a fixed four-entry map. See the note there for why wp_kses() cannot be used.
);
