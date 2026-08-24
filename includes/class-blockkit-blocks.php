<?php
/**
 * Block registration.
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers every compiled block found in build/.
 *
 * Blocks are discovered by scanning the directory rather than listed in
 * an array, so adding a block means adding a folder under src/ — no PHP
 * change, and nothing to keep in sync.
 */
class BlockKit_Blocks {

	/**
	 * Hooks registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'register_category' ) );
	}

	/**
	 * Registers all blocks from their compiled metadata.
	 *
	 * Note this reads build/, not src/. wp-scripts copies block.json and
	 * render.php across at build time; registering from src/ would fail
	 * because the compiled asset files it references do not exist there.
	 *
	 * @return void
	 */
	public static function register() {
		$build_dir = BLOCKKIT_PATH . 'build';

		if ( ! is_dir( $build_dir ) ) {
			return;
		}

		foreach ( (array) glob( $build_dir . '/*', GLOB_ONLYDIR ) as $block_dir ) {
			if ( ! file_exists( $block_dir . '/block.json' ) ) {
				continue;
			}

			register_block_type( $block_dir );
		}
	}

	/**
	 * Adds the plugin's category to the block inserter.
	 *
	 * The slug must match the `category` field in each block.json, or the
	 * blocks land in the inserter's "Uncategorized" section.
	 *
	 * @param array[] $categories Registered block categories.
	 * @return array[] Categories with this plugin's category added.
	 */
	public static function register_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && BLOCKKIT_SLUG === $category['slug'] ) {
				return $categories;
			}
		}

		return array_merge(
			array(
				array(
					'slug'  => BLOCKKIT_SLUG,
					'title' => __( 'BlockKit', 'blockkit' ),
					'icon'  => null,
				),
			),
			$categories
		);
	}
}
