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
	ToolbarButton,
	Popover,
} from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';

import ResponsiveWidthControl from './responsive-width';
import { useStyleState } from './responsive-width/use-style-state';
import { getResolvedValue } from './responsive-width/style-value';

const NEW_TAB_TARGET = '_blank';
const NOFOLLOW_REL = 'noreferrer noopener';

export default function Edit( {
	attributes,
	setAttributes,
	isSelected,
	clientId,
} ) {
	const { text, url, linkTarget, rel, title, style } = attributes;

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
	 * Memoised deliberately. `useBlockProps()` feeds whatever it is given into
	 * `useMergeRefs()`, so handing it a fresh object literal on every render
	 * churns the block root's ref and the props RichText spreads onto its
	 * contentEditable — which is enough to disturb typing. A stable object
	 * means the identity only changes when the previewed width actually does.
	 */
	const extraBlockProps = useMemo(
		() => ( previewWidth ? { style: { width: previewWidth } } : {} ),
		[ previewWidth ]
	);

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
						title={ __( 'Link', 'blockkit' ) }
						onClick={ () => setIsEditingLink( true ) }
					/>
				) }
				{ !! url && (
					<ToolbarButton
						name="unlink"
						icon="editor-unlink"
						title={ __( 'Unlink', 'blockkit' ) }
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
				<PanelBody title={ __( 'Settings', 'blockkit' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Link rel', 'blockkit' ) }
						value={ rel }
						onChange={ ( value ) => setAttributes( { rel: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Title attribute', 'blockkit' ) }
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
						help={ __(
							'Describes the link destination for assistive technology.',
							'blockkit'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			{ /*
			  * A span in the editor rather than an anchor, so a click
			  * selects the block instead of following the link.
			  */ }
			<RichText
				{ ...blockProps }
				tagName="span"
				value={ text }
				onChange={ ( value ) => setAttributes( { text: value } ) }
				placeholder={ __( 'Add text…', 'blockkit' ) }
				allowedFormats={ [] }
				identifier="text"
			/>

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
