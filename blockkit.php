<?php
/**
 * Plugin Name:       BlockKit
 * Plugin URI:        https://github.com/rahulvarma722/blockkit
 * Description:       A Gutenberg block collection built on the WordPress 7.1 block API.
 * Version:           1.0.0
 * Requires at least: 7.1
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Author:            Brainstorm Force
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockkit
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
 *
 * The WordPress floor is 7.1 because per-viewport style states are a 7.1
 * feature and the button's width/icon-size controls are built directly on
 * them: they write core's `@tablet` / `@mobile` layers and detect which state
 * the editor is in by probing core's own slots. On 6.x those slots do not
 * exist, so the controls would still render and still store values — into
 * layers the editor cannot read back. Failing loudly beats that.
 */
const BLOCKKIT_MIN_PHP = '8.1';
const BLOCKKIT_MIN_WP  = '7.1';

require_once BLOCKKIT_PATH . 'includes/class-blockkit-requirements.php';

if ( ! BlockKit_Requirements::are_met() ) {
	add_action( 'admin_notices', array( 'BlockKit_Requirements', 'render_notice' ) );
	return;
}

require_once BLOCKKIT_PATH . 'includes/class-blockkit-settings.php';
require_once BLOCKKIT_PATH . 'includes/class-blockkit-responsive-styles.php';
require_once BLOCKKIT_PATH . 'includes/class-blockkit-blocks.php';
require_once BLOCKKIT_PATH . 'includes/class-blockkit.php';

BlockKit::init();
