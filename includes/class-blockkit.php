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
	}

	/*
	 * No load_plugin_textdomain() call, deliberately.
	 *
	 * Since WordPress 4.6 core loads translations for a .org-hosted plugin
	 * just-in-time, keyed on the plugin slug, the first time a translation
	 * function runs. Calling it by hand is redundant and Plugin Check flags it
	 * (`DiscouragedFunctions.load_plugin_textdomainFound`). The text domain
	 * still has to match the folder slug for that to work — see
	 * BLOCKKIT_SLUG in blockkit.php.
	 *
	 * JavaScript strings are a separate mechanism and DO need wiring up:
	 * BlockKit_Blocks::set_script_translations().
	 */
}
