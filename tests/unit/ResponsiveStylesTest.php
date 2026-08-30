<?php
/**
 * Unit tests for per-viewport CSS generation.
 *
 * @package NeuraBlocks
 */

namespace NeuraBlocks\Tests\Unit;

use NeuraBlocks\Responsive_Styles;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NeuraBlocks\Responsive_Styles
 */
final class ResponsiveStylesTest extends TestCase {

	/**
	 * A `style` attribute carrying values in all three layers.
	 *
	 * @return array
	 */
	private function style() {
		return array(
			'neura-blocks' => array( 'width' => '200px' ),
			'@tablet'  => array( 'neura-blocks' => array( 'width' => '150px' ) ),
			'@mobile'  => array( 'neura-blocks' => array( 'width' => '100%' ) ),
		);
	}

	/**
	 * null means the base layer, which is Desktop — not a third band.
	 *
	 * @return void
	 */
	public function test_get_state_value_reads_each_layer() {
		$style = $this->style();

		$this->assertSame( '200px', Responsive_Styles::get_state_value( $style, null, 'neura-blocks', 'width' ) );
		$this->assertSame( '150px', Responsive_Styles::get_state_value( $style, '@tablet', 'neura-blocks', 'width' ) );
		$this->assertSame( '100%', Responsive_Styles::get_state_value( $style, '@mobile', 'neura-blocks', 'width' ) );
	}

	/**
	 * Missing layers, keys and non-scalars all read as '' rather than warning.
	 *
	 * @return void
	 */
	public function test_get_state_value_is_total() {
		$this->assertSame( '', Responsive_Styles::get_state_value( array(), null, 'neura-blocks', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), '@print', 'neura-blocks', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), null, 'other', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), null, 'neura-blocks', 'height' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( 'not-an-array', null, 'neura-blocks', 'width' ) );
		$this->assertSame(
			'',
			Responsive_Styles::get_state_value(
				array( 'neura-blocks' => array( 'width' => array( 'nested' ) ) ),
				null,
				'neura-blocks',
				'width'
			),
			'a non-scalar value is not emitted'
		);
	}

	/**
	 * The allow-list accepts the units the control offers and nothing else.
	 *
	 * @return void
	 */
	public function test_is_safe_length_allow_list() {
		foreach ( array( '200px', '100%', '1.5em', '10rem', '50vw', '80vh', '.5em', '0px' ) as $ok ) {
			$this->assertTrue( Responsive_Styles::is_safe_length( $ok ), $ok . ' should be allowed' );
		}

		foreach (
			array(
				'expression(alert(1))',
				'200',
				'200 px',
				'calc(100% - 10px)',
				'200px;color:red',
				'url(x)',
				'',
				'auto',
				'200ch',
				'50px}</style><script>alert(1)</script>',
			) as $bad
		) {
			$this->assertFalse( Responsive_Styles::is_safe_length( $bad ), $bad . ' should be rejected' );
		}
	}

	/**
	 * Negatives are rejected unless the caller opts in.
	 *
	 * `width` cannot be negative in CSS, so emitting `-50px` only ever produced
	 * a declaration the browser discards.
	 *
	 * @return void
	 */
	public function test_negative_lengths_require_opt_in() {
		$this->assertFalse( Responsive_Styles::is_safe_length( '-50px' ) );
		$this->assertTrue( Responsive_Styles::is_safe_length( '-50px', true ) );
		$this->assertFalse( Responsive_Styles::is_safe_length( '-abc', true ), 'opting in does not relax the rest' );
	}

	/**
	 * The base layer carries no media query, so it applies at every width.
	 *
	 * @return void
	 */
	public function test_build_css_emits_base_unwrapped() {
		$css = Responsive_Styles::build_css(
			array( 'neura-blocks' => array( 'width' => '200px' ) ),
			'.t',
			'neura-blocks',
			'width',
			'width'
		);

		$this->assertSame( '.t{width:200px;}', $css );
		$this->assertStringNotContainsString( '@media', $css );
	}

	/**
	 * Unsafe values are dropped, and drop nothing else with them.
	 *
	 * @return void
	 */
	public function test_build_css_drops_unsafe_values_only() {
		$css = Responsive_Styles::build_css(
			array(
				'neura-blocks' => array( 'width' => 'expression(alert(1))' ),
				'@mobile'  => array( 'neura-blocks' => array( 'width' => '100%' ) ),
			),
			'.t',
			'neura-blocks',
			'width',
			'width'
		);

		$this->assertStringNotContainsString( 'expression', $css );
		$this->assertStringContainsString( '100%', $css, 'the valid mobile value survives' );
	}

	/**
	 * Nothing stored means nothing emitted — not an empty rule.
	 *
	 * @return void
	 */
	public function test_build_css_emits_nothing_when_unset() {
		$this->assertSame( '', Responsive_Styles::build_css( array(), '.t', 'neura-blocks', 'width', 'width' ) );
		$this->assertSame( '', Responsive_Styles::build_css( 'nope', '.t', 'neura-blocks', 'width', 'width' ) );
	}

	/**
	 * Any CSS property can be emitted, including a custom property — that is
	 * what makes the descendant (icon-size) case work.
	 *
	 * @return void
	 */
	public function test_build_css_emits_custom_properties() {
		$css = Responsive_Styles::build_css(
			array( 'neura-blocks' => array( 'iconSize' => '1.5em' ) ),
			'.t',
			'neura-blocks',
			'iconSize',
			'--neura-blocks-button-icon-size'
		);

		$this->assertSame( '.t{--neura-blocks-button-icon-size:1.5em;}', $css );
	}
}
