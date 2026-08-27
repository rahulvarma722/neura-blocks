/**
 * Checking this block's heading level against the rest of the document.
 *
 * WHY THIS EXISTS, AND WHY IT PAIRS WITH styleAs.
 *
 * Decoupling the tag from the visual style removes the reason people break a
 * heading outline — you no longer have to pick `h4` to get h4's size. But
 * removing the reason is not the same as helping, so the block also says when
 * the outline is wrong.
 *
 * Two rules, both from WCAG 1.3.1 / H42 and both things core never mentions:
 *
 *   - Levels must not SKIP. h2 followed by h4 leaves a screen-reader user
 *     wondering what happened to the h3, and it breaks the document outline
 *     assistive technology builds from headings.
 *   - There should be ONE h1 per document. Themes usually render the post
 *     title as one already, which is why a second is worth flagging rather
 *     than assuming.
 *
 * These are WARNINGS, never enforcement. A single-heading landing page with one
 * h1 and one h3 is unusual but not wrong, and a block that refused to save
 * would be worse than one that mentions it. The notice is dismissible by
 * ignoring it.
 */

import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';

import { HEADING_TAGS } from './constants';

/**
 * Pulls every heading in the document, in order, from the block tree.
 *
 * Walks `getBlocks()` recursively rather than reading a flat list: headings
 * nested inside a Group or Columns are still part of the document outline, and
 * a flat read would report a false "skipped level" the moment somebody used a
 * layout block.
 *
 * @param {Array} blocks Block list from the editor store.
 * @return {Array<{clientId: string, level: number}>} Headings in document order.
 */
function collectHeadings( blocks ) {
	const headings = [];

	const walk = ( list ) => {
		list.forEach( ( block ) => {
			const { name, attributes, innerBlocks, clientId } = block;

			let tag = null;

			if ( 'blockkit/text' === name ) {
				tag = attributes?.tagName;
			} else if ( 'core/heading' === name && attributes?.level ) {
				// Core headings count. The outline is the DOCUMENT's, not this
				// plugin's, so ignoring core/heading would report nonsense on
				// any page that mixes the two.
				tag = `h${ attributes.level }`;
			}

			if ( tag && HEADING_TAGS.includes( tag ) ) {
				headings.push( {
					clientId,
					level: parseInt( tag.slice( 1 ), 10 ),
				} );
			}

			if ( innerBlocks?.length ) {
				walk( innerBlocks );
			}
		} );
	};

	walk( blocks );

	return headings;
}

/**
 * Returns a warning about this block's heading level, or null.
 *
 * @param {string}  clientId This block's client ID.
 * @param {?string} tagName  This block's tag.
 * @return {?string} A human-readable warning, or null when the outline is fine.
 */
export function useOutlineCheck( clientId, tagName ) {
	const isHeading = HEADING_TAGS.includes( tagName );

	return useSelect(
		( select ) => {
			if ( ! isHeading ) {
				return null;
			}

			const headings = collectHeadings(
				select( blockEditorStore ).getBlocks()
			);

			const index = headings.findIndex(
				( heading ) => heading.clientId === clientId
			);

			if ( -1 === index ) {
				return null;
			}

			const level = headings[ index ].level;

			if ( 1 === level ) {
				const earlierH1 = headings
					.slice( 0, index )
					.some( ( heading ) => 1 === heading.level );

				return earlierH1
					? __(
							'This is a second Heading 1. Most themes already output the post title as the page’s only H1.',
							'blockkit'
					  )
					: null;
			}

			// Compare against the nearest preceding heading, which is what
			// defines whether this one skipped a level.
			const previous = headings[ index - 1 ];

			if ( ! previous ) {
				return 1 === level
					? null
					: sprintf(
							/* translators: %d: heading level, e.g. 3. */
							__(
								'The first heading on the page is a Heading %d. Outlines normally start at Heading 1 or 2.',
								'blockkit'
							),
							level
					  );
			}

			if ( level > previous.level + 1 ) {
				return sprintf(
					/* translators: 1: previous heading level, 2: this heading level. */
					__(
						'Heading level skipped: this follows a Heading %1$d, so Heading %2$d leaves a gap. Change the tag, and use Style as to keep the size you want.',
						'blockkit'
					),
					previous.level,
					level
				);
			}

			return null;
		},
		/*
		 * `tagName` is deliberately absent.
		 *
		 * The selector never reads it: this block's own level is read back out
		 * of getBlocks() like every other heading's. So changing the tag
		 * changes the STORE, useSelect re-runs on that, and the new level is
		 * picked up. Listing tagName as well would be a dependency the
		 * selector does not have — and the linter is right to say so.
		 *
		 * `isHeading` IS listed, because it is computed outside and short-
		 * circuits the selector.
		 */
		[ clientId, isHeading ]
	);
}
