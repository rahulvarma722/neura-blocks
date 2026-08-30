<?php
/**
 * Shared helpers for block render templates.
 *
 * @package NeuraBlocks
 */

namespace NeuraBlocks\Block;

use NeuraBlocks\Responsive_Styles;

defined( 'ABSPATH' ) || exit;

/**
 * The parts of a render template that every block repeats.
 *
 * Why this exists: `button/render.php` reached 408 lines with 23 escaping call
 * sites, and roughly half of that was not about buttons at all: reading an
 * attribute with a default, narrowing a value to an allow-list, deriving a
 * per-instance class from stored values, deciding whether a `<style>` block is
 * safe to print. At two blocks that is tolerable duplication. At twenty it is
 * twenty places for the same sanitising bug to hide, and twenty places to fix
 * it.
 *
 * Extracted at block three rather than block ten, deliberately: the shape of
 * the duplication is already clear, and the cost of moving it later grows with
 * every block added.
 *
 * All static, and that is correct here. Unlike the feature modules, these are
 * pure functions — same input, same output, no state, no hooks, nothing to
 * inject or fake. Making them instance methods would add ceremony and buy
 * nothing; see the note in interface-module.php for where instances DO earn
 * their keep.
 */
final class Render {

	/**
	 * Reads one attribute as a trimmed string.
	 *
	 * Every render template opens with a run of
	 * `isset( $attributes['x'] ) ? $attributes['x'] : ''`, which is noise that
	 * hides the one line among them that does something different.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $key        Attribute name.
	 * @param string $fallback   Value when absent or not scalar.
	 * @return string
	 */
	public static function text( $attributes, $key, $fallback = '' ) {
		if ( ! is_array( $attributes ) || ! isset( $attributes[ $key ] ) ) {
			return $fallback;
		}

		$value = $attributes[ $key ];

		return is_scalar( $value ) ? trim( (string) $value ) : $fallback;
	}

	/**
	 * Narrows a value to an allow-list.
	 *
	 * An allow-list rather than escaping wherever the set of meaningful values
	 * is finite and known. Escaping makes a value safe to PRINT; it does
	 * nothing about a value that is safe but meaningless, and those cause
	 * bugs that look like CSS or browser problems rather than data problems.
	 *
	 * @param string   $value    Candidate.
	 * @param string[] $allowed  Permitted values.
	 * @param string   $fallback Returned when $value is not permitted.
	 * @return string
	 */
	public static function one_of( $value, $allowed, $fallback = '' ) {
		return in_array( $value, (array) $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Filters a space-separated token list against an allow-list.
	 *
	 * Whole tokens, not characters. Stripping disallowed characters is not
	 * enough: `noopener"><script>alert(1)</script>` reduces to
	 * `noopenerscriptalert1script`, which is perfectly safe to print and
	 * completely meaningless to a browser.
	 *
	 * @param string   $value   Space-separated tokens.
	 * @param string[] $allowed Permitted tokens, lowercase.
	 * @return string Surviving tokens, space-separated and de-duplicated.
	 */
	public static function tokens( $value, $allowed ) {
		$value = strtolower( trim( (string) $value ) );

		if ( '' === $value ) {
			return '';
		}

		$found = (array) preg_split( '/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY );

		return implode( ' ', array_unique( array_intersect( $found, (array) $allowed ) ) );
	}

	/**
	 * Escapes a URL, returning '' when it is not usable.
	 *
	 * The return value is the thing to branch on, not the input. esc_url()
	 * does not merely escape — it DROPS the value when the scheme is not
	 * allow-listed, so `javascript:alert(1)` comes back as ''. Testing the raw
	 * value for emptiness instead lets a hostile URL through and emits
	 * `<a href="">`: an anchor with no destination, which is not keyboard
	 * focusable and is exactly what such a branch usually exists to avoid.
	 *
	 * @param string $url Raw URL from block attributes.
	 * @return string Escaped URL, or '' when unusable.
	 */
	public static function url( $url ) {
		$url = trim( (string) $url );

		return '' === $url ? '' : esc_url( $url );
	}

	/**
	 * Builds per-instance CSS for namespaced per-viewport style values.
	 *
	 * Returns the scoping class and the CSS together, because neither is
	 * useful alone and deriving them separately is how they drift: a class
	 * emitted with no matching rule, or a rule scoped to a class the block
	 * does not carry.
	 *
	 * The class is derived from the VALUES, so two blocks holding identical
	 * values collapse onto one rule rather than emitting a near-duplicate
	 * each. It is dropped entirely when nothing passed validation, so the
	 * block never carries a class nothing matches.
	 *
	 * @param array  $style       The block's `style` attribute.
	 * @param string $namespace_key   Namespace key inside each layer.
	 * @param array  $properties  Map of style key => CSS property to emit.
	 * @param string $class_prefix Prefix for the generated class name.
	 * @return array{class: string, css: string}
	 */
	public static function responsive( $style, $namespace_key, $properties, $class_prefix ) {
		$empty = array(
			'class' => '',
			'css'   => '',
		);

		if ( ! is_array( $style ) || empty( $properties ) ) {
			return $empty;
		}

		// Every stored value across every state, in a stable order, so the
		// signature is deterministic for a given set of values.
		$stored = array();

		foreach ( array_keys( $properties ) as $property_key ) {
			foreach ( array( null, '@tablet', '@mobile' ) as $state_key ) {
				$stored[] = Responsive_Styles::get_state_value( $style, $state_key, $namespace_key, $property_key );
			}
		}

		if ( '' === implode( '', $stored ) ) {
			return $empty;
		}

		$class = $class_prefix . substr( md5( (string) wp_json_encode( $stored ) ), 0, 8 );
		$css   = '';

		foreach ( $properties as $property_key => $css_property ) {
			$css .= Responsive_Styles::build_css( $style, '.' . $class, $namespace_key, $property_key, $css_property );
		}

		// Values that failed is_safe_length() produce no CSS. Without this the
		// block would carry a scoping class with no rule anywhere to match it.
		if ( '' === $css ) {
			return $empty;
		}

		return array(
			'class' => $class,
			'css'   => $css,
		);
	}

	/**
	 * Inline formatting permitted in a short user-facing label.
	 *
	 * A CONSTANT rather than a `label()` wrapper, deliberately. Wrapping
	 * wp_kses() in a helper hides the escaping from PHPCS — the sniff sees a
	 * static method call and reports unescaped output — which would trade one
	 * duplicated-escaping problem for a phpcs:ignore at every call site. It
	 * also makes a reviewer take the escaping on trust.
	 *
	 * Sharing the ALLOW-LIST instead gives the same single source of truth
	 * while `wp_kses()` stays visible where the output happens:
	 *
	 *     wp_kses( $text, Block_Render::LABEL_HTML )
	 *
	 * Note what is absent. wp_kses_post() is the filter for post BODIES, so it
	 * permits <img>, <iframe> and <a> — none of which belong in a button or
	 * heading label, and a nested <a> inside a link is invalid HTML that
	 * browsers recover from unpredictably.
	 *
	 * A block whose RichText sets `allowedFormats={ [] }` cannot produce any
	 * markup here at all; this list is what survives if that is ever relaxed to
	 * basic formatting, and nothing wider can slip through in the meantime.
	 *
	 * @var array<string, array>
	 */
	const LABEL_HTML = array(
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

	/**
	 * Wraps generated CSS in a `<style>` element, or returns ''.
	 *
	 * Inline rather than enqueued so the rule is present before first paint
	 * even when the block renders late — a widget, a template part, a
	 * REST-rendered preview.
	 *
	 * NOT escaped, and not passed through wp_strip_all_tags(). Core's media
	 * queries use CSS range syntax — `@media (480px < width <= 782px)` — and
	 * strip_tags() reads that `<` as the start of a tag and deletes everything
	 * after it; measured, the string survives only as
	 * `.bk{width:200px;}@media (480px < width`. esc_attr() mangles the same
	 * `<`, and no escaping makes `expression(…)` safe inside a declaration
	 * anyway.
	 *
	 * So the inputs are allow-listed at source instead — values by
	 * Responsive_Styles::is_safe_length(), the selector from an md5, the media
	 * query from core — and the only thing left to guard is a literal `</`
	 * that could close the element early. That cannot arise from any of those
	 * three sources; the check is here so it stays true if a future caller
	 * passes something else.
	 *
	 * @param string $css Generated CSS.
	 * @return string A `<style>` element, or '' when there is nothing safe to emit.
	 */
	public static function style_tag( $css ) {
		$css = (string) $css;

		if ( '' === $css || false !== strpos( $css, '</' ) ) {
			return '';
		}

		return '<style>' . $css . '</style>';
	}
}
