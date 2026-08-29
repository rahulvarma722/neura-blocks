/**
 * Kit Icon — editor.
 */

import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	SelectControl,
	ToggleControl,
	RangeControl,
	TextControl,
	Placeholder,
	Spinner,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

import { useIcons, findIcon } from './use-icons';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { icon, flipHorizontal, flipVertical, rotation, label } = attributes;

	const { icons, isLoading } = useIcons();
	const selected = findIcon( icons, icon );

	const blockProps = useBlockProps( {
		className: [
			flipHorizontal ? 'is-flip-horizontal' : '',
			flipVertical ? 'is-flip-vertical' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const options = [
		{ value: '', label: __( 'Select an icon…', 'blockkit' ) },
		...icons.map( ( item ) => ( {
			value: item.name,
			label: item.label || item.name,
		} ) ),
	];

	const controls = (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Icon', 'blockkit' ) }
				resetAll={ () =>
					setAttributes( {
						flipHorizontal: false,
						flipVertical: false,
						rotation: 0,
						label: '',
					} )
				}
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! icon }
					label={ __( 'Icon', 'blockkit' ) }
					onDeselect={ () => setAttributes( { icon: '' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Icon', 'blockkit' ) }
						value={ icon }
						options={ options }
						onChange={ ( value ) =>
							setAttributes( { icon: value } )
						}
						disabled={ isLoading }
						help={
							isLoading
								? __( 'Loading the icon library…', 'blockkit' )
								: sprintf(
										/* translators: %d: number of icons available. */
										__( '%d icons available.', 'blockkit' ),
										icons.length
								  )
						}
					/>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => flipHorizontal || flipVertical }
					label={ __( 'Flip', 'blockkit' ) }
					onDeselect={ () =>
						setAttributes( {
							flipHorizontal: false,
							flipVertical: false,
						} )
					}
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Flip horizontally', 'blockkit' ) }
						checked={ !! flipHorizontal }
						onChange={ ( value ) =>
							setAttributes( { flipHorizontal: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Flip vertically', 'blockkit' ) }
						checked={ !! flipVertical }
						onChange={ ( value ) =>
							setAttributes( { flipVertical: value } )
						}
					/>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => !! rotation }
					label={ __( 'Rotation', 'blockkit' ) }
					onDeselect={ () => setAttributes( { rotation: 0 } ) }
					panelId={ clientId }
				>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Rotation', 'blockkit' ) }
						value={ rotation }
						onChange={ ( value ) =>
							setAttributes( { rotation: value ?? 0 } )
						}
						min={ 0 }
						max={ 359 }
						step={ 1 }
						/*
						 * The quarter turns cover almost every real use, but a
						 * free range is what makes the control worth having
						 * over four preset buttons.
						 */
						marks={ [
							{ value: 0, label: '0°' },
							{ value: 90, label: '90°' },
							{ value: 180, label: '180°' },
							{ value: 270, label: '270°' },
						] }
					/>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => !! label }
					label={ __( 'Alternative text', 'blockkit' ) }
					onDeselect={ () => setAttributes( { label: '' } ) }
					panelId={ clientId }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Alternative text', 'blockkit' ) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						help={ __(
							'Describe the icon only if it carries meaning of its own. Leave empty for decoration, and it will be hidden from screen readers.',
							'blockkit'
						) }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);

	if ( isLoading && ! icon ) {
		return (
			<>
				{ controls }
				<div { ...blockProps }>
					<Placeholder
						icon="star-filled"
						label={ __( 'Kit Icon', 'blockkit' ) }
					>
						<Spinner />
					</Placeholder>
				</div>
			</>
		);
	}

	if ( ! selected ) {
		return (
			<>
				{ controls }
				<div { ...blockProps }>
					<Placeholder
						icon="star-filled"
						label={ __( 'Kit Icon', 'blockkit' ) }
						instructions={ __(
							'Choose an icon in the block settings.',
							'blockkit'
						) }
					/>
				</div>
			</>
		);
	}

	return (
		<>
			{ controls }
			<div { ...blockProps }>
				{ /*
				 * The SVG markup comes from core's icon registry over an
				 * authenticated REST endpoint (`edit_posts`), and is the same
				 * markup wp_get_icon() prints on the front end. There is no
				 * React equivalent — the registry stores markup, not
				 * components — so this is the only way to preview it, and
				 * rendering anything else here would mean the canvas showing
				 * something the front end does not produce.
				 */ }
				<span
					className="wp-block-blockkit-icon__svg"
					style={
						rotation
							? { rotate: `${ rotation % 360 }deg` }
							: undefined
					}
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ { __html: selected.content } }
				/>
			</div>
		</>
	);
}
