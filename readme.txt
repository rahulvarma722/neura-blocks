=== BlockKit ===
Contributors:      REPLACE_WITH_WPORG_USERNAME
Tags:              blocks, button, responsive, icons, breakpoints
Requires at least: 7.1
Tested up to:      7.1
Requires PHP:      8.1
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Button blocks with genuinely per-viewport styling — set a different width and icon size for desktop, tablet and mobile.

== Description ==

WordPress core already ships a Buttons block. BlockKit's exists for one thing
core's cannot do: set a *different* value per viewport. A core button's width is
one value at every screen size. A BlockKit button's width and icon size can be
200px on desktop, 150px on tablet and 100% on mobile, stored as three separate
values on the same block.

It does that with WordPress 7.1's per-viewport style states. A width or icon size set while previewing Tablet or
Mobile is stored for that viewport only, using the same breakpoints and the same
mutually exclusive media-query bands as core's own controls — so a BlockKit
control and a core control on the same block never disagree about where a
breakpoint sits.

= Blocks =

* **Kit Buttons** — a flex container for one or more buttons, with block gap, padding and wide/full alignment.
* **Kit Button** — a button-style link with an optional icon, and per-viewport Custom Width and Icon Size.

= What makes the responsive part different =

Most plugins invent their own attributes for this — `widthTablet`, `widthMobile`
— and then ship their own breakpoints, which drift out of step with the theme
and with core. BlockKit stores its values inside core's own `style` attribute
under a namespaced key, in core's viewport-state shape:

`style.blockkit.width` for the base layer, `style.@tablet.blockkit.width` and
`style.@mobile.blockkit.width` for the overrides.

The media queries come from `WP_Theme_JSON::get_viewport_media_queries()`, which
reads `settings.viewport` from theme.json. Change your breakpoints in theme.json
and BlockKit follows them, because it never had its own.

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

= 1.0.0 =
* Initial release.
* Kit Buttons container block with flex layout, block gap, padding and wide/full alignment.
* Kit Button block with link controls, four icons, and left/right icon position.
* Per-viewport Custom Width and Icon Size using WordPress 7.1 style states and core's viewport media queries.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
