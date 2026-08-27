# Architecture

## File layout

```
blockkit.php                          plugin header, constants, boot
includes/
  class-autoloader.php                BlockKit\*  ->  includes/class-*.php
  class-requirements.php              PHP / WordPress floor
  class-plugin.php                    subsystem wiring
  class-blocks.php                    block registration + JS translations
  class-responsive-styles.php         per-viewport values -> CSS
src/
  button/                             authored source for blockkit/button
  buttons/                            authored source for blockkit/buttons
build/                                wp-scripts output (gitignored, but SHIPPED)
bin/
  build-zip.sh                        release gate + packaging
  rename.sh                           rename the whole plugin
docs/                                 this documentation
```

## Namespace and autoloading

Everything except the main file lives under the `BlockKit` namespace and is
loaded on demand.

```php
BlockKit\Autoloader          includes/class-autoloader.php
BlockKit\Requirements        includes/class-requirements.php
BlockKit\Plugin              includes/class-plugin.php
BlockKit\Blocks              includes/class-blocks.php
BlockKit\Responsive_Styles   includes/class-responsive-styles.php
```

The mapping strips the namespace, lowercases the rest, turns `_` into `-`, and
prefixes `class-`. Sub-namespaces become sub-directories, so
`BlockKit\Blocks\Registrar` would resolve to
`includes/blocks/class-registrar.php` with no extra registration.

### Why not Composer's autoloader

Composer is a **dev** dependency here — it installs PHPCS and nothing else, and
`vendor/` is excluded from the release ZIP. Depending on `vendor/autoload.php`
at runtime would invert that: the plugin could not boot without shipping
Composer's autoloader plus its `composer/` support files, several hundred files
of machinery to resolve five classes. It would also make `composer install` a
build step for anyone cloning the repo rather than only a linting one.

The hand-written autoloader is about thirty lines and has no runtime
dependency.

### Three namespace consequences worth knowing

**Global classes must be fully qualified.** Inside a namespace, an unqualified
class name resolves *within that namespace* — there is no fallback to global.
So `$x instanceof WP_Block_Type` inside `BlockKit\Blocks` would test against
`BlockKit\WP_Block_Type`, which does not exist, and silently evaluate false.
Core classes are written `\WP_Block_Type`, `\WP_Theme_JSON`.

**Functions and constants do fall back.** Unqualified `add_action()`,
`esc_html()`, `BLOCKKIT_PATH` and `ABSPATH` resolve to global if no namespaced
version exists, which is why they are left unqualified throughout.

**`render.php` is a special case.** Core requires it from inside a closure in
`wp-includes/blocks.php`, which is *global* scope regardless of where the file
sits on disk. Every plugin class it uses is therefore written fully qualified,
leading separator included: `\BlockKit\Responsive_Styles::build_css()`.

## Boot order

`blockkit.php` stays in the global namespace, because a plugin's main file is
also its header block and WordPress reads that by parsing the file rather than
loading it.

```
1.  Guard          defined( 'ABSPATH' ) || exit
2.  Constants      BLOCKKIT_VERSION, BLOCKKIT_SLUG, BLOCKKIT_PATH
                   BLOCKKIT_MIN_PHP, BLOCKKIT_MIN_WP
3.  Autoloader     require + register — the only require in the file
4.  Requirements   BlockKit\Requirements::are_met()
                     ├─ fails -> admin_notices, then RETURN. Nothing else loads.
                     └─ passes -> continue
5.  Boot           BlockKit\Plugin::init()
                     └─ BlockKit\Blocks::init()
                          ├─ add_action( 'init', register )
                          └─ add_filter( 'block_categories_all', … )
```

Requirements are checked **on load**, not only on activation, so a site that
downgrades PHP or WordPress after activating gets an admin notice instead of a
fatal error. The notice callback is registered with a *string* class name so
the class is not loaded unless the notice actually renders.

## Constants

| Constant | Purpose |
|---|---|
| `BLOCKKIT_VERSION` | Canonical version. `bin/build-zip.sh` refuses to package unless this, the `Version:` header and readme's `Stable tag` agree. |
| `BLOCKKIT_SLUG` | Folder name, text domain, script handles, block category. |
| `BLOCKKIT_PATH` | Absolute plugin path, used by the autoloader and block scan. |
| `BLOCKKIT_MIN_PHP` / `BLOCKKIT_MIN_WP` | The declared floor, checked at load. |

There is deliberately **no** namespace constant — `register_block_type()` reads
block names only from `block.json`, so a constant could only duplicate that
literal and drift from it. See [Renaming](RENAMING.md).

## The classes

### `Requirements`
Compares `PHP_VERSION` and `get_bloginfo( 'version' )` against the declared
floor and returns human-readable failures. `render_notice()` checks
`current_user_can( 'activate_plugins' )` first — a subscriber has no use for a
message about PHP versions.

The notice builds its list with a `foreach` and inline `esc_html()` rather than
`array_map()` + `implode()`. Both escape identically, but PHPCS cannot follow a
value out of a callback, so the `array_map()` form raised an
`EscapeOutput.OutputNotEscaped` **error** on already-safe code. Writing the
escaping where the sniff can see it beats silencing a security sniff.

### `Plugin`
A list of what is enabled, not a place where behaviour accumulates. Each
subsystem owns its own hooks and exposes its own `init()`.

No `load_plugin_textdomain()` call: core has loaded translations for
.org-hosted plugins just-in-time since 4.6, and Plugin Check flags the manual
call as discouraged. The text domain must still match the folder slug for that
to work.

### `Blocks`
Registers every block found by scanning `build/` — adding a block means adding
a directory under `src/`, with no PHP to update. Also:

- Adds the plugin's block category, matching the `category` field each
  `block.json` declares, or the blocks land in "Uncategorized".
- Calls `wp_set_script_translations()` for each registered block.
  `register_block_type()` handles the strings *inside* `block.json` but not the
  `__()` calls in the compiled editor script, which is where the control labels
  actually live. Handles are read back off the returned `\WP_Block_Type` rather
  than reconstructed, because the naming is core's private business
  (`generate_block_asset_handle()`).

### `Responsive_Styles`
Converts namespaced per-viewport values into CSS. Covered in
[Styles](STYLES.md).

## What this plugin does not do

Worth stating, because their absence is deliberate and a reviewer will check:

- No options are stored, no settings page, no `register_setting()`.
- No database queries, no `$wpdb`.
- No AJAX handlers, no REST routes — so no nonces to verify.
- No external HTTP requests, no tracking, no update checker.
- No front-end JavaScript. Both blocks render server-side.
- No bundled copies of libraries WordPress already ships.
