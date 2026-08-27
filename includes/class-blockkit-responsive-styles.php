<?php
/**
 * Turning per-viewport values stored in a block's `style` attribute into CSS.
 *
 * Core generates CSS only for style paths it owns, so a namespaced key such as
 * `style.blockkit.width` produces nothing on its own — the value round-trips
 * through save and parse untouched, and it is this class that emits it.
 *
 * The media queries are core's own. `WP_Theme_JSON::get_viewport_media_queries()`
 * is public and documented (`@since 7.1.0`), reads `settings.viewport` from
 * theme.json, and returns exactly the bands core uses for its own per-viewport
 * output:
 *
 *   @mobile   @media (width <= 480px)
 *   @tablet   @media (480px < width <= 782px)
 *
 * Note the shape of those bands. They are MUTUALLY EXCLUSIVE ranges, not
 * stacked `max-width` queries, which is what makes mobile fall back to the base
 * layer rather than to tablet. Reimplementing them with `max-width` would
 * silently reintroduce a tablet-into-mobile cascade and disagree with every
 * core control on the same block.
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emits scoped CSS for namespaced per-viewport style values.
 */
class BlockKit_Responsive_Styles {

	/**
	 * Breakpoints used when core cannot supply them.
	 *
	 * Only reachable below WordPress 7.1, where viewport style states do not
	 * exist. Kept identical to `WP_Theme_JSON::DEFAULT_VIEWPORT_BREAKPOINTS` so
	 * a site that later upgrades sees no shift in behaviour.
	 *
	 * @var array<string, string>
	 */
	const FALLBACK_BREAKPOINTS = array(
		'mobile' => '480px',
		'tablet' => '782px',
	);

	/**
	 * Media queries keyed by viewport state, from core where possible.
	 *
	 * @return array<string, string> e.g. `array( '@mobile' => '@media (…)' )`.
	 */
	public static function get_media_queries() {
		$viewport = null;

		if ( function_exists( 'wp_get_global_settings' ) ) {
			$settings = wp_get_global_settings( array( 'viewport' ) );

			/*
			 * `wp_get_global_settings()` returns the WHOLE settings tree when
			 * the requested path is absent, which is the normal case since few
			 * themes declare `settings.viewport`. Passing that tree through
			 * happens to survive core's sanitizer, but only by accident — so
			 * narrow it to the two keys that are actually breakpoints.
			 */
			$viewport = is_array( $settings )
				? array_intersect_key( $settings, array_flip( array( 'mobile', 'tablet' ) ) )
				: null;
		}

		if ( is_callable( array( '\WP_Theme_JSON', 'get_viewport_media_queries' ) ) ) {
			return (array) \WP_Theme_JSON::get_viewport_media_queries( $viewport );
		}

		// Pre-7.1: no viewport states in core, so there is no core output to
		// match and the classic form is the safer choice for old browsers.
		return array(
			'@mobile' => sprintf(
				'@media (max-width: %s)',
				self::FALLBACK_BREAKPOINTS['mobile']
			),
			'@tablet' => sprintf(
				'@media (max-width: %s)',
				self::FALLBACK_BREAKPOINTS['tablet']
			),
		);
	}

	/**
	 * Read one namespaced value out of a `style` attribute layer.
	 *
	 * @param array   $style     The block's `style` attribute.
	 * @param ?string $state_key `'@tablet'`, `'@mobile'`, or null for the base.
	 * @param string  $namespace_key Namespace key inside the layer.
	 * @param string  $property  Property key inside the namespace.
	 * @return string Value, or '' when unset.
	 */
	public static function get_state_value( $style, $state_key, $namespace_key, $property ) {
		$layer = null === $state_key
			? $style
			: ( isset( $style[ $state_key ] ) ? $style[ $state_key ] : null );

		if ( ! is_array( $layer ) || ! isset( $layer[ $namespace_key ][ $property ] ) ) {
			return '';
		}

		$value = $layer[ $namespace_key ][ $property ];

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Whether a value is a CSS length or percentage we are willing to print.
	 *
	 * The value reaches us from post content, so it is untrusted even though
	 * only an editor could have written it. An allow-list of
	 * `<number><unit>` is used rather than escaping, because escaping cannot
	 * make `expression(…)` or an unbalanced `)` safe inside a declaration.
	 *
	 * NEGATIVES ARE REJECTED BY DEFAULT. Both properties this currently serves
	 * — `width` and the icon-size custom property, which feeds `width`/`height`
	 * — are non-negative in CSS, so `-50px` is not a value a browser will honour
	 * and emitting it only produces a declaration that is silently discarded.
	 * Rejecting it here means the block drops the rule instead, which is the
	 * same outcome with none of the dead CSS.
	 *
	 * The flag exists because that is a property of the CALLER, not of this
	 * function: reused for `margin` or `text-indent`, where negatives are
	 * meaningful, it should be passed true.
	 *
	 * @param string $value          Candidate value.
	 * @param bool   $allow_negative Whether a leading `-` is acceptable.
	 * @return bool Whether it is safe to emit.
	 */
	public static function is_safe_length( $value, $allow_negative = false ) {
		$sign = $allow_negative ? '-?' : '';

		return 1 === preg_match(
			'/^' . $sign . '(?:\d+\.?\d*|\.\d+)(?:px|%|em|rem|vw|vh)$/',
			(string) $value
		);
	}

	/**
	 * Build scoped CSS for one property across the base and viewport states.
	 *
	 * @param array  $style     The block's `style` attribute.
	 * @param string $selector  CSS selector to scope every rule to.
	 * @param string $namespace_key Namespace key inside each layer.
	 * @param string $property  Key inside the namespace.
	 * @param string $css_prop  The CSS property to emit.
	 * @return string CSS, or '' when there is nothing to emit.
	 */
	public static function build_css( $style, $selector, $namespace_key, $property, $css_prop ) {
		if ( ! is_array( $style ) ) {
			return '';
		}

		$rules = '';

		// Base layer first: it carries no media query and therefore applies at
		// every width, which is what makes Desktop the base rather than a
		// third band.
		$base = self::get_state_value( $style, null, $namespace_key, $property );

		if ( '' !== $base && self::is_safe_length( $base ) ) {
			$rules .= sprintf( '%s{%s:%s;}', $selector, $css_prop, $base );
		}

		/*
		 * Then the states, in the order core returns them. Because the bands
		 * are mutually exclusive ranges there is no specificity race between
		 * them, so this order is for readability rather than correctness — but
		 * it keeps the output diffable against core's.
		 */
		foreach ( self::get_media_queries() as $state_key => $media_query ) {
			$value = self::get_state_value( $style, $state_key, $namespace_key, $property );

			if ( '' === $value || ! self::is_safe_length( $value ) ) {
				continue;
			}

			$rules .= sprintf(
				'%s{%s{%s:%s;}}',
				$media_query,
				$selector,
				$css_prop,
				$value
			);
		}

		return $rules;
	}
}
