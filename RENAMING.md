# Renaming this plugin

Every identifier is derived from three constants in `blockkit.php`:

| Constant | Drives |
|---|---|
| `BLOCKKIT_SLUG` | folder name, text domain, option prefix, script handles, block category |
| `BLOCKKIT_NAMESPACE` | block names — `blockkit/button` |
| `BLOCKKIT_VERSION` | asset cache-busting |

Nothing else in the codebase contains the literal string `blockkit` except
`block.json` files (where the name must be a static string) and `style.scss`
(where the generated CSS class must be written out).

## Before release — cheap

```bash
./bin/rename.sh brickyard Brickyard        # dry run, lists what changes
./bin/rename.sh brickyard Brickyard --go   # apply
rm -rf build && npm run build
```

Then deactivate and reactivate the plugin, since the folder path changed.

## After release — expensive

Two things persist in the database and the script cannot reach them:

1. **Block names.** Post content stores `<!-- wp:blockkit/button -->`. Change
   the namespace and every existing post shows "This block contains
   unexpected content."
2. **Static block classes.** `save.js` output is saved into post content, so
   `wp-block-blockkit-button` is baked into published markup.

Handling that properly means shipping `deprecated` definitions on each block
so the old name still parses, or running a content migration. Neither is
hard, but both are permanent maintenance.

**So:** treat `BLOCKKIT_NAMESPACE` as permanent from your first release.
`BLOCKKIT_SLUG` and the display name stay cheap to change — that is why they
are separate constants rather than one.

## Rules that keep it that way

- Never write a block name as a literal. Import it from `block.json`:
  `registerBlockType( metadata.name, … )`.
- Never call `get_option( 'blockkit_x' )`. Use `BlockKit_Settings::get( 'x' )`.
- Never hardcode a script handle. Build it from `BLOCKKIT_SLUG`.
- Keep the display name (`Plugin Name:` header, admin titles) out of
  identifiers. It should be free to change at any time.
