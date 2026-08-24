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
import { useState } from '@wordpress/element';

const NEW_TAB_TARGET = '_blank';
const NOFOLLOW_REL = 'noreferrer noopener';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { text, url, linkTarget, rel, title } = attributes;

	const [ isEditingLink, setIsEditingLink ] = useState( false );

	// The block root. render.php puts the same classes on its <a>.
	const blockProps = useBlockProps();

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
