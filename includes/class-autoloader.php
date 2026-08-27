<?php
/**
 * Class autoloading.
 *
 * @package BlockKit
 */

namespace BlockKit;

defined( 'ABSPATH' ) || exit;

/**
 * Maps `BlockKit\*` class names onto files under includes/.
 *
 * WHY A HAND-WRITTEN AUTOLOADER RATHER THAN COMPOSER'S.
 *
 * Composer is a dev dependency here — it installs PHPCS and nothing else, and
 * `vendor/` is deliberately excluded from the release ZIP. Using
 * `vendor/autoload.php` at runtime would invert that: the plugin could not boot
 * without shipping Composer's autoloader and its `composer/` support files,
 * which is several hundred files of machinery to resolve four classes. It would
 * also mean `composer install` becomes a build step for anyone cloning the
 * repo, not just a linting one.
 *
 * So the mapping is done here, in about thirty lines, with no runtime
 * dependency at all.
 *
 * NAMING CONVENTION.
 *
 * The namespace is stripped, then the remaining class name is lowercased and
 * underscores become hyphens, prefixed with `class-`:
 *
 *   BlockKit\Blocks               -> includes/class-blocks.php
 *   BlockKit\Responsive_Styles    -> includes/class-responsive-styles.php
 *   BlockKit\Blocks\Registrar     -> includes/blocks/class-registrar.php
 *
 * That is the WordPress file-naming convention, so the layout stays legible to
 * anyone who has read another WordPress plugin, and sub-namespaces map onto
 * sub-directories without any extra registration.
 */
final class Autoloader {

	/**
	 * Namespace prefix this autoloader answers for, with trailing separator.
	 *
	 * @var string
	 */
	const PREFIX = __NAMESPACE__ . '\\';

	/**
	 * Registers the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Resolves and requires the file for a class, if we own the name.
	 *
	 * Returns silently for anything outside our namespace: an autoloader that
	 * is not responsible for a class must do nothing, so the next one in the
	 * SPL stack gets its turn. Throwing or warning here would break unrelated
	 * plugins.
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @return void
	 */
	public static function load( $class_name ) {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$path     = self::path_for( $relative );

		/*
		 * file_exists() rather than letting require fail. A missing file is a
		 * programming error, but a fatal from inside an autoloader is nearly
		 * unreadable — it reports the require, not the class_exists() call or
		 * the `new` that triggered it.
		 */
		if ( $path && file_exists( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Builds the file path for a namespace-relative class name.
	 *
	 * @param string $relative Class name with the plugin namespace removed.
	 * @return string|false Absolute path, or false if the name is not one we map.
	 */
	private static function path_for( $relative ) {
		// Reject anything that is not a plain class path. Belt and braces: the
		// name comes from PHP itself, but this function builds a filesystem
		// path, so it validates rather than trusts.
		if ( ! preg_match( '/^[A-Za-z0-9_\\\\]+$/', $relative ) ) {
			return false;
		}

		$segments = explode( '\\', $relative );
		$class    = array_pop( $segments );

		$directory = BLOCKKIT_PATH . 'includes/';

		foreach ( $segments as $segment ) {
			$directory .= self::to_filename( $segment ) . '/';
		}

		return $directory . 'class-' . self::to_filename( $class ) . '.php';
	}

	/**
	 * Converts one CamelCase_Segment into its file-name form.
	 *
	 * @param string $segment A single namespace or class segment.
	 * @return string Lowercased, hyphen-separated.
	 */
	private static function to_filename( $segment ) {
		return str_replace( '_', '-', strtolower( $segment ) );
	}
}
