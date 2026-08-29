<?php
/**
 * Unit tests for class/interface/trait resolution.
 *
 * @package BlockKit
 */

namespace BlockKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * @covers \BlockKit\Autoloader
 */
final class AutoloaderTest extends TestCase {

	/**
	 * Classes resolve to class-*.php.
	 *
	 * @return void
	 */
	public function test_resolves_classes() {
		$this->assertTrue( class_exists( 'BlockKit\\Block\\Registrar' ), 'sub-namespace resolves to includes/block/' );
		$this->assertTrue( class_exists( 'BlockKit\\Block\\Render' ) );
		$this->assertTrue( class_exists( 'BlockKit\\Responsive_Styles' ), 'underscores become hyphens' );
		$this->assertTrue( class_exists( 'BlockKit\\Responsive_Styles' ) );
		$this->assertTrue( class_exists( 'BlockKit\\Helper' ) );
		$this->assertTrue( class_exists( 'BlockKit\\Plugin' ) );
	}

	/**
	 * Interfaces resolve to interface-*.php.
	 *
	 * PHP gives an autoloader no way to know which kind it was asked for, so
	 * this is the test that the prefix fallback actually works — without it,
	 * BlockKit\Module would be looked for as class-module.php and never found.
	 *
	 * @return void
	 */
	public function test_resolves_interfaces() {
		$this->assertTrue( interface_exists( 'BlockKit\\Module' ) );
	}

	/**
	 * An unknown name must not fatal, warn, or be claimed.
	 *
	 * An autoloader that is not responsible for a name has to do nothing, so
	 * the next one in the SPL stack gets its turn.
	 *
	 * @return void
	 */
	public function test_unknown_names_are_declined_quietly() {
		$this->assertFalse( class_exists( 'BlockKit\\Does_Not_Exist' ) );
		$this->assertFalse( class_exists( 'BlockKit\\Deeply\\Nested\\Missing' ) );
	}

	/**
	 * Names outside our namespace are ignored entirely.
	 *
	 * @return void
	 */
	public function test_foreign_namespaces_are_ignored() {
		$this->assertFalse( class_exists( 'SomeOther\\Plugin\\Thing' ) );
		$this->assertFalse( class_exists( 'BlockKitchen\\Thing' ), 'prefix match must respect the separator' );
	}

	/**
	 * A name that could escape includes/ must be refused.
	 *
	 * The class name comes from PHP itself so this is belt and braces, but the
	 * method builds a filesystem path and validates rather than trusts.
	 *
	 * @return void
	 */
	public function test_path_traversal_is_refused() {
		$this->assertFalse( class_exists( 'BlockKit\\..\\..\\etc\\passwd' ) );
		$this->assertFalse( class_exists( 'BlockKit\\Foo/Bar' ) );
	}
}
