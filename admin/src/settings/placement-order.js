export const POSITION_ORDERS = {
	first: [ 'original', 'cash', 'installments' ],
	second: [ 'original', 'installments', 'cash' ],
	third: [ 'cash', 'original', 'installments' ],
	fourth: [ 'cash', 'installments', 'original' ],
	fifth: [ 'installments', 'original', 'cash' ],
	sixth: [ 'installments', 'cash', 'original' ],
};

export const getPositionOrder = ( value ) => [
	...( POSITION_ORDERS[ value ] || POSITION_ORDERS.first ),
];

export const getPositionValue = ( order ) =>
	Object.keys( POSITION_ORDERS ).find(
		( value ) => POSITION_ORDERS[ value ].join( '|' ) === order.join( '|' )
	) || 'first';

export const movePositionStatement = ( value, statement, offset ) => {
	const order = getPositionOrder( value );
	const currentIndex = order.indexOf( statement );
	const targetIndex = currentIndex + offset;

	if (
		-1 === currentIndex ||
		targetIndex < 0 ||
		targetIndex >= order.length
	) {
		return value;
	}

	[ order[ currentIndex ], order[ targetIndex ] ] = [
		order[ targetIndex ],
		order[ currentIndex ],
	];

	return getPositionValue( order );
};
