<?php
/**
 * Environment checks.
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the site meets the plugin's minimum PHP and WordPress versions.
 */
class BlockKit_Requirements {

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

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p><ul style="list-style:disc;margin-left:20px">%s</ul></div>',
			esc_html__( 'BlockKit could not be loaded.', 'blockkit' ),
			implode(
				'',
				array_map(
					static function ( $failure ) {
						return '<li>' . esc_html( $failure ) . '</li>';
					},
					self::get_failures()
				)
			)
		);
	}
}
