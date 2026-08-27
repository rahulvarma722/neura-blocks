<?php
/**
 * Environment checks.
 *
 * @package BlockKit
 */

namespace BlockKit;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the site meets the plugin's minimum PHP and WordPress versions.
 */
class Requirements {

	/**
	 * Whether the current environment satisfies the minimums.
	 *
	 * @return bool
	 */
	public static function are_met() {
		return empty( self::get_failures() );
	}

	/**
	 * Lists the unmet requirements.
	 *
	 * @return string[] Human-readable failure messages.
	 */
	public static function get_failures() {
		$failures = array();

		if ( version_compare( PHP_VERSION, BLOCKKIT_MIN_PHP, '<' ) ) {
			$failures[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'PHP %1$s or higher is required. This site runs PHP %2$s.', 'blockkit' ),
				BLOCKKIT_MIN_PHP,
				PHP_VERSION
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), BLOCKKIT_MIN_WP, '<' ) ) {
			$failures[] = sprintf(
				/* translators: 1: required WordPress version, 2: current WordPress version */
				__( 'WordPress %1$s or higher is required. This site runs WordPress %2$s.', 'blockkit' ),
				BLOCKKIT_MIN_WP,
				get_bloginfo( 'version' )
			);
		}

		return $failures;
	}

	/**
	 * Prints an admin notice describing why the plugin did not load.
	 *
	 * @return void
	 */
	public static function render_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		/*
		 * Built with a foreach and echoed piecemeal rather than assembled with
		 * array_map()/implode() and passed to printf().
		 *
		 * Both escape identically, but PHPCS cannot follow a value out of a
		 * callback and back, so the array_map() form raises
		 * `WordPress.Security.EscapeOutput.OutputNotEscaped` — an ERROR in
		 * Plugin Check, on code that was already safe. Rather than silence a
		 * security sniff with a phpcs:ignore, the escaping is written where the
		 * sniff can see it. A reviewer reads it the same way.
		 */
		echo '<div class="notice notice-error"><p><strong>';
		esc_html_e( 'BlockKit could not be loaded.', 'blockkit' );
		echo '</strong></p><ul style="list-style:disc;margin-left:20px">';

		foreach ( self::get_failures() as $failure ) {
			echo '<li>' . esc_html( $failure ) . '</li>';
		}

		echo '</ul></div>';
	}
}
