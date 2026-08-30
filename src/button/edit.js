/**
 * Button — editor component.
 *
 * `useBlockProps()` carries everything core generates from the block's
 * `supports`, including its responsive and pseudo-state rules. It is
 * spread onto the same element render.php uses as the block root, so the
 * editor and the front end style the same node.
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	BlockControls,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	ToolbarButton,
	Popover,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';

import { ButtonIcon, ICON_OPTIONS } from './icon';
import ResponsiveWidthControl from './responsive-width';
import { useStyleState } from './responsive-width/use-style-state';
import { getResolvedValue } from './responsive-width/style-value';
import { ICON_SIZE_KEY, CSS_VARS } from './responsive-width/constants';

const NEW_TAB_TARGET = '_blank';
const NOFOLLOW_REL = 'noreferrer noopener';

export default function Edit( {
	attributes,
	setAttributes,
	isSelected,
	clientId,
} ) {
	const { text, url, linkTarget, rel, title, style, icon, iconPosition } =
		attributes;

	const [ isEditingLink, setIsEditingLink ] = useState( false );

	// Which viewport state the inspector is editing, and the probe that makes
	// the answer knowable at all. See responsive-width/use-style-state.js.
	const { device, stateKey, diagnostics, Probes } = useStyleState();

	/*
	 * Preview the width for the device being previewed, not for the state
	 * being edited. Those differ: with Responsive styles off on Tablet the
	 * canvas is narrow (so the front end would apply any tablet override) while
	 * the control is editing the base. Resolving by DEVICE keeps the canvas
	 * honest about what a visitor at that width would see.
	 *
	 * Inline rather than a media query because the editor canvas is already
	 * sized to the device; render.php emits the real media queries.
	 */
	const previewWidth = getResolvedValue( style, device );

	/*
	 * The icon size resolves exactly like the width — same layer, same
	 * fallback — but lands on the root as a CUSTOM PROPERTY rather than as a
	 * real declaration, because the element it styles is a child.
	 *
	 * This is the whole descendant technique: the platform lets us write to the
	 * block root, so the root carries a variable and the stylesheet spends it on
	 * `.wp-block-neura-blocks-button__icon`. No selector plumbing, and it behaves
	 * identically in the canvas and on the front end because both read the same
	 * rule from style.scss.
	 */
	const previewIconSize = getResolvedValue( style, device, ICON_SIZE_KEY );

	/*
	 * Memoised deliberately. `useBlockProps()` feeds whatever it is given into
	 * `useMergeRefs()`, so handing it a fresh object literal on every render
	 * churns the block root's ref and the props RichText spreads onto its
	 * contentEditable — which is enough to disturb typing. A stable object
	 * means the identity only changes when the previewed width actually does.
	 */
	const extraBlockProps = useMemo( () => {
		const inline = {};

		if ( previewWidth ) {
			inline.width = previewWidth;
		}

		if ( previewIconSize ) {
			inline[ CSS_VARS.iconSize ] = previewIconSize;
		}

		return {
			...( Object.keys( inline ).length ? { style: inline } : {} ),
			// Mirrors render.php, so the icon sits on the same side in both.
			...( icon && 'left' === iconPosition
				? { className: 'has-icon-left' }
				: {} ),
		};
	}, [ previewWidth, previewIconSize, icon, iconPosition ] );

	// The block root. render.php puts the same classes on its <a>.
	const blockProps = useBlockProps( extraBlockProps );

	const unlink = () => {
		setAttributes( { url: '', linkTarget: '', rel: '' } );
		setIsEditingLink( false );
	};

	return (
		<>
			<BlockControls group="block">
				{ ! url && (
					<ToolbarButton
						name="link"
						icon="admin-links"
						title={ __( 'Link', 'neura-blocks' ) }
						onClick={ () => setIsEditingLink( true ) }
					/>
				) }
				{ !! url && (
					<ToolbarButton
						name="unlink"
						icon="editor-unlink"
						title={ __( 'Unlink', 'neura-blocks' ) }
						onClick={ unlink }
						isActive
					/>
				) }
			</BlockControls>

			<Probes />

			<ResponsiveWidthControl
				clientId={ clientId }
				style={ style }
				setAttributes={ setAttributes }
				device={ device }
				stateKey={ stateKey }
				diagnostics={ diagnostics }
			/>

			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'neura-blocks' ) }>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Icon', 'neura-blocks' ) }
						value={ icon }
						options={ ICON_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { icon: value } )
						}
					/>

					{ !! icon && (
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Icon position', 'neura-blocks' ) }
							value={ iconPosition }
							onChange={ ( value ) =>
								setAttributes( { iconPosition: value } )
							}
							isBlock
						>
							<ToggleGroupControlOption
								value="left"
								label={ __( 'Left', 'neura-blocks' ) }
							/>
							<ToggleGroupControlOption
								value="right"
								label={ __( 'Right', 'neura-blocks' ) }
							/>
						</ToggleGroupControl>
					) }

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Link rel', 'neura-blocks' ) }
						value={ rel }
						onChange={ ( value ) =>
							setAttributes( { rel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Title attribute', 'neura-blocks' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						help={ __(
							'Describes the link destination for assistive technology.',
							'neura-blocks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			{ /*
			 * A span in the editor rather than an anchor, so a click
			 * selects the block instead of following the link.
			 */ }
			{ /*
			 * RichText can no longer BE the block root: the root now holds two
			 * children, the text and the icon. So blockProps moves to a
			 * wrapping span and RichText becomes an ordinary child — which is
			 * also what render.php does.
			 */ }
			<span { ...blockProps }>
				<RichText
					tagName="span"
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					placeholder={ __( 'Add text…', 'neura-blocks' ) }
					allowedFormats={ [] }
					identifier="text"
				/>
				<ButtonIcon icon={ icon } />
			</span>

			{ isSelected && isEditingLink && (
				<Popover
					placement="bottom"
					onClose={ () => setIsEditingLink( false ) }
					focusOnMount="firstElement"
				>
					<LinkControl
						value={ {
							url,
							opensInNewTab: linkTarget === NEW_TAB_TARGET,
						} }
						onChange={ ( {
							url: newUrl = '',
							opensInNewTab: newOpensInNewTab,
						} ) => {
							setAttributes( {
								url: newUrl,
								linkTarget: newOpensInNewTab
									? NEW_TAB_TARGET
									: '',
								rel: newOpensInNewTab ? NOFOLLOW_REL : '',
							} );
						} }
						onRemove={ unlink }
						forceIsEditingLink={ ! url }
					/>
				</Popover>
			) }
		</>
	);
}
