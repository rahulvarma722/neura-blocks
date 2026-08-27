<?php
/**
 * Unit tests for the text tag vocabulary.
 *
 * @package BlockKit
 */

namespace BlockKit\Tests\Unit;

use BlockKit\Text_Tags;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlockKit\Text_Tags
 */
final class TextTagsTest extends TestCase {

	/**
	 * The default set is headings plus the general-purpose containers.
	 *
	 * @return void
	 */
	public function test_defaults_are_headings_and_containers() {
		$this->assertSame(
			array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ),
			Text_Tags::DEFAULT_TAGS
		);
	}

	/**
	 * all() is a superset of the defaults and includes the optional groups.
	 *
	 * @return void
	 */
	public function test_all_includes_defaults_and_optionals() {
		$all = Text_Tags::all();

		foreach ( Text_Tags::DEFAULT_TAGS as $tag ) {
			$this->assertContains( $tag, $all );
		}

		$this->assertContains( 'blockquote', $all );
		$this->assertContains( 'figcaption', $all );
		$this->assertContains( 'sup', $all );
	}

	/**
	 * Nothing dangerous is in the vocabulary, whatever else changes.
	 *
	 * This is the assertion that must never be relaxed: the tag name goes
	 * straight into an element position in the markup, so an executable or
	 * resource-loading tag here is an XSS vector rather than a styling bug.
	 *
	 * @return void
	 */
	public function test_never_permitted_tags_are_absent() {
		$all = Text_Tags::all();

		foreach ( Text_Tags::NEVER as $forbidden ) {
			$this->assertNotContains( $forbidden, $all, $forbidden . ' must never be a permitted tag' );
			$this->assertFalse( Text_Tags::is_valid( $forbidden ), $forbidden . ' must not validate' );
		}
	}

	/**
	 * Specifically the ones an attacker would reach for first.
	 *
	 * @return void
	 */
	public function test_obvious_attack_tags_are_rejected() {
		foreach ( array( 'script', 'iframe', 'img', 'a', 'style', 'form', 'object', 'embed', 'svg' ) as $tag ) {
			$this->assertFalse( Text_Tags::is_valid( $tag ) );
			$this->assertFalse( Text_Tags::is_valid( strtoupper( $tag ) ), 'case must not bypass the check' );
		}
	}

	/**
	 * Malformed names cannot slip through.
	 *
	 * @return void
	 */
	public function test_malformed_names_are_rejected() {
		foreach (
			array(
				'h2 onload=alert(1)',
				'h2><script>alert(1)</script><h2',
				'',
				'   ',
				'123',
				'-h2',
				'h2/',
				'h2:hover',
				str_repeat( 'a', 200 ),
			) as $bad
		) {
			$this->assertFalse( Text_Tags::is_valid( $bad ), var_export( $bad, true ) . ' must be rejected' );
		}
	}

	/**
	 * is_valid() is case-insensitive for legitimate tags.
	 *
	 * A saved post could carry `H2` from a paste or a migration.
	 *
	 * @return void
	 */
	public function test_is_valid_normalises_case() {
		$this->assertTrue( Text_Tags::is_valid( 'H2' ) );
		$this->assertTrue( Text_Tags::is_valid( 'BlockQuote' ) );
	}

	/**
	 * Optional groups are non-empty and contain only permitted tags.
	 *
	 * @return void
	 */
	public function test_optional_groups_are_well_formed() {
		$groups = Text_Tags::optional_groups();

		$this->assertNotEmpty( $groups );

		foreach ( $groups as $group => $tags ) {
			$this->assertIsString( $group );
			$this->assertNotEmpty( $tags, $group . ' should not be an empty group' );

			foreach ( $tags as $tag ) {
				$this->assertTrue( Text_Tags::is_valid( $tag ) );
				$this->assertNotContains( $tag, Text_Tags::DEFAULT_TAGS, 'optional tags must not duplicate defaults' );
			}
		}
	}
}
