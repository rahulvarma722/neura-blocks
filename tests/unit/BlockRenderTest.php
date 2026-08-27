<?php
/**
 * Unit tests for the shared render helpers.
 *
 * @package BlockKit
 */

namespace BlockKit\Tests\Unit;

use BlockKit\Block_Render;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlockKit\Block_Render
 */
final class BlockRenderTest extends TestCase {

	/**
	 * Absent, non-scalar and whitespace attributes all resolve predictably.
	 *
	 * @return void
	 */
	public function test_text_reads_and_trims() {
		$attributes = array(
			'label'  => '  spaced  ',
			'number' => 42,
			'array'  => array( 'nope' ),
			'null'   => null,
		);

		$this->assertSame( 'spaced', Block_Render::text( $attributes, 'label' ) );
		$this->assertSame( '42', Block_Render::text( $attributes, 'number' ), 'scalars are cast to string' );
		$this->assertSame( '', Block_Render::text( $attributes, 'array' ), 'arrays fall back' );
		$this->assertSame( '', Block_Render::text( $attributes, 'null' ), 'null falls back' );
		$this->assertSame( '', Block_Render::text( $attributes, 'missing' ) );
		$this->assertSame( 'x', Block_Render::text( $attributes, 'missing', 'x' ), 'fallback is honoured' );
		$this->assertSame( '', Block_Render::text( 'not-an-array', 'label' ) );
	}

	/**
	 * one_of() is strict — a loose comparison would let 0 == 'a' through.
	 *
	 * @return void
	 */
	public function test_one_of_is_strict() {
		$allowed = array( '_blank', '_self' );

		$this->assertSame( '_blank', Block_Render::one_of( '_blank', $allowed ) );
		$this->assertSame( '', Block_Render::one_of( 'evil" onmouseover="x', $allowed ) );
		$this->assertSame( 'a', Block_Render::one_of( 'nope', array( 'a', 'button' ), 'a' ) );
		$this->assertSame( '', Block_Render::one_of( 0, $allowed ), 'no loose comparison' );
	}

	/**
	 * Token filtering keeps whole tokens and discards the rest entirely.
	 *
	 * The injection case is the reason this is not a character filter:
	 * stripping disallowed characters leaves `noopenerscriptalert1script`,
	 * which is safe to print and completely meaningless.
	 *
	 * @return void
	 */
	public function test_tokens_matches_whole_tokens() {
		$allowed = array( 'noopener', 'noreferrer', 'nofollow' );

		$this->assertSame( 'noopener noreferrer', Block_Render::tokens( 'noopener noreferrer', $allowed ) );
		$this->assertSame( 'noopener', Block_Render::tokens( 'NOOPENER', $allowed ), 'case-insensitive' );
		$this->assertSame( 'noopener', Block_Render::tokens( "noopener\t\n  nofollow-ish", $allowed ) );
		$this->assertSame( 'noopener', Block_Render::tokens( 'noopener noopener', $allowed ), 'de-duplicated' );
		$this->assertSame( '', Block_Render::tokens( 'noopener"><script>alert(1)</script>', $allowed ) );
		$this->assertSame( '', Block_Render::tokens( '', $allowed ) );
		$this->assertSame( '', Block_Render::tokens( '   ', $allowed ) );
	}

	/**
	 * style_tag() emits nothing for empty input and refuses a `</` breakout.
	 *
	 * @return void
	 */
	public function test_style_tag_guards_element_breakout() {
		$this->assertSame( '<style>.a{width:1px;}</style>', Block_Render::style_tag( '.a{width:1px;}' ) );
		$this->assertSame( '', Block_Render::style_tag( '' ) );
		$this->assertSame( '', Block_Render::style_tag( '.a{}</style><script>alert(1)</script>' ) );
	}

	/**
	 * responsive() returns a class and CSS together, or neither.
	 *
	 * Deriving them separately is how they drift: a class emitted with no
	 * matching rule, or a rule scoped to a class the block does not carry.
	 *
	 * @return void
	 */
	public function test_responsive_pairs_class_with_css() {
		$result = Block_Render::responsive(
			array(
				'blockkit' => array( 'width' => '200px' ),
				'@mobile'  => array( 'blockkit' => array( 'width' => '100%' ) ),
			),
			'blockkit',
			array( 'width' => 'width' ),
			'bk-btn-'
		);

		$this->assertStringStartsWith( 'bk-btn-', $result['class'] );
		$this->assertSame( 8, strlen( $result['class'] ) - strlen( 'bk-btn-' ), 'class carries an 8-char hash' );
		$this->assertStringContainsString( '.' . $result['class'], $result['css'], 'CSS is scoped to the class' );
		$this->assertStringContainsString( '200px', $result['css'] );
		$this->assertStringContainsString( '100%', $result['css'] );
	}

	/**
	 * Identical values collapse onto one class, so two blocks sharing a value
	 * do not emit near-duplicate rules.
	 *
	 * @return void
	 */
	public function test_responsive_hash_is_value_derived() {
		$style = array( 'blockkit' => array( 'width' => '200px' ) );
		$props = array( 'width' => 'width' );

		$a = Block_Render::responsive( $style, 'blockkit', $props, 'bk-' );
		$b = Block_Render::responsive( $style, 'blockkit', $props, 'bk-' );
		$c = Block_Render::responsive( array( 'blockkit' => array( 'width' => '201px' ) ), 'blockkit', $props, 'bk-' );

		$this->assertSame( $a['class'], $b['class'], 'same values, same class' );
		$this->assertNotSame( $a['class'], $c['class'], 'different values, different class' );
	}

	/**
	 * A value that is present but unusable must yield NO class.
	 *
	 * Otherwise the block carries a scoping class with no rule anywhere to
	 * match it — dead markup that invites "what styles this?".
	 *
	 * @return void
	 */
	public function test_responsive_drops_class_when_no_css_survives() {
		$result = Block_Render::responsive(
			array( 'blockkit' => array( 'width' => 'expression(alert(1))' ) ),
			'blockkit',
			array( 'width' => 'width' ),
			'bk-'
		);

		$this->assertSame( '', $result['class'] );
		$this->assertSame( '', $result['css'] );
	}

	/**
	 * Nothing stored, nothing emitted.
	 *
	 * @return void
	 */
	public function test_responsive_is_empty_when_unset() {
		foreach ( array( array(), 'nope', null ) as $style ) {
			$result = Block_Render::responsive( $style, 'blockkit', array( 'width' => 'width' ), 'bk-' );
			$this->assertSame( array( 'class' => '', 'css' => '' ), $result );
		}
	}

	/**
	 * CSS range syntax must survive — this is the bug that motivated the
	 * comment in style_tag(). `wp_strip_all_tags()` would truncate at the `<`.
	 *
	 * @return void
	 */
	public function test_style_tag_preserves_css_range_syntax() {
		$css = '@media (480px < width <= 782px){.a{width:2px;}}';

		$this->assertSame( '<style>' . $css . '</style>', Block_Render::style_tag( $css ) );
		$this->assertStringContainsString( '480px < width <= 782px', Block_Render::style_tag( $css ) );
	}
}
