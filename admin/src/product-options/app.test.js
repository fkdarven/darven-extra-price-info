import apiFetch from '@wordpress/api-fetch';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';

import ProductOptionsApp, { createProductOptionsApi } from './app';

jest.mock(
	'@wordpress/element',
	() => {
		const react = require( 'react' );

		return {
			Component: react.Component,
			createElement: react.createElement,
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
	__: jest.fn( ( text ) => text ),
} ) );

const currentSettings = {
	schema_version: 1,
	disable_incash: false,
	disable_installments: true,
};

const flushPromises = () =>
	act( async () => {
		await Promise.resolve();
		await Promise.resolve();
	} );

const click = ( element ) =>
	act( () =>
		element.dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true } )
		)
	);

describe( 'product options API client', () => {
	it( 'uses the localized productId for GET and PUT requests', async () => {
		const middleware = jest.fn();
		const fetch = jest.fn().mockResolvedValue( currentSettings );
		fetch.use = jest.fn();
		apiFetch.createNonceMiddleware.mockReturnValue( middleware );
		const client = createProductOptionsApi(
			{
				restUrl:
					'https://example.test/wp-json/darven-precos-parcelados/v1/',
				nonce: 'rest-nonce',
				productId: 42,
			},
			fetch
		);
		const changedSettings = {
			...currentSettings,
			disable_incash: true,
		};

		await client.loadSettings();
		await client.saveSettings( changedSettings );

		expect( apiFetch.createNonceMiddleware ).toHaveBeenCalledWith(
			'rest-nonce'
		);
		expect( fetch.use ).toHaveBeenCalledWith( middleware );
		expect( fetch ).toHaveBeenNthCalledWith( 1, {
			url: 'https://example.test/wp-json/darven-precos-parcelados/v1/products/42/settings',
		} );
		expect( fetch ).toHaveBeenNthCalledWith( 2, {
			url: 'https://example.test/wp-json/darven-precos-parcelados/v1/products/42/settings',
			method: 'PUT',
			data: changedSettings,
		} );
	} );
} );

describe( 'ProductOptionsApp', () => {
	let container;
	let root;

	beforeEach( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		require( '@wordpress/i18n' ).__.mockClear();
		container = document.createElement( 'div' );
		document.body.appendChild( container );
	} );

	afterEach( () => {
		if ( root ) {
			act( () => root.unmount() );
		}
		container.remove();
		root = null;
	} );

	const renderApp = ( apiClient, productId = 42 ) => {
		root = createRoot( container );
		act( () =>
			root.render(
				<ProductOptionsApp
					apiClient={ apiClient }
					productId={ productId }
				/>
			)
		);
	};

	it( 'does not request product zero and asks to save a new product first', () => {
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( currentSettings ),
			saveSettings: jest.fn(),
		};

		renderApp( apiClient, 0 );

		expect( apiClient.loadSettings ).not.toHaveBeenCalled();
		expect( apiClient.saveSettings ).not.toHaveBeenCalled();
		expect(
			container.querySelector( '[role="status"]' ).textContent
		).toContain( 'Save the product before editing Darven options.' );
	} );

	it( 'uses the text domain declared by the current plugin header', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( currentSettings ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		const calls = require( '@wordpress/i18n' ).__.mock.calls;
		expect( calls.length ).toBeGreaterThan( 0 );
		expect( calls.every( ( call ) => 'darven-multiplos-precos-informativos' === call[ 1 ] ) ).toBe(
			true
		);
	} );

	it( 'loads the current flags only after mounting', async () => {
		let resolveRequest;
		const apiClient = {
			loadSettings: jest.fn(
				() =>
					new Promise( ( resolve ) => {
						resolveRequest = resolve;
					} )
			),
			saveSettings: jest.fn(),
		};

		expect( apiClient.loadSettings ).not.toHaveBeenCalled();
		renderApp( apiClient );
		expect( apiClient.loadSettings ).toHaveBeenCalledTimes( 1 );
		expect(
			container.querySelector( '[role="progressbar"]' )
		).not.toBeNull();

		resolveRequest( currentSettings );
		await flushPromises();

		const toggles = container.querySelectorAll( 'input[type="checkbox"]' );
		expect( toggles ).toHaveLength( 2 );
		expect( toggles[ 0 ].checked ).toBe( false );
		expect( toggles[ 1 ].checked ).toBe( true );
	} );

	it( 'saves each toggle change immediately and blocks both controls while saving', async () => {
		let resolveFirstSave;
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( currentSettings ),
			saveSettings: jest
				.fn()
				.mockImplementationOnce(
					() =>
						new Promise( ( resolve ) => {
							resolveFirstSave = resolve;
						} )
				)
				.mockResolvedValueOnce( {
					...currentSettings,
					disable_incash: true,
					disable_installments: false,
				} ),
		};
		renderApp( apiClient );
		await flushPromises();

		let toggles = container.querySelectorAll( 'input[type="checkbox"]' );
		click( toggles[ 0 ] );

		expect( apiClient.saveSettings ).toHaveBeenNthCalledWith( 1, {
			...currentSettings,
			disable_incash: true,
		} );
		toggles = container.querySelectorAll( 'input[type="checkbox"]' );
		expect( toggles[ 0 ].disabled ).toBe( true );
		expect( toggles[ 1 ].disabled ).toBe( true );

		resolveFirstSave( { ...currentSettings, disable_incash: true } );
		await flushPromises();
		toggles = container.querySelectorAll( 'input[type="checkbox"]' );
		click( toggles[ 1 ] );
		await flushPromises();

		expect( apiClient.saveSettings ).toHaveBeenNthCalledWith( 2, {
			...currentSettings,
			disable_incash: true,
			disable_installments: false,
		} );
		expect(
			container.querySelector( '[role="status"]' ).textContent
		).toContain( 'Product options saved.' );
	} );

	it( 'shows a 403 load error instead of controls', async () => {
		renderApp( {
			loadSettings: jest.fn().mockRejectedValue( {
				code: 'rest_forbidden',
				message: 'Forbidden',
			} ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		expect(
			container.querySelector( '[role="alert"]' ).textContent
		).toContain( 'Forbidden' );
		expect(
			container.querySelectorAll( 'input[type="checkbox"]' )
		).toHaveLength( 0 );
	} );

	it.each( [
		[ '403', { code: 'rest_forbidden', message: 'Forbidden' } ],
		[
			'500',
			{
				code: 'darven_epi_product_settings_save_failed',
				message: 'Could not save product settings.',
			},
		],
	] )(
		'keeps the attempted flag unsaved after a PUT %s error',
		async ( status, error ) => {
			renderApp( {
				loadSettings: jest.fn().mockResolvedValue( currentSettings ),
				saveSettings: jest.fn().mockRejectedValue( error ),
			} );
			await flushPromises();

			click(
				container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ]
			);
			await flushPromises();

			expect(
				container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ]
					.checked
			).toBe( true );
			expect(
				container.querySelector( '[role="alert"]' ).textContent
			).toContain( error.message );
			expect(
				Array.from( container.querySelectorAll( 'button' ) ).some(
					( button ) => 'Retry save' === button.textContent
				)
			).toBe( true );
			expect(
				Array.from(
					container.querySelectorAll( '[role="status"]' )
				).some( ( item ) => item.textContent.includes( 'saved' ) )
			).toBe( false );
		}
	);

	it( 'retries the exact unsaved payload and confirms it only after success', async () => {
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( currentSettings ),
			saveSettings: jest
				.fn()
				.mockRejectedValueOnce( { message: 'Temporary failure' } )
				.mockResolvedValueOnce( {
					...currentSettings,
					disable_incash: true,
				} ),
		};
		renderApp( {
			...apiClient,
		} );
		await flushPromises();

		click( container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ] );
		await flushPromises();
		const retryButton = Array.from(
			container.querySelectorAll( 'button' )
		).find( ( button ) => 'Retry save' === button.textContent );
		expect( retryButton ).toBeDefined();
		if ( ! retryButton ) {
			return;
		}
		click( retryButton );
		await flushPromises();

		expect( apiClient.saveSettings ).toHaveBeenNthCalledWith( 1, {
			...currentSettings,
			disable_incash: true,
		} );
		expect( apiClient.saveSettings ).toHaveBeenNthCalledWith( 2, {
			...currentSettings,
			disable_incash: true,
		} );
		expect(
			container.querySelector( '[role="status"]' ).textContent
		).toContain( 'Product options saved.' );
	} );
} );
