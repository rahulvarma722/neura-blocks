<?php
/**
 * The HTML tag vocabulary for text blocks.
 *
 * @package BlockKit
 */

namespace BlockKit;

defined( 'ABSPATH' ) || exit;

/**
 * Which tags a text block may use, and which are offered in the editor.
 *
 * TWO DIFFERENT QUESTIONS, DELIBERATELY SEPARATED.
 *
 * `all()` is what the RENDERER accepts. `enabled()` is what the EDITOR offers.
 * They are not the same list, and conflating them breaks existing content:
 * if a site disables `blockquote` after publishing posts that use it, those
 * posts must keep rendering as `blockquote`. Disabling a tag stops NEW uses; it
 * does not rewrite history.
 *
 * So render.php validates against all(), and only the dropdown is filtered.
 */
final class Text_Tags {

	/**
	 * Tags offered out of the box.
	 *
	 * Headings plus the three general-purpose containers. Deliberately short:
	 * a dropdown of forty tags is a worse experience than a dropdown of nine
	 * plus a setting, and most sites never need `bdo`.
	 *
	 * @var string[]
	 */
	const DEFAULT_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' );

	/**
	 * Additional tags a site can switch on.
	 *
	 * Grouped by what they are FOR, because that is how someone picking one
	 * thinks about it, and the group labels surface in the settings screen.
	 *
	 * Excluded on purpose: `pre` and `code` as block-level containers (core has
	 * dedicated blocks and their whitespace handling differs), and anything
	 * that is not text — no `img`, no `iframe`, no `video`, no form controls.
	 *
	 * @var array<string, string[]>
	 */
	const OPTIONAL_TAGS = array(
		'quotation'  => array( 'blockquote', 'q', 'cite' ),
		'annotation' => array( 'abbr', 'dfn', 'mark', 'small', 'time', 'address' ),
		'emphasis'   => array( 'strong', 'em', 's', 'del', 'ins', 'sub', 'sup' ),
		'caption'    => array( 'figcaption', 'caption', 'legend', 'label', 'summary' ),
		'list'       => array( 'li', 'dt', 'dd' ),
		'output'     => array( 'kbd', 'samp', 'var', 'output' ),
	);

	/**
	 * Tags that must never be offered or rendered.
	 *
	 * A saved post could name any string, and a filter could add one. This is
	 * the floor: no tag that can execute, load a resource, take input, or
	 * break out of the document structure, whatever the settings say.
	 *
	 * @var string[]
	 */
	const NEVER = array(
		'script',
		'style',
		'iframe',
		'object',
		'embed',
		'form',
		'input',
		'button',
		'select',
		'textarea',
		'link',
		'meta',
		'base',
		'html',
		'head',
		'body',
		'a',
		'img',
		'svg',
		'video',
		'audio',
		'canvas',
		'template',
		'slot',
	);

	/**
	 * Every tag the renderer will accept.
	 *
	 * @return string[]
	 */
	public static function all() {
		$tags = array_merge( self::DEFAULT_TAGS, ...array_values( self::OPTIONAL_TAGS ) );

		/**
		 * Filters the complete tag vocabulary.
		 *
		 * The extension point for a Pro add-on that wants tags this list does
		 * not have. Whatever is returned is still passed through the NEVER
		 * list below, so a filter cannot introduce `script`.
		 *
		 * @since 0.0.1
		 *
		 * @param string[] $tags Lowercase tag names.
		 */
		$tags = apply_filters( BLOCKKIT_SLUG . '_text_tags', $tags );

		return self::sanitize_list( $tags );
	}

	/**
	 * Tags the editor should offer on this site.
	 *
	 * @return string[]
	 */
	public static function enabled() {
		$stored = Settings::get( 'text_tags', array() );
		$extra  = is_array( $stored ) ? $stored : array();

		$tags = array_merge( self::DEFAULT_TAGS, $extra );

		/**
		 * Filters the tags offered in the editor.
		 *
		 * @since 0.0.1
		 *
		 * @param string[] $tags Lowercase tag names.
		 */
		$tags = apply_filters( BLOCKKIT_SLUG . '_enabled_text_tags', $tags );

		// Intersect with all(), so a stored value left behind by a deactivated
		// add-on cannot keep appearing after the tag stops being known.
		return array_values( array_intersect( self::sanitize_list( $tags ), self::all() ) );
	}

	/**
	 * Whether a tag is safe to render.
	 *
	 * @param string $tag Candidate tag name.
	 * @return bool
	 */
	public static function is_valid( $tag ) {
		return in_array( strtolower( (string) $tag ), self::all(), true );
	}

	/**
	 * The optional tags, grouped, for a settings screen.
	 *
	 * @return array<string, string[]>
	 */
	public static function optional_groups() {
		$groups = array();

		foreach ( self::OPTIONAL_TAGS as $group => $tags ) {
			$tags = array_values( array_intersect( self::sanitize_list( $tags ), self::all() ) );

			if ( ! empty( $tags ) ) {
				$groups[ $group ] = $tags;
			}
		}

		return $groups;
	}

	/**
	 * Reduces any list to lowercase, unique, well-formed, permitted tag names.
	 *
	 * @param mixed $tags Candidate list.
	 * @return string[]
	 */
	private static function sanitize_list( $tags ) {
		if ( ! is_array( $tags ) ) {
			return self::DEFAULT_TAGS;
		}

		$clean = array();

		foreach ( $tags as $tag ) {
			if ( ! is_string( $tag ) ) {
				continue;
			}

			$tag = strtolower( trim( $tag ) );

			// A tag name, and nothing that could be interpreted as markup.
			if ( 1 !== preg_match( '/^[a-z][a-z0-9]{0,14}$/', $tag ) ) {
				continue;
			}

			if ( in_array( $tag, self::NEVER, true ) ) {
				continue;
			}

			$clean[] = $tag;
		}

		return array_values( array_unique( $clean ) );
	}
}
