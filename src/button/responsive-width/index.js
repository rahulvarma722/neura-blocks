/**
 * Responsive Width — a custom style control that behaves like a core one.
 *
 * What this demonstrates:
 *
 *   1. A custom attribute can be made per-viewport WITHOUT inventing
 *      `…Tablet` / `…Mobile` attributes, by storing it inside core's `style`
 *      object under core's own viewport-state keys. See `constants.js`.
 *
 *   2. The control reads and writes only the state currently being edited, so
 *      with Responsive styles OFF it shows and edits the Desktop value alone —
 *      exactly like a core control. See `use-style-state.js`.
 *
 *   3. Which inspector group a fill declares decides whether it survives the
 *      responsive view at all. Flip `EXPERIMENT_GROUP` to see it.
 *
 * @package BlockKit
 */

import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	Notice,
} from '@wordpress/components';

import {
	EXPERIMENT_GROUP,
	WIDTH_UNITS,
	DESKTOP,
	SHOW_DIAGNOSTICS,
} from './constants';
import { getStateValue, setStateValue } from './style-value';

/**
 * The width control, plus the per-state help text that explains itself.
 *
 * @param {Object}   props
 * @param {string}   props.clientId      Block client ID, used as the panel ID.
 * @param {?Object}  props.style         The block's `style` attribute.
 * @param {Function} props.setAttributes Block attribute setter.
 * @param {string}   props.device        Core device name.
 * @param {?string}  props.stateKey      Active state, or null for the base.
 * @param {Object}   props.diagnostics   Live detection state, for the readout.
 * @return {Element} The control.
 */
export default function ResponsiveWidthControl( {
	clientId,
	style,
	setAttributes,
	device,
	stateKey,
	diagnostics,
} ) {
	const units = useCustomUnits( { availableUnits: WIDTH_UNITS } );

	// Only ever the value for the state being edited — never the inherited
	// one. An empty field on Tablet means "no tablet override", which is the
	// truth the user needs in order to decide whether to add one.
	const value = getStateValue( style, stateKey );

	const onChange = ( next ) => {
		setAttributes( {
			style: setStateValue( style, stateKey, next || undefined ),
		} );
	};

	const label = stateKey
		? sprintf(
				/* translators: %s: device name, e.g. Tablet. */
				__( 'Custom Width (%s)', 'blockkit' ),
				device
		  )
		: __( 'Custom Width', 'blockkit' );

	return (
		<InspectorControls
			group={ EXPERIMENT_GROUP }
			resetAllFilter={ ( attributes ) => ( {
				// Scoped to the state being edited, so "Reset all" on Tablet
				// clears the tablet override and reveals the Desktop value
				// instead of destroying it.
				//
				// Core scopes its OWN filters automatically, via
				// `scopeResetAllFilterToState` in
				// `inspector-controls/fill.js` — but that reads
				// `useBlockStyleState()`, whose context comes from the fill's
				// position in the React tree. Our fill sits outside core's
				// `BlockStyleStateProvider` (`hooks/style.js:885`), so it
				// would read the DEFAULT state and wipe the base value. Doing
				// the scoping ourselves is the fix.
				style: setStateValue( attributes?.style, stateKey, undefined ),
			} ) }
		>
			<ToolsPanelItem
				hasValue={ () => undefined !== value }
				label={ label }
				onDeselect={ () => onChange( undefined ) }
				resetAllFilter={ () => ( {
					style: setStateValue( style, stateKey, undefined ),
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ label }
					labelPosition="top"
					value={ value }
					onChange={ onChange }
					units={ units }
					min={ 0 }
					placeholder={ __( 'auto', 'blockkit' ) }
				/>

				{ stateKey && (
					<Notice status="info" isDismissible={ false }>
						{ sprintf(
							/* translators: %s: device name, e.g. Tablet. */
							__(
								'Applies to %s only. Clearing it falls back to the Desktop value, not to a wider device.',
								'blockkit'
							),
							device
						) }
					</Notice>
				) }

				{ ! stateKey && DESKTOP !== device && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'Editing the Desktop value, which applies at every width. Turn on Responsive styles from View to set this device separately.',
							'blockkit'
						) }
					</Notice>
				) }

				{ SHOW_DIAGNOSTICS && (
					<pre
						style={ {
							fontSize: '11px',
							lineHeight: 1.5,
							whiteSpace: 'pre-wrap',
							background: '#f0f0f0',
							padding: '8px',
							margin: '8px 0 0',
						} }
					>
						{ [
							`group        ${ EXPERIMENT_GROUP }`,
							`device       ${ diagnostics.device }`,
							`toolbarProbe ${ diagnostics.toolbarShowsStyleState }`,
							`stylesProbe  ${ diagnostics.inspectorShowsNormalView }`,
							`editing      ${ diagnostics.stateKey }`,
							'—',
							`base         ${ getStateValue( style, null ) ?? '—' }`,
							`@tablet      ${ getStateValue( style, '@tablet' ) ?? '—' }`,
							`@mobile      ${ getStateValue( style, '@mobile' ) ?? '—' }`,
						].join( '\n' ) }
					</pre>
				) }
			</ToolsPanelItem>
		</InspectorControls>
	);
}
