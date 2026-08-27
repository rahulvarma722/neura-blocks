=== BlockKit ===
Tags:              blocks, button, responsive, icons, breakpoints
Requires at least: 7.1
Tested up to:      7.1
Requires PHP:      8.1
Stable tag:        0.0.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Blocks that extend WordPress 7.1 rather than reinvent it — separate visual style from semantic markup, and reach the CSS core leaves out.

== Description ==

BlockKit adds blocks that build on WordPress 7.1's block API instead of
duplicating it. Core's typography, colour, spacing and border controls — and
core's per-viewport style states — are declared as supports and inherited, not
reimplemented. What BlockKit adds is the part core leaves out.

= Blocks =

* **Kit Text** — one text block whose HTML tag and visual style are *independent*. Choose `h2` for your document outline and style it like a caption, or the reverse. Plus balanced text wrapping, a readability measure, and line clamping.
* **Kit Buttons** — a flex container for one or more buttons, with block gap, padding and wide/full alignment.
* **Kit Button** — a button-style link with an optional icon, and per-viewport width and icon size.

= Why a separate text block =

Core couples a heading's level to its appearance. Pick `h2` and you get h2's
size; pick the size you want and you change the heading level with it. That
forces a choice between a correct document outline and the design you actually
want, and the outline usually loses.

Kit Text splits the two. The tag is a semantic decision — for screen readers,
for search engines, for the document outline. The visual style is a design
decision. Neither constrains the other, and the editor warns you when a heading
level is skipped or a second `h1` appears on the page.

It also fills in typographic controls core has no UI for at all:

* `text-wrap: balance` and `pretty` — headings that break evenly instead of leaving one orphaned word
* **Measure** — a maximum line length in `ch`, the readability unit
* **Line clamp** — truncate to a set number of lines with an ellipsis

= On responsive values =

WordPress 7.1 emits per-viewport CSS for its own style paths, so BlockKit does
not need to reinvent that and does not try to. Where BlockKit adds a property
core has no support for, it stores the value inside core's own `style` attribute
under a namespaced key, in core's viewport-state shape, and generates the CSS
using core's media queries:

`style.blockkit.textWrap` for the base layer, `style.@tablet.blockkit.textWrap`
and `style.@mobile.blockkit.textWrap` for the overrides.

The breakpoints come from `WP_Theme_JSON::get_viewport_media_queries()`, which
reads `settings.viewport` from theme.json. Change your breakpoints there and
BlockKit follows, because it never had its own.

Desktop is the base layer rather than a third band, matching core: it carries no
media query and applies at every width, so Mobile falls back to the base value
when unset, never to Tablet.

= Icons =

Four built-in icons (arrow, chevron, download, external), positionable left or
right. Position is done with `flex-direction: row-reverse` rather than `order`,
so the DOM order stays text-then-icon and the button's accessible name is
unaffected by where the icon appears. Icons are marked `aria-hidden` — they are
decorative, and the button text is the accessible name.

= Privacy =

BlockKit does not collect, store or transmit any data. It makes no external
network requests, sets no cookies, creates no database tables, and registers no
REST endpoints or AJAX handlers.

= Source code =

The JavaScript in `build/` is compiled and minified by `@wordpress/scripts`.
The unminified sources are included in this plugin under `src/`, and the full
development history, build tooling and issue tracker are public at
https://github.com/rahulvarma722/blockkit

To build from source: `npm install && npm run build`.

== Installation ==

1. Upload the plugin through **Plugins > Add New**, or extract the ZIP into `wp-content/plugins/`.
2. Activate **BlockKit** through the **Plugins** menu.
3. In the editor, insert **Kit Buttons** from the **BlockKit** category in the block inserter.
4. Select a button and open the **Styles** tab to find **Custom Width** and **Icon Size**.

To set a per-viewport value, switch the editor preview to Tablet or Mobile with
Responsive styles enabled, then change the value. With Responsive styles off, a
narrow preview edits the base layer — the same behaviour as core's controls.

== Frequently Asked Questions ==

= Why does it require WordPress 7.1? =

Per-viewport style states are a 7.1 feature. The controls read and write core's
viewport-state layers and detect which state the editor is in from core's own
slots, none of which exist earlier. On WordPress 6.x the controls would appear to
work while writing values the editor could not show back to you.

= Where do the breakpoints come from? =

`WP_Theme_JSON::get_viewport_media_queries()`, which reads `settings.viewport`
from your theme.json. The defaults are 480px for mobile and 782px for tablet,
identical to core's. BlockKit has no breakpoints of its own to configure.

= Why is Desktop not listed alongside Tablet and Mobile? =

Because Desktop is the base layer, not a breakpoint. A value set on Desktop
carries no media query and therefore applies at every width; Tablet and Mobile
are overrides on top of it. This is core's model, and following it is what makes
Mobile fall back to your Desktop value rather than to your Tablet value.

= Can I use these buttons inside core's Buttons block? =

No. Kit Button declares `blockkit/buttons` as its parent, so it can only be
inserted into a Kit Buttons container. The container supplies the flex layout and
block gap the button expects.

= Does it work with Full Site Editing and template parts? =

Yes. The per-viewport CSS is emitted immediately before the button rather than
enqueued, so the rule is present before first paint even when the block renders
late — in a widget, a template part, or a REST-rendered preview.

= Does it add any front-end JavaScript? =

No. Both blocks render server-side in PHP and ship no front-end script.

== Screenshots ==

1. A Kit Buttons container with two Kit Buttons, one with an arrow icon.
2. The Custom Width and Icon Size controls in the Styles tab.
3. Editing a Mobile-only width with Responsive styles enabled.
4. The BlockKit category in the block inserter.

== Changelog ==

= 0.0.1 =
* Initial release.
* Kit Buttons container block with flex layout, block gap, padding and wide/full alignment.
* Kit Button block with link controls, four icons, and left/right icon position.
* Per-viewport Custom Width and Icon Size using WordPress 7.1 style states and core's viewport media queries.

== Upgrade Notice ==

= 0.0.1 =
Initial release.
