<?php
/**
 * Plugin Name:       BlockKit
 * Plugin URI:        https://github.com/rahulvarma722/blockkit
 * Description:       A Gutenberg block collection built on the WordPress 7.1 block API.
 * Version:           0.0.1
 * Requires at least: 7.1
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Author:            Aman Dubey
 * Author URI:        https://amandubey.com/
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
 * Nothing else in the codebase should contain the literal string
 * "blockkit" except each block's `block.json`, where the name has to be a
 * static string — see bin/rename.sh and docs/RENAMING.md.
 *
 * BLOCKKIT_SLUG    Folder name, text domain, script handles, block
 *                  category. Changeable before release; after release the
 *                  folder rename breaks .org updates.
 *
 * BLOCKKIT_VERSION The canonical version. Read by bin/build-zip.sh, which
 *                  refuses to package unless this, the `Version:` header
 *                  and readme.txt's `Stable tag` all agree.
 *
 * THE BLOCK NAMESPACE is deliberately NOT a constant. `blockkit/button` is
 * written as a literal in each block.json, because that is the only place
 * `register_block_type()` will read it from, and a constant beside it could
 * only ever duplicate that value — silently drifting from it the moment one
 * changed. The prose is the part worth keeping:
 *
 *   The namespace is written into post content as an HTML comment
 *   (`<!-- wp:blockkit/button -->`) and is effectively PERMANENT once any
 *   site saves a post using one of these blocks. Changing it without
 *   shipping block `deprecated` definitions breaks existing content.
 */
define( 'BLOCKKIT_VERSION', '0.0.1' );
define( 'BLOCKKIT_SLUG', 'blockkit' );
define( 'BLOCKKIT_PATH', plugin_dir_path( __FILE__ ) );

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

/*
 * ---------------------------------------------------------------------
 * Boot
 * ---------------------------------------------------------------------
 * This file stays in the GLOBAL namespace, because a plugin's main file is
 * also its header block and WordPress reads that by parsing the file rather
 * than loading it. Everything else lives under the `BlockKit` namespace, so
 * the references below are fully qualified.
 *
 * The autoloader is the only require. Adding a class means adding a file —
 * there is no list here to keep in step, which is the whole point.
 */
require_once BLOCKKIT_PATH . 'includes/class-autoloader.php';

BlockKit\Autoloader::register();

/*
 * Requirements are checked before anything else is touched.
 *
 * Note the class is referenced as a STRING in the callback. That is
 * deliberate: `array( ClassName::class, 'method' )` would be tidier, but the
 * point of this branch is that the environment cannot run the plugin, and a
 * string defers class loading until the notice actually renders.
 */
if ( ! BlockKit\Requirements::are_met() ) {
	add_action( 'admin_notices', array( 'BlockKit\\Requirements', 'render_notice' ) );
	return;
}

BlockKit\Plugin::init();
