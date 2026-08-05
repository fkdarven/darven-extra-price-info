import {
	POSITION_ORDERS,
	getPositionOrder,
	getPositionValue,
	movePositionStatement,
} from '../placement-order';

const expectedOrders = {
	first: [ 'original', 'cash', 'installments' ],
	second: [ 'original', 'installments', 'cash' ],
	third: [ 'cash', 'original', 'installments' ],
	fourth: [ 'cash', 'installments', 'original' ],
	fifth: [ 'installments', 'original', 'cash' ],
	sixth: [ 'installments', 'cash', 'original' ],
};

it( 'round-trips every persisted position value', () => {
	expect( POSITION_ORDERS ).toEqual( expectedOrders );
	Object.entries( expectedOrders ).forEach( ( [ value, order ] ) => {
		expect( getPositionOrder( value ) ).toEqual( order );
		expect( getPositionValue( order ) ).toBe( value );
	} );
} );

it( 'falls back safely and ignores boundary moves', () => {
	expect( getPositionOrder( 'unknown' ) ).toEqual( expectedOrders.first );
	expect( movePositionStatement( 'first', 'original', -1 ) ).toBe( 'first' );
	expect( movePositionStatement( 'first', 'installments', 1 ) ).toBe(
		'first'
	);
} );

it( 'maps a valid movement to the matching persisted value', () => {
	expect( movePositionStatement( 'second', 'cash', -1 ) ).toBe( 'first' );
	expect( movePositionStatement( 'first', 'cash', -1 ) ).toBe( 'third' );
} );
