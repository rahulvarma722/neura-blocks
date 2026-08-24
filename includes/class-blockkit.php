<?php
/**
 * Plugin bootstrap.
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires up the plugin's subsystems.
 *
 * Each subsystem exposes its own static init() and owns its hooks, so
 * this class stays a list of what is enabled rather than a place where
 * behaviour accumulates.
 */
class BlockKit {

	/**
	 * Boots the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		BlockKit_Blocks::init();

		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
	}

	/**
	 * Loads translations.
	 *
	 * The text domain must match the plugin's folder slug for
	 * translate.wordpress.org to deliver translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			BLOCKKIT_SLUG,
			false,
			dirname( plugin_basename( BLOCKKIT_FILE ) ) . '/languages'
		);
	}
}
