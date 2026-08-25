jest.mock(
	'@wordpress/element',
	() => {
		const react = require( 'react' );

		return {
			Component: react.Component,
			createElement: react.createElement,
			createRoot: undefined,
			render: jest.fn(),
		};
	},
	{ virtual: true }
);

jest.mock(
	'@wordpress/api-fetch',
	() => ( {
		__esModule: true,
		default: Object.assign( jest.fn(), {
			createNonceMiddleware: jest.fn(),
			use: jest.fn(),
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

const loadEntrypoint = () => jest.isolateModules( () => require( './index' ) );

describe( 'product options entrypoint', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
		window.DarvenPrecosParceladosProductOptions = {
			restUrl:
				'https://example.test/wp-json/darven-precos-parcelados/v1/',
			nonce: 'rest-nonce',
			productId: 42,
		};
		require( '@wordpress/element' ).render.mockClear();
		require( '@wordpress/element' ).createRoot = undefined;
	} );

	afterEach( () => {
		delete window.DarvenPrecosParceladosProductOptions;
	} );

	it( 'does not mount a matching root outside the Darven panel', () => {
		document.body.innerHTML =
			'<div id="darven-precos-parcelados-product-options-root"></div>';

		loadEntrypoint();

		expect( require( '@wordpress/element' ).render ).not.toHaveBeenCalled();
	} );

	it( 'mounts only the root nested in the Darven panel', () => {
		document.body.innerHTML = [
			'<div id="darven-precos-parcelados-product-options-panel">',
			'<div id="darven-precos-parcelados-product-options-root"></div>',
			'</div>',
		].join( '' );

		loadEntrypoint();

		const render = require( '@wordpress/element' ).render;
		expect( render ).toHaveBeenCalledTimes( 1 );
		expect( render.mock.calls[ 0 ][ 1 ].id ).toBe(
			'darven-precos-parcelados-product-options-root'
		);
		expect( render.mock.calls[ 0 ][ 0 ].props.productId ).toBe( 42 );
	} );

	it( 'uses the React 18 root API when WordPress provides it', () => {
		document.body.innerHTML = [
			'<div id="darven-precos-parcelados-product-options-panel">',
			'<div id="darven-precos-parcelados-product-options-root"></div>',
			'</div>',
		].join( '' );
		const modernRender = jest.fn();
		const element = require( '@wordpress/element' );
		element.createRoot = jest.fn( () => ( { render: modernRender } ) );

		loadEntrypoint();

		expect( element.createRoot ).toHaveBeenCalledTimes( 1 );
		expect( modernRender ).toHaveBeenCalledTimes( 1 );
		expect( element.render ).not.toHaveBeenCalled();
	} );

	it( 'mounts a new-product message without constructing a REST client for zero', () => {
		window.DarvenPrecosParceladosProductOptions.productId = 0;
		document.body.innerHTML = [
			'<div id="darven-precos-parcelados-product-options-panel">',
			'<div id="darven-precos-parcelados-product-options-root"></div>',
			'</div>',
		].join( '' );

		loadEntrypoint();

		const renderedApp =
			require( '@wordpress/element' ).render.mock.calls[ 0 ][ 0 ];
		expect( renderedApp.props.productId ).toBe( 0 );
		expect( renderedApp.props.apiClient ).toBeNull();
	} );
} );
