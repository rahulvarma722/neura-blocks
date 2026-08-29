/**
 * Loading the icon library from core's REST API.
 *
 * WHY REST AND NOT A BUNDLED LIST.
 *
 * WordPress 7.1 keeps icons in a PHP registry (WP_Icons_Registry) and exposes
 * it over `/wp/v2/icons`. Registration is PHP-only — `wp_register_icon()` and
 * `wp_register_icon_collection()` — and the editor is expected to read the
 * result back over REST.
 *
 * That is worth leaning on rather than working around. Bundling a copy of the
 * icon set in JavaScript would mean the picker and the renderer each holding
 * their own list, drifting apart the moment either changed — which is exactly
 * the problem the button block has today, where icon.js and render.php keep
 * four SVG paths in step by hand.
 *
 * It also means any collection a plugin registers in PHP appears here with no
 * JavaScript at all.
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Every registered icon, fetched once per editor session.
 *
 * Cached at module scope rather than in component state: the list is the same
 * for every Icon block on the page, and there can be many. Without this each
 * one would issue its own request.
 *
 * @type {?Promise<Array>}
 */
let request = null;

/**
 * Fetches the icon library, reusing the in-flight or completed request.
 *
 * @return {Promise<Array>} Icons, or an empty array if the request fails.
 */
function fetchIcons() {
	if ( ! request ) {
		request = apiFetch( { path: '/wp/v2/icons?per_page=100' } )
			.then( ( icons ) => ( Array.isArray( icons ) ? icons : [] ) )
			.catch( () => {
				/*
				 * A failed request must not leave the promise cached, or the
				 * picker stays empty for the rest of the session with no way
				 * to retry. Clearing it means the next block that mounts tries
				 * again.
				 */
				request = null;
				return [];
			} );
	}

	return request;
}

/**
 * The icon library, and whether it is still loading.
 *
 * @return {{icons: Array, isLoading: boolean}} Library state.
 */
export function useIcons() {
	const [ icons, setIcons ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		let cancelled = false;

		fetchIcons().then( ( result ) => {
			// The block may have been removed while the request was in flight.
			// Setting state on an unmounted component is a React warning and,
			// more usefully, a sign of a leak.
			if ( ! cancelled ) {
				setIcons( result );
				setIsLoading( false );
			}
		} );

		return () => {
			cancelled = true;
		};
	}, [] );

	return { icons, isLoading };
}

/**
 * Finds one icon in the library by its namespaced name.
 *
 * @param {Array}   icons Library from useIcons().
 * @param {?string} name  Namespaced name, e.g. `core/star-filled`.
 * @return {?Object} The icon, or undefined.
 */
export function findIcon( icons, name ) {
	return name ? icons.find( ( icon ) => icon.name === name ) : undefined;
}
