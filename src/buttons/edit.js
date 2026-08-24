/**
 * Buttons — editor component.
 *
 * A container block. `useInnerBlocksProps` merges the block's own props
 * with the inner-block list, so the layout support's flex classes land
 * on the same element that holds the children.
 */

import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/**
 * Inserted when an empty Buttons block is added.
 *
 * One child, so the block is never a blank container the user has to
 * figure out how to fill.
 */
const TEMPLATE = [ [ 'blockkit/button' ] ];

export default function Edit( { clientId } ) {
	const hasChildren = useSelect(
		( select ) =>
			select( blockEditorStore ).getBlockCount( clientId ) > 0,
		[ clientId ]
	);

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		/*
		 * Show the full inserter while empty, then the compact appender
		 * once there is at least one button — matching how core's
		 * container blocks behave.
		 */
		renderAppender: hasChildren
			? undefined
			: false,
	} );

	return <div { ...innerBlocksProps } />;
}
