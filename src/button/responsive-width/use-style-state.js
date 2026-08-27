/**
 * Detecting which style state the editor is in — from a plugin.
 *
 * This is the hard part of aligning a custom attribute with core's
 * per-viewport behaviour, and it is worth explaining why it takes three
 * signals instead of one store selector.
 *
 * Core knows the answer precisely. `isResponsiveEditing()` and
 * `getSelectedBlockStyleState()` live in
 * `block-editor/src/store/private-selectors.js`, are registered through
 * `registerPrivateSelectors`, and are reachable only via `unlock()` — which
 * throws for anything that is not a core package
 * (`private-apis/src/implementation.ts:86`). `useBlockStyleState` and
 * `BlockStyleStateProvider` are not exported from the package at all. So a
 * plugin cannot read the state directly, and there is no filter for it.
 *
 * What a plugin CAN read, publicly and reactively:
 *
 *   1. WHICH device is selected. `select( 'core/editor' ).getDeviceType()` is
 *      public (`editor/src/store/selectors.js:1346`), and core derives the
 *      viewport style state from the same canvas width (`setCanvasWidth` in
 *      `editor/src/store/private-actions.js`), so the two can never disagree
 *      about which viewport is being edited.
 *
 *   2. Whether core is showing its STYLE-STATE TOOLBAR. `BlockControls` has a
 *      public `style-state` group (`block-controls/groups.js`) whose slot
 *      `block-toolbar/index.js:159` renders only when
 *      `isResponsiveEditing() && hasViewportBlockStyleState( … )`. That is an
 *      exact mirror of core's own condition — the closest thing to reading the
 *      private selector.
 *
 *   3. Whether the inspector is showing its NORMAL style view. The `styles`
 *      inspector group is rendered by the Styles tab (`styles-tab.js:159`) and
 *      by `StyleInspectorSlots`, but NOT by `StyleStateInspectorSlots`. So a
 *      fill there is present exactly when the responsive view is absent.
 *
 * Rather than sniffing core's DOM for
 * `.editor-preview-dropdown.is-responsive-editing` — which is not reactive and
 * breaks whenever core's markup changes — each signal is obtained by mounting
 * an invisible component inside the slot that gates it and letting it report
 * its own presence. Core's own render decisions become our input, and they
 * update on their own.
 *
 * Signals 2 and 3 are combined rather than trusting either alone:
 *
 *   - Signal 2 alone is fragile because the block toolbar unmounts while the
 *     user is typing (`isBlockInterfaceHidden`), which would flip the control
 *     back to editing the base layer mid-edit.
 *   - Signal 3 alone cannot distinguish "responsive view active" from
 *     "inspector closed" or "Settings tab open". That ambiguity is harmless
 *     here, because in both of those cases the control is not rendered either,
 *     so nothing reads the result.
 *
 * Together they cover each other. The one remaining inaccuracy is a closed
 * inspector on a narrow device with Responsive styles off, which reads as
 * "state active" — and is invisible, because the control is not on screen and
 * the canvas preview resolves by device rather than by state.
 */

import { useEffect, useState, useCallback, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';

import { DESKTOP, VIEWPORT_STATE_BY_DEVICE } from './constants';

/**
 * Reports its own mount state to the parent. Renders no UI.
 *
 * @param {Object}   props
 * @param {Function} props.onChange Called with true on mount, false on unmount.
 * @return {null} Nothing.
 */
function Probe( { onChange } ) {
	useEffect( () => {
		onChange( true );
		return () => onChange( false );
	}, [ onChange ] );

	return null;
}

/**
 * Which style state the block inspector is currently editing.
 *
 * @return {Object} `{ device, stateKey, isEditingViewportState, diagnostics,
 *                  Probes }`. `stateKey` is null for the base layer, or
 *                  `'@tablet'` / `'@mobile'`. `Probes` MUST be rendered by the
 *                  caller — the detection does not work without it.
 */
export function useStyleState() {
	const [ toolbarShowsStyleState, setToolbarShowsStyleState ] =
		useState( false );
	const [ inspectorShowsNormalView, setInspectorShowsNormalView ] =
		useState( false );

	const device = useSelect(
		( select ) => select( 'core/editor' )?.getDeviceType?.() ?? DESKTOP,
		[]
	);

	// Stable identities, so the probes' effects do not re-run every render.
	const onToolbarProbe = useCallback( ( value ) => {
		setToolbarShowsStyleState( value );
	}, [] );

	const onInspectorProbe = useCallback( ( value ) => {
		setInspectorShowsNormalView( value );
	}, [] );

	const isNarrowDevice =
		null !== ( VIEWPORT_STATE_BY_DEVICE[ device ] ?? null );

	const isEditingViewportState =
		toolbarShowsStyleState ||
		( isNarrowDevice && ! inspectorShowsNormalView );

	/*
	 * The base layer is the answer in two distinct situations, and conflating
	 * them is the bug this guard exists to prevent:
	 *
	 *   - Desktop is selected. Desktop IS the base — it carries no media query
	 *     and applies at every width.
	 *   - A narrow device is selected but Responsive styles is OFF. The device
	 *     is then only a preview; core writes every edit to the base layer, so
	 *     the control must read and write the base too. Writing to `@tablet`
	 *     here would store a value the user can neither see nor asked for.
	 */
	const stateKey = isEditingViewportState
		? VIEWPORT_STATE_BY_DEVICE[ device ] ?? null
		: null;

	/**
	 * Both probes, each wrapped in the slot that gates it.
	 *
	 * Returned as a component rather than rendered here, because a hook cannot
	 * render into the block's output on its own.
	 *
	 * @return {Element} The probes.
	 */
	const Probes = useCallback(
		() => (
			<>
				<BlockControls group="style-state">
					<Probe onChange={ onToolbarProbe } />
				</BlockControls>
				<InspectorControls group="styles">
					<Probe onChange={ onInspectorProbe } />
				</InspectorControls>
			</>
		),
		[ onToolbarProbe, onInspectorProbe ]
	);

	const diagnostics = useMemo(
		() => ( {
			device,
			isNarrowDevice,
			toolbarShowsStyleState,
			inspectorShowsNormalView,
			isEditingViewportState,
			stateKey: stateKey ?? 'base',
		} ),
		[
			device,
			isNarrowDevice,
			toolbarShowsStyleState,
			inspectorShowsNormalView,
			isEditingViewportState,
			stateKey,
		]
	);

	return {
		device,
		stateKey,
		isEditingViewportState,
		diagnostics,
		Probes,
	};
}
