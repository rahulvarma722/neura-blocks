<?php
/**
 * Option storage.
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper over the options API.
 *
 * Every option key is built here from BLOCKKIT_SLUG, so a rename does not
 * require touching call sites. Never call get_option() with a hardcoded
 * "blockkit_" prefix elsewhere — a renamed plugin would silently read the
 * wrong key and appear to lose its settings.
 */
class BlockKit_Settings {

	/**
	 * Builds the prefixed option name for a key.
	 *
	 * @param string $key Unprefixed key, e.g. 'enabled_blocks'.
	 * @return string Prefixed option name, e.g. 'blockkit_enabled_blocks'.
	 */
	public static function key( $key ) {
		return BLOCKKIT_SLUG . '_' . $key;
	}

	/**
	 * Reads an option.
	 *
	 * @param string $key           Unprefixed key.
	 * @param mixed  $default_value Value returned when the option is absent.
	 * @return mixed
	 */
	public static function get( $key, $default_value = false ) {
		return get_option( self::key( $key ), $default_value );
	}

	/**
	 * Writes an option.
	 *
	 * @param string $key   Unprefixed key.
	 * @param mixed  $value Value to store.
	 * @return bool Whether the value changed.
	 */
	public static function update( $key, $value ) {
		return update_option( self::key( $key ), $value );
	}

	/**
	 * Deletes an option.
	 *
	 * @param string $key Unprefixed key.
	 * @return bool Whether the option existed.
	 */
	public static function delete( $key ) {
		return delete_option( self::key( $key ) );
	}
}
