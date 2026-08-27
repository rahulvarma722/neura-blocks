<?php
/**
 * Option storage.
 *
 * @package BlockKit
 */

namespace BlockKit;

defined( 'ABSPATH' ) || exit;

/**
 * A thin, sanitising wrapper over the options API.
 *
 * An earlier version of this class was removed for being unused, which was the
 * right call at the time — an options wrapper with no options is just a file to
 * read. It is back because Text_Tags needs somewhere to store which optional
 * tags a site has switched on.
 *
 * ONE OPTION, NOT ONE PER SETTING. Everything lives in a single autoloaded
 * array at `blockkit_settings`. A plugin that grows features grows settings, and
 * one option per setting means one query per setting on sites where autoload is
 * disabled, plus a scattered mess to clean up on uninstall.
 *
 * SANITISED ON READ, not only on write. Options can be set by WP-CLI, by a
 * migration, by another plugin, or by a developer in a hurry — so the read path
 * cannot assume the write path was used.
 */
final class Settings {

	/**
	 * The option name holding every setting.
	 *
	 * Built from the slug so a rename does not orphan the data.
	 *
	 * @return string
	 */
	public static function option_name() {
		return BLOCKKIT_SLUG . '_settings';
	}

	/**
	 * Default values, and by extension the list of known settings.
	 *
	 * A key absent from here is not a setting, and get()/update() will not
	 * store it. That keeps the option from accumulating junk from typos.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			// Optional text tags a site has switched on, beyond the defaults.
			'text_tags' => array(),
		);
	}

	/**
	 * Reads one setting.
	 *
	 * @param string $key           Setting name.
	 * @param mixed  $default_value Returned when the setting is unknown or unset.
	 * @return mixed
	 */
	public static function get( $key, $default_value = null ) {
		$defaults = self::defaults();

		if ( ! array_key_exists( $key, $defaults ) ) {
			return $default_value;
		}

		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $defaults[ $key ];
	}

	/**
	 * Reads every setting, with defaults filled in.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::option_name(), array() );
		$stored = is_array( $stored ) ? $stored : array();

		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * Writes one setting.
	 *
	 * @param string $key   Setting name. Ignored if not a known setting.
	 * @param mixed  $value New value.
	 * @return bool Whether the option changed.
	 */
	public static function update( $key, $value ) {
		if ( ! array_key_exists( $key, self::defaults() ) ) {
			return false;
		}

		$all         = self::all();
		$all[ $key ] = $value;

		return update_option( self::option_name(), self::sanitize( $all ) );
	}

	/**
	 * Deletes the whole option.
	 *
	 * @return bool Whether it existed.
	 */
	public static function delete() {
		return delete_option( self::option_name() );
	}

	/**
	 * Coerces a settings array into its declared shape.
	 *
	 * Unknown keys are dropped rather than preserved. Preserving them sounds
	 * generous but means a typo lives in the database forever and shows up in
	 * every export.
	 *
	 * @param array $settings Candidate settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$clean    = array();

		foreach ( self::defaults() as $key => $default_value ) {
			$value = array_key_exists( $key, $settings ) ? $settings[ $key ] : $default_value;

			switch ( $key ) {
				case 'text_tags':
					/*
					 * Tag names are validated by Text_Tags, which owns the
					 * vocabulary and the never-permitted list. Doing it here
					 * too would be a second place for the two to disagree.
					 */
					$clean[ $key ] = is_array( $value )
						? array_values( array_intersect( array_map( 'strval', $value ), Text_Tags::all() ) )
						: array();
					break;

				default:
					$clean[ $key ] = $value;
					break;
			}
		}

		return $clean;
	}
}
