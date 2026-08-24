<?php
/**
 * Plugin Name:       BlockKit
 * Plugin URI:        https://example.com/blockkit
 * Description:       A Gutenberg block collection built on the WordPress 7.1 block API.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Brainstorm Force
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockkit
 * Domain Path:       /languages
 *
 * @package BlockKit
 */

defined( 'ABSPATH' ) || exit;

/*
 * ---------------------------------------------------------------------
 * Identity
 * ---------------------------------------------------------------------
 * Every name the plugin uses is derived from the three constants below.
 * Nothing else in the codebase should contain the literal string
 * "blockkit" — see bin/rename.sh and RENAMING.md.
 *
 * BLOCKKIT_SLUG      Folder name, text domain, option prefix, handles.
 *                    Changeable, but the folder rename breaks .org updates.
 *
 * BLOCKKIT_NAMESPACE Block namespace, e.g. `blockkit/button`. This is
 *                    written into post content as an HTML comment and is
 *                    effectively PERMANENT once a site saves a post with
 *                    one of these blocks. Changing it without shipping
 *                    block deprecations breaks existing content.
 */
define( 'BLOCKKIT_VERSION', '1.0.0' );
define( 'BLOCKKIT_SLUG', 'blockkit' );
define( 'BLOCKKIT_NAMESPACE', 'blockkit' );

define( 'BLOCKKIT_FILE', __FILE__ );
define( 'BLOCKKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLOCKKIT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum environment the plugin supports.
 *
 * Checked on load rather than only on activation, so that a site which
 * downgrades PHP or WordPress gets a notice instead of a fatal error.
 */
const BLOCKKIT_MIN_PHP = '8.1';
const BLOCKKIT_MIN_WP  = '6.6';

require_once BLOCKKIT_PATH . 'includes/class-blockkit-requirements.php';

if ( ! BlockKit_Requirements::are_met() ) {
	add_action( 'admin_notices', array( 'BlockKit_Requirements', 'render_notice' ) );
	return;
}

require_once BLOCKKIT_PATH . 'includes/class-blockkit-settings.php';
require_once BLOCKKIT_PATH . 'includes/class-blockkit-blocks.php';
require_once BLOCKKIT_PATH . 'includes/class-blockkit.php';

BlockKit::init();
