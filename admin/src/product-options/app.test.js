import apiFetch from '@wordpress/api-fetch';
import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';

import ProductOptionsApp, { createProductOptionsApi } from './app';

jest.mock(
	'@wordpress/element',
	() => ( {
		...require( 'react' ),
		render: jest.fn(),
	} ),
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

jest.mock(
	'@wordpress/components',
	() => {
		const element = require( '@wordpress/element' );

		return {
			Notice: ( { children, status = 'info' } ) =>
				element.createElement(
					'div',
					{ role: 'error' === status ? 'alert' : 'status' },
					children
				),
			Spinner: () =>
				element.createElement( 'div', { role: 'progressbar' } ),
			ToggleControl: ( { label, help, checked, disabled, onChange } ) =>
				element.createElement(
					'label',
					null,
					label,
					element.createElement( 'input', {
						type: 'checkbox',
						checked: Boolean( checked ),
						disabled,
						onChange: ( event ) => onChange( event.target.checked ),
					} ),
					element.createElement( 'span', null, help )
				),
		};
	},
	{ virtual: true }
);

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

	const renderApp = ( apiClient ) => {
		root = createRoot( container );
		act( () =>
			root.render( <ProductOptionsApp apiClient={ apiClient } /> )
		);
	};

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

	it( 'keeps the previous flag and shows a 500 error when saving fails', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( currentSettings ),
			saveSettings: jest.fn().mockRejectedValue( {
				code: 'darven_epi_product_settings_save_failed',
				message: 'Could not save product settings.',
			} ),
		} );
		await flushPromises();

		click( container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ] );
		await flushPromises();

		expect(
			container.querySelectorAll( 'input[type="checkbox"]' )[ 0 ].checked
		).toBe( false );
		expect(
			container.querySelector( '[role="alert"]' ).textContent
		).toContain( 'Could not save product settings.' );
		expect(
			Array.from( container.querySelectorAll( '[role="status"]' ) ).some(
				( notice ) => notice.textContent.includes( 'saved' )
			)
		).toBe( false );
	} );
} );
