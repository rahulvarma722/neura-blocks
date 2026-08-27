/**
 * Kit Text — editor.
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	BlockControls,
} from '@wordpress/block-editor';
import {
	SelectControl,
	Notice,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	ToolbarDropdownMenu,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';

import {
	STYLE_PRESETS,
	TAG_LABELS,
	FALLBACK_TAGS,
	HEADING_TAGS,
} from './constants';
import { useOutlineCheck } from './use-outline-check';

/**
 * Tags this site offers.
 *
 * Localised from PHP by BlockKit\Blocks, because the vocabulary is a SETTING —
 * a site can switch optional tags on, and a Pro add-on can add its own through
 * the `blockkit_text_tags` filter. Hardcoding the list in JS would mean the
 * dropdown and the renderer disagreeing the moment either changed.
 *
 * @return {string[]} Tag names.
 */
function useAvailableTags() {
	return useMemo( () => {
		const localised = window.blockkitText?.tags;

		return Array.isArray( localised ) && localised.length
			? localised
			: FALLBACK_TAGS;
	}, [] );
}

export default function Edit( {
	attributes,
	setAttributes,
	clientId,
	mergeBlocks,
	onReplace,
	style,
} ) {
	const { content, tagName, styleAs, placeholder } = attributes;

	const tags = useAvailableTags();
	const outlineWarning = useOutlineCheck( clientId, tagName );

	/*
	 * `styleAs` is a class, not an inline style.
	 *
	 * A class lets style.scss resolve the preset against the theme's own
	 * font-size presets with a fallback, and lets a theme override the whole
	 * scale in one place. An inline font-size would win against the theme
	 * forever and could not be themed at all.
	 */
	const blockProps = useBlockProps( {
		className: styleAs ? `has-style-${ styleAs }` : undefined,
		style,
	} );

	// `span` is inline: rendering the editable as a block-level element would
	// show the author a layout the front end does not produce.
	const TagName = tags.includes( tagName ) ? tagName : 'p';

	return (
		<>
			<BlockControls group="block">
				<ToolbarDropdownMenu
					icon="heading"
					label={ __( 'Change HTML tag', 'blockkit' ) }
					controls={ tags.map( ( tag ) => ( {
						title: TAG_LABELS[ tag ] || tag,
						isActive: tag === tagName,
						onClick: () => setAttributes( { tagName: tag } ),
					} ) ) }
				/>
			</BlockControls>

			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'Markup', 'blockkit' ) }
					resetAll={ () =>
						setAttributes( { tagName: 'p', styleAs: '' } )
					}
					panelId={ clientId }
				>
					<ToolsPanelItem
						hasValue={ () => 'p' !== tagName }
						label={ __( 'HTML tag', 'blockkit' ) }
						onDeselect={ () => setAttributes( { tagName: 'p' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'HTML tag', 'blockkit' ) }
							value={ tagName }
							options={ tags.map( ( tag ) => ( {
								value: tag,
								label: TAG_LABELS[ tag ] || tag,
							} ) ) }
							onChange={ ( value ) =>
								setAttributes( { tagName: value } )
							}
							help={ __(
								'What this text MEANS — used by screen readers, search engines and the document outline. It does not control how the text looks.',
								'blockkit'
							) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! styleAs }
						label={ __( 'Style as', 'blockkit' ) }
						onDeselect={ () => setAttributes( { styleAs: '' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Style as', 'blockkit' ) }
							value={ styleAs }
							options={ STYLE_PRESETS }
							onChange={ ( value ) =>
								setAttributes( { styleAs: value } )
							}
							help={ __(
								'How this text LOOKS, independent of the tag. Set an H2 for the outline and style it as a caption, or the reverse.',
								'blockkit'
							) }
						/>
					</ToolsPanelItem>

					{ !! outlineWarning && (
						<Notice status="warning" isDismissible={ false }>
							{ outlineWarning }
						</Notice>
					) }
				</ToolsPanel>
			</InspectorControls>

			<RichText
				{ ...blockProps }
				identifier="content"
				tagName={ TagName }
				value={ content }
				onChange={ ( value ) => setAttributes( { content: value } ) }
				onMerge={ mergeBlocks }
				onReplace={ onReplace }
				placeholder={
					placeholder ||
					( HEADING_TAGS.includes( tagName )
						? __( 'Heading…', 'blockkit' )
						: __( 'Text…', 'blockkit' ) )
				}
			/>
		</>
	);
}
