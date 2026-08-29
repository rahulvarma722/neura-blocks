<?php
/**
 * Unit tests for per-viewport CSS generation.
 *
 * @package BlockKit
 */

namespace BlockKit\Tests\Unit;

use BlockKit\Responsive_Styles;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlockKit\Responsive_Styles
 */
final class ResponsiveStylesTest extends TestCase {

	/**
	 * A `style` attribute carrying values in all three layers.
	 *
	 * @return array
	 */
	private function style() {
		return array(
			'blockkit' => array( 'width' => '200px' ),
			'@tablet'  => array( 'blockkit' => array( 'width' => '150px' ) ),
			'@mobile'  => array( 'blockkit' => array( 'width' => '100%' ) ),
		);
	}

	/**
	 * null means the base layer, which is Desktop — not a third band.
	 *
	 * @return void
	 */
	public function test_get_state_value_reads_each_layer() {
		$style = $this->style();

		$this->assertSame( '200px', Responsive_Styles::get_state_value( $style, null, 'blockkit', 'width' ) );
		$this->assertSame( '150px', Responsive_Styles::get_state_value( $style, '@tablet', 'blockkit', 'width' ) );
		$this->assertSame( '100%', Responsive_Styles::get_state_value( $style, '@mobile', 'blockkit', 'width' ) );
	}

	/**
	 * Missing layers, keys and non-scalars all read as '' rather than warning.
	 *
	 * @return void
	 */
	public function test_get_state_value_is_total() {
		$this->assertSame( '', Responsive_Styles::get_state_value( array(), null, 'blockkit', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), '@print', 'blockkit', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), null, 'other', 'width' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( $this->style(), null, 'blockkit', 'height' ) );
		$this->assertSame( '', Responsive_Styles::get_state_value( 'not-an-array', null, 'blockkit', 'width' ) );
		$this->assertSame(
			'',
			Responsive_Styles::get_state_value(
				array( 'blockkit' => array( 'width' => array( 'nested' ) ) ),
				null,
				'blockkit',
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
			array( 'blockkit' => array( 'width' => '200px' ) ),
			'.t',
			'blockkit',
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
				'blockkit' => array( 'width' => 'expression(alert(1))' ),
				'@mobile'  => array( 'blockkit' => array( 'width' => '100%' ) ),
			),
			'.t',
			'blockkit',
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
		$this->assertSame( '', Responsive_Styles::build_css( array(), '.t', 'blockkit', 'width', 'width' ) );
		$this->assertSame( '', Responsive_Styles::build_css( 'nope', '.t', 'blockkit', 'width', 'width' ) );
	}

	/**
	 * Any CSS property can be emitted, including a custom property — that is
	 * what makes the descendant (icon-size) case work.
	 *
	 * @return void
	 */
	public function test_build_css_emits_custom_properties() {
		$css = Responsive_Styles::build_css(
			array( 'blockkit' => array( 'iconSize' => '1.5em' ) ),
			'.t',
			'blockkit',
			'iconSize',
			'--bk-button-icon-size'
		);

		$this->assertSame( '.t{--bk-button-icon-size:1.5em;}', $css );
	}
}
