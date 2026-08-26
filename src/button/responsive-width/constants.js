/**
 * Responsive Width — experiment constants.
 *
 * @package BlockKit
 */

/**
 * Which inspector group the control fills.
 *
 * THE EXPERIMENT BENCH. Change this value, rebuild, and observe. The three
 * settings answer three different questions about WordPress 7.1.
 *
 * 'dimensions'  The working setting. `dimensions` is one of the seven slots
 *               `StyleStateInspectorSlots` renders, so the control appears in
 *               the Styles tab on Desktop AND in the responsive view on
 *               Tablet/Mobile. This is the only arrangement that works today.
 *
 * 'styles'      Reproduces the bug. `styles` is the general-purpose group for
 *               third-party panels and the one core renders UNLABELLED, so a
 *               fill can bring its own ToolsPanel and heading. It is rendered
 *               in the Styles tab (`styles-tab.js:159`) but NOT by
 *               `StyleStateInspectorSlots`. Result: the control is visible on
 *               Desktop and disappears the moment you switch to Tablet or
 *               Mobile with Responsive styles on — while its stored values
 *               stay in the post content, unreachable.
 *
 * 'viewport'    The negative control. This group does not exist in 7.1; it is
 *               added by the still-open draft PR #82003. Filling it here makes
 *               core log `Unknown InspectorControls group "viewport" provided.`
 *               and render nothing — which is the proof that the group registry
 *               (`inspector-controls/groups.js`) is closed to plugins. When
 *               #82003 ships, this same value should start working AND keep the
 *               control's own panel, because core renders that slot unlabelled.
 *
 * @type {string}
 */
export const EXPERIMENT_GROUP = 'dimensions';

/**
 * Where the value lives inside core's `style` attribute.
 *
 * The whole point of the experiment: rather than inventing our own
 * `buttonWidth` / `buttonWidthTablet` / `buttonWidthMobile` attributes, the
 * value is stored INSIDE core's `style` object, under a namespaced key, using
 * core's own viewport-state shape:
 *
 *   style: {
 *     blockkit: { width: '200px' },                  // base — applies everywhere
 *     '@tablet': { blockkit: { width: '150px' } },   // tablet band only
 *     '@mobile': { blockkit: { width: '100%'  } },   // mobile band only
 *   }
 *
 * `style` is declared by core as a free-form `{ type: 'object' }` attribute
 * (`block-editor/src/hooks/style.js:546`) with no key allowlist, so a
 * namespaced key survives serialization untouched. Core generates CSS only for
 * paths it knows, so nothing of ours is emitted by core — see render.php.
 *
 * Storing it this way is what makes the control behave like a core one: the
 * state the user is editing decides which value is read and written, so with
 * Responsive styles off only the base value is ever visible or editable.
 *
 * @type {string}
 */
export const STYLE_NAMESPACE = 'blockkit';

/**
 * The key under the namespace.
 *
 * @type {string}
 */
export const WIDTH_KEY = 'width';

/**
 * Core's device names, as returned by `select( 'core/editor' ).getDeviceType()`.
 */
export const DESKTOP = 'Desktop';
export const TABLET = 'Tablet';
export const MOBILE = 'Mobile';

/**
 * Device name to the viewport-state key core uses inside `style`.
 *
 * Desktop maps to null rather than a key, because Desktop IS the base layer —
 * it carries no media query and applies at every width. That asymmetry is
 * core's model, not a simplification: `@tablet` and `@mobile` are overrides on
 * top of the base, and they are mutually exclusive, so mobile falls back to the
 * BASE when unset, never to tablet.
 *
 * @type {Object<string, ?string>}
 */
export const VIEWPORT_STATE_BY_DEVICE = {
	[ DESKTOP ]: null,
	[ TABLET ]: '@tablet',
	[ MOBILE ]: '@mobile',
};

/**
 * Units offered by the control.
 *
 * `%` earns its place here: "full width on mobile, auto on desktop" is the
 * canonical responsive button requirement, and it is the case that makes a
 * per-viewport value obviously different from a single global one.
 *
 * @type {Array<Object>}
 */
export const WIDTH_UNITS = [
	{ value: 'px', label: 'px', default: 200 },
	{ value: '%', label: '%', default: 100 },
	{ value: 'em', label: 'em', default: 10 },
	{ value: 'rem', label: 'rem', default: 10 },
];

/**
 * Whether to print the live detection state into the inspector.
 *
 * On for the experiment: it shows the device, both probe readings, the resolved
 * state and the stored values, so the mechanism can be watched rather than
 * guessed at. Set to false and rebuild for a clean UI.
 *
 * @type {boolean}
 */
export const SHOW_DIAGNOSTICS = true;
