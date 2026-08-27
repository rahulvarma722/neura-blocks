# Blocks

Three blocks: a text block, and a container with its only permitted child.

## Kit Text — `blockkit/text`

One block whose **HTML tag and visual style are independent**. That is the whole
point of it, and the reason it is not a duplicate of `core/heading`.

Core couples the two: pick `h2` and you get h2's size. So you choose between a
correct document outline and the design you want, and the outline usually loses.

| Control | Decides | Attribute |
|---|---|---|
| **HTML tag** | what the text *means* — screen readers, search engines, outline | `tagName` |
| **Style as** | what the text *looks like* | `styleAs` |

An `h2` styled as a caption:

```html
<h2 class="has-style-caption wp-block-blockkit-text">Section title</h2>
```

`styleAs` is emitted as a **class, never an inline style**, so `style.scss` can
resolve each preset against the active theme's font-size presets — falling back
to a `clamp()` only when the theme declares none — and a theme can override the
whole scale in one place. An inline `font-size` would beat the theme forever.

### The tag vocabulary is a setting

`includes/class-text-tags.php` owns it, and answers two different questions:

- **`all()`** — what the RENDERER accepts
- **`enabled()`** — what the EDITOR offers

They are deliberately different lists. Defaults are `h1`–`h6`, `p`, `span`,
`div`; the rest are grouped and switched on per site. If a site disables
`blockquote` after publishing posts that use it, those posts **keep rendering as
`blockquote`** — turning a tag off stops new uses, it does not rewrite existing
content.

`NEVER` is the floor: no `script`, `iframe`, `img`, `a`, `style`, `form`, `svg`
or anything else that can execute, load a resource or take input. A stored value
*or a filter* cannot get past it, because `all()` sanitises its own filter
output. The tag name lands in an element position, so this is an XSS boundary,
not a styling preference.

Extension points for a Pro add-on:

```php
add_filter( 'blockkit_text_tags', … );          // the vocabulary
add_filter( 'blockkit_enabled_text_tags', … );  // what the editor offers
```

### `content` has no `source` — and that is load-bearing

```json
"content": { "type": "string", "default": "", "role": "content" }
```

Core stores it inside the block comment delimiter:

```html
<!-- wp:blockkit/text {"content":"Hello","tagName":"h2"} /-->
```

Declaring `"source": "rich-text"` instead — which is what `core/heading` and
`core/paragraph` do — tells core to parse the value back out of the **saved
markup**. Those blocks have a real `save()` that writes that markup. This one
returns `null`, so there would be nothing to parse from: the text is written
nowhere, reads back empty, and the block renders nothing on the front end.

**This shipped as a bug and reached the front end.** It is the same trap as a
container returning `null` and discarding its inner blocks, and it fails just as
silently — the editor looks correct until you reload.

Every other integration check hand-wrote the block delimiter, which quietly
guaranteed the attribute was present. That is not what the editor does, so those
checks could not catch it. There is now a round-trip test that goes
attributes → `serialize_blocks()` → parse → render, plus one through a real post
and `the_content`.

If this block ever gains a real `save()`, `source` should come back with it. The
two decisions belong together.

### Heading-outline guardrail

`use-outline-check.js` warns in the inspector when a heading **skips a level**
(h2 → h4) or when a **second `h1`** appears. It walks the block tree recursively
— headings inside a Group or Columns are still part of the outline — and counts
`core/heading` too, because the outline belongs to the document, not to this
plugin.

Warnings only, never enforcement. A block that refused to save would be worse
than one that mentions it.

This pairs with `styleAs` by design: decoupling removes the *reason* people break
an outline, and the warning covers the rest.

## Kit Buttons and Kit Button

A container and its only permitted child.

```
blockkit/buttons   Kit Buttons   flex container, wide/full align, block gap
  └─ blockkit/button   Kit Button   link or button, optional icon, per-viewport width
```

`button` declares `"parent": [ "blockkit/buttons" ]`, so it cannot be inserted
anywhere else; `buttons` declares `"allowedBlocks": [ "blockkit/button" ]`, so
nothing else can go inside. The container supplies the flex layout and gap the
child expects, which is why neither is useful alone.

## Registration

`BlockKit\Blocks::register()` scans `build/*/` for `block.json` and registers
each directory it finds. There is no array of block names anywhere.

**It reads `build/`, not `src/`.** `wp-scripts` copies `block.json` and
`render.php` across at build time, and the compiled `block.json` points at
`index.js` / `style-index.css` / `index.asset.php`, which exist only in
`build/`. Registering from `src/` would fail on the missing assets.

The consequence to remember: **`build/` is gitignored but must ship.** A fresh
clone registers zero blocks until `npm run build` runs, and a release ZIP
without `build/` installs cleanly and does nothing. `bin/build-zip.sh` runs the
build itself so the second case cannot happen.

## Source layout

```
src/button/
  block.json            metadata, attributes, supports
  index.js              registerBlockType( metadata.name, … )
  edit.js               editor UI
  icon.js               the four icons + their labels (JS side)
  render.php            front-end markup
  style.scss            shared, front end AND editor canvas
  editor.scss           editor-only affordances
  responsive-width/     the per-viewport control — see docs/STYLES.md
src/buttons/
  block.json  index.js  edit.js  render.php  style.scss  editor.scss
```

## The save/render split — the trap worth knowing

Both blocks render server-side from `render.php`. But their `save` functions
differ, and getting it wrong is silent and destructive.

**`button` — `save: () => null`.** Correct. Nothing is written to post content;
the front-end markup comes entirely from `render.php`.

**`buttons` — `save: () => <InnerBlocks.Content />`.** Also correct, and *not*
`null`. It is tempting to return `null` for a block that renders in PHP, but
for a **container** that is wrong: with no save output, core serialises the
block as a self-closing comment — `<!-- wp:blockkit/buttons /-->` — and every
inner block is discarded at save time. The buttons vanish along with their
text, URLs and styles, and nothing warns the user.

"Dynamic" means the block's *own markup* is generated at render time. Inner
blocks still have to exist in post content, because that is where they are
stored. `render.php` receives them already rendered, as `$content`.

`InnerBlocks.Content` emits the children and no wrapper, which is what this
block needs — `render.php` supplies the wrapping `div` itself.

## `render.php`

Both files carry a file-level `phpcs:disable
WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound`. The
variables are *not* global: core wraps render templates in a static closure and
requires them from inside it (`wp-includes/blocks.php:629-631`), so every
assignment is function-scoped. PHPCS sees a file whose top level assigns
variables and cannot know that. Core's own block templates trip the same sniff.

### `button/render.php`

The anchor **is** the block root — `get_block_wrapper_attributes()` is spread
onto it, so every class and inline style core generates from `supports` lands
on the element the user actually clicks. This differs deliberately from
`core/button`, which nests `.wp-block-button__link` inside a wrapper div; core
needs that extra element so a width setting can size the flex item
independently of the link.

Order of operations:

1. **Empty label → render nothing.** An invisible button still occupies a flex
   slot.
2. **Sanitise every attribute.** Values come from post content: written by
   someone with edit rights, but long-lived, portable between sites, and
   reachable by anything that can write a post.
3. **Escape the URL first, then choose the tag.** `esc_url()` does not merely
   escape — it *drops* the value when the scheme is not allow-listed. Testing
   the raw value for emptiness let `javascript:alert(1)` through the anchor
   branch and emitted `<a href="">`, an anchor with no destination, which is
   exactly what the branch exists to prevent.
4. **No usable URL → `<button type="button">`** rather than a hrefless anchor,
   which is not keyboard focusable.
5. **Build the icon** from a fixed four-entry path map.
6. **Emit per-viewport CSS** — see [Styles](STYLES.md).
7. **Print**, with attributes handed to `get_block_wrapper_attributes()`.

Attribute handling:

| Attribute | Treatment |
|---|---|
| `url` | `esc_url()`; empty result falls back to `<button>` |
| `linkTarget` | allow-listed to `_blank` / `_self` / `_parent` / `_top` |
| `rel` | whole tokens matched against the HTML link-type registry |
| `title` | `sanitize_text_field()` |
| `text` | `wp_kses()` with an explicit inline-formatting list |
| `tagName` | allow-listed to `a` / `button` |
| `icon` | must be a key of the internal path map |
| `iconPosition` | allow-listed to `left` / `right` |

`target` gets an allow-list rather than escaping alone because browsers treat
an unrecognised target as a **named browsing context** — `target="evil"`
silently opens links in a window named `evil` and keeps reusing it, invisibly.

`rel` gets whole-token matching because character filtering is not enough:
`noopener"><script>alert(1)</script>` reduces to
`noopenerscriptalert1script`, safe to print and completely meaningless.

The label uses `wp_kses()` with an explicit list rather than `wp_kses_post()`.
`wp_kses_post()` is the filter for post *bodies*, so it permits `<img>`,
`<iframe>` and `<a>` — and a nested `<a>` inside this anchor is invalid HTML
browsers recover from unpredictably.

The icon SVG is **not** run through `wp_kses()`. It cannot be: kses lowercases
every attribute name before matching, so `viewBox` becomes `viewbox` whatever
the allow-list says, and an allow-list written as `viewBox` matches nothing and
drops the attribute entirely. `viewbox` survives via the HTML parser's
SVG-attribute adjustment, but `viewBox` is what makes the icon scale, and
leaning on parser error-correction is a poor trade for defence-in-depth on a
value that is one of four literals in the same file.

### `buttons/render.php`

Emits the wrapper and prints `$content`. Layout classes — the flex container
and its `blockGap` — are added by `wp_render_layout_support_flag()` on the
`render_block` filter, *after* this file runs.

`$content` is deliberately **unfiltered**. It is already-rendered inner block
HTML, each child escaped by its own callback, so filtering would escape twice.
`wp_kses()` there would be actively destructive: a container cannot know what
its children legitimately emit, so any allow-list narrow enough to be worth
writing would silently delete part of somebody's page. Core's `core/group`,
`core/buttons` and `core/columns` print `$content` raw for the same reason.

## `block.json` notes

**Experimental support keys are correct as written.** `__experimentalBorder`,
`__experimentalFontFamily` and friends look like something to modernise, but
WordPress 7.1's `block-supports/typography.php` and `border.php` read *only*
the experimental keys, and core's own bundled `blocks/blocks-json.php` still
uses them for core blocks. Renaming them to `border` / `fontFamily` would
silently stop server-side style serialisation.

**Icons are duplicated by design, and by hand.** `src/button/icon.js` holds the
JS copy and `render.php` holds the PHP twin, because the editor renders from JS
and the front end from PHP. A key added to one must be added to the other or
the icon disappears on save.

## Adding a block

1. `mkdir src/my-block` with `block.json`, `index.js`, `edit.js`, `render.php`.
2. Set `"name": "blockkit/my-block"` and `"category": "blockkit"`.
3. Import the name from metadata — never write it as a literal:
   `registerBlockType( metadata.name, … )`.
4. `npm run build`.

No PHP changes. The scan finds it.
