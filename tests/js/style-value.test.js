/**
 * Unit tests for the per-viewport value helpers.
 *
 * These are the JS counterparts of ResponsiveStylesTest.php, and they matter
 * for the same reason: read/write/resolve across three style-state layers is
 * pure logic, easy to get subtly wrong, and impossible to eyeball in the
 * editor — a value written to the wrong layer looks identical until you switch
 * device.
 *
 * Run with `npm run test:js`. No WordPress, no DOM, no build step.
 */

import {
	getStateValue,
	setStateValue,
	getResolvedValue,
} from '../../src/button/responsive-width/style-value';
import { ICON_SIZE_KEY } from '../../src/button/responsive-width/constants';

const style = () => ( {
	blockkit: { width: '200px' },
	'@tablet': { blockkit: { width: '150px' } },
	'@mobile': { blockkit: { width: '100%' } },
} );

describe( 'getStateValue', () => {
	it( 'reads each layer, with null meaning the base', () => {
		expect( getStateValue( style(), null ) ).toBe( '200px' );
		expect( getStateValue( style(), '@tablet' ) ).toBe( '150px' );
		expect( getStateValue( style(), '@mobile' ) ).toBe( '100%' );
	} );

	it( 'returns undefined rather than throwing on anything missing', () => {
		expect( getStateValue( undefined, null ) ).toBeUndefined();
		expect( getStateValue( {}, null ) ).toBeUndefined();
		expect( getStateValue( style(), '@print' ) ).toBeUndefined();
		expect( getStateValue( style(), null, 'nope' ) ).toBeUndefined();
	} );

	it( 'defaults to width so single-property callers are unchanged', () => {
		expect( getStateValue( style(), null ) ).toBe(
			getStateValue( style(), null, 'width' )
		);
	} );
} );

describe( 'getResolvedValue', () => {
	it( 'Desktop resolves to the base layer, which carries no media query', () => {
		expect( getResolvedValue( style(), 'Desktop' ) ).toBe( '200px' );
	} );

	it( 'a narrow device prefers its own layer', () => {
		expect( getResolvedValue( style(), 'Tablet' ) ).toBe( '150px' );
		expect( getResolvedValue( style(), 'Mobile' ) ).toBe( '100%' );
	} );

	it( 'MOBILE FALLS BACK TO BASE, NOT TABLET', () => {
		// The single most important rule here. Core's bands are mutually
		// exclusive ranges, so an unset mobile value must inherit Desktop.
		// Falling back to Tablet would be a tablet-into-mobile cascade and
		// would disagree with every core control on the same block.
		const partial = {
			blockkit: { width: '200px' },
			'@tablet': { blockkit: { width: '150px' } },
		};

		expect( getResolvedValue( partial, 'Mobile' ) ).toBe( '200px' );
	} );

	it( 'an unknown device is treated as the base layer', () => {
		expect( getResolvedValue( style(), 'Watch' ) ).toBe( '200px' );
	} );
} );

describe( 'setStateValue', () => {
	it( 'writes to the base layer when no state is given', () => {
		const next = setStateValue( undefined, null, '10px' );

		expect( next.blockkit.width ).toBe( '10px' );
	} );

	it( 'writes into the requested state layer only', () => {
		const next = setStateValue( style(), '@mobile', '50%' );

		expect( next[ '@mobile' ].blockkit.width ).toBe( '50%' );
		expect( next.blockkit.width ).toBe( '200px' );
		expect( next[ '@tablet' ].blockkit.width ).toBe( '150px' );
	} );

	it( 'does not mutate the input', () => {
		const original = style();
		const snapshot = JSON.stringify( original );

		setStateValue( original, '@mobile', '50%' );

		expect( JSON.stringify( original ) ).toBe( snapshot );
	} );

	it( 'prunes empty layers instead of leaving debris in post content', () => {
		// undefined clears a value. Once the last value in a layer is gone the
		// layer itself must go, or `style` accumulates `{"@mobile":{}}` forever
		// and every save writes a slightly larger post.
		const next = setStateValue(
			{ '@mobile': { blockkit: { width: '50px' } } },
			'@mobile',
			undefined
		);

		expect( next ).toBeUndefined();
	} );

	it( 'keeps sibling properties when clearing one', () => {
		const next = setStateValue(
			{ blockkit: { width: '10px', iconSize: '1em' } },
			null,
			undefined
		);

		expect( next.blockkit.iconSize ).toBe( '1em' );
		expect( next.blockkit.width ).toBeUndefined();
	} );

	it( 'writes a second property alongside the first', () => {
		const next = setStateValue( style(), null, '2em', ICON_SIZE_KEY );

		expect( next.blockkit.iconSize ).toBe( '2em' );
		expect( next.blockkit.width ).toBe( '200px' );
	} );
} );
