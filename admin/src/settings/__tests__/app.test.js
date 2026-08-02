import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';

import SettingsApp from '../app';

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
	__: ( text ) =>
		'Darven Preços Parcelados' === text
			? 'Translated brand must not render'
			: text,
} ) );

const settingsDocument = {
	schema_version: 2,
	general: {
		darven_epi_incash_is_enabled: 'darven_epi_incash_is_enabled',
		darven_epi_installments_is_enabled:
			'darven_epi_installments_is_enabled',
		darven_epi_installments_interest_fee_is_table_enabled:
			'darven_epi_installments_interest_fee_is_table_enabled',
		darven_epi_type_of_discount: 'percent',
		darven_epi_mode_of_view: 'default',
		darven_epi_minimum_installments_value: '20',
		darven_epi_installments_interest_fee: '2',
		darven_epi_installments_interest_fee_first_install: '1',
		darven_epi_minimum_incash_value: '10',
		darven_epi_value_of_incash_discount: '5',
		darven_epi_max_installments: '12',
		darven_epi_installments_interest_fee_from: '3',
		darven_epi_installments_prefix: 'em',
		darven_epi_installments_suffix: 'sem juros',
		darven_epi_incash_suffix: 'no PIX',
		darven_epi_incash_prefix: 'por',
		darven_epi_popup_text: 'Ver parcelas',
		darven_epi_installments_interest_fee_table: '1|2|3',
	},
	display: {
		darven_epi_color_of_installments_install: '#111111',
		darven_epi_color_of_installments_prefix: '#222222',
		darven_epi_color_of_installments_suffix: '#333333',
		darven_epi_color_of_installments_price: '#444444',
		darven_epi_color_of_incash_prefix: '#555555',
		darven_epi_color_of_incash_suffix: '#666666',
		darven_epi_color_of_incash_price: '#777777',
		darven_epi_font_size_of_incash_price: '1.0',
		darven_epi_font_size_of_incash_suffix: '1.1',
		darven_epi_font_size_of_incash_prefix: '1.2',
		darven_epi_font_size_of_installments_price: '1.3',
		darven_epi_font_size_of_installments_suffix: '1.4',
		darven_epi_font_size_of_installments_prefix: '1.5',
		darven_epi_font_size_of_installments_install: '1.6',
	},
	positions: {
		darven_epi_others_product_position: 'first',
		darven_epi_single_product_position: 'second',
		darven_epi_catalog_product_position: 'third',
	},
	compatibility: {
		darven_epi_yith_dynamic_pricing_mode: 'auto',
	},
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

const keyDown = ( element, key ) =>
	act( () =>
		element.dispatchEvent(
			new window.KeyboardEvent( 'keydown', { bubbles: true, key } )
		)
	);

const change = ( element, value ) =>
	act( () => {
		const setter = Object.getOwnPropertyDescriptor(
			Object.getPrototypeOf( element ),
			'value'
		).set;
		setter.call( element, value );
		element.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

describe( 'SettingsApp', () => {
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
		act( () => root.render( <SettingsApp apiClient={ apiClient } /> ) );
	};

	const findButton = ( label ) =>
		Array.from( container.querySelectorAll( 'button' ) ).find(
			( button ) => label === button.textContent
		);

	it( 'shows loading until the settings document arrives', async () => {
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

		renderApp( apiClient );
		expect(
			container.querySelector( '[role="progressbar"]' )
		).not.toBeNull();

		resolveRequest( settingsDocument );
		await flushPromises();

		expect(
			container.querySelector( '[name="darven_epi_max_installments"]' )
				.value
		).toBe( '12' );
	} );

	it( 'shows a normalized error when loading fails', async () => {
		renderApp( {
			loadSettings: jest
				.fn()
				.mockRejectedValue( { message: 'REST indisponível' } ),
			saveSettings: jest.fn(),
		} );

		await flushPromises();

		expect(
			container.querySelector( '[role="alert"]' ).textContent
		).toContain( 'REST indisponível' );
	} );

	it( 'renders the canonical brand name as a non-translatable heading', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );

		await flushPromises();

		expect( container.querySelector( 'h1' ).textContent ).toBe(
			'Darven Preços Parcelados'
		);
	} );

	it( 'edits one field in every tab and PUTs the entire document', async () => {
		const savedDocument = JSON.parse( JSON.stringify( settingsDocument ) );
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn( ( document ) => {
				Object.assign( savedDocument, document );
				return Promise.resolve( document );
			} ),
		};
		renderApp( apiClient );
		await flushPromises();

		change(
			container.querySelector( '[name="darven_epi_max_installments"]' ),
			'10'
		);
		click(
			Array.from( container.querySelectorAll( 'button' ) ).find(
				( button ) => 'Display' === button.textContent
			)
		);
		change(
			container.querySelector(
				'[name="darven_epi_font_size_of_incash_price"]'
			),
			'1.8'
		);
		click(
			Array.from( container.querySelectorAll( 'button' ) ).find(
				( button ) => 'Positions' === button.textContent
			)
		);
		change(
			container.querySelector(
				'[name="darven_epi_single_product_position"]'
			),
			'sixth'
		);
		click(
			Array.from( container.querySelectorAll( 'button' ) ).find(
				( button ) => 'Compatibility' === button.textContent
			)
		);
		change(
			container.querySelector(
				'[name="darven_epi_yith_dynamic_pricing_mode"]'
			),
			'disabled'
		);
		click(
			Array.from( container.querySelectorAll( 'button' ) ).find(
				( button ) => 'Save settings' === button.textContent
			)
		);
		await flushPromises();

		expect( apiClient.saveSettings ).toHaveBeenCalledWith( {
			...settingsDocument,
			general: {
				...settingsDocument.general,
				darven_epi_max_installments: '10',
			},
			display: {
				...settingsDocument.display,
				darven_epi_font_size_of_incash_price: '1.8',
			},
			positions: {
				...settingsDocument.positions,
				darven_epi_single_product_position: 'sixth',
			},
			compatibility: { darven_epi_yith_dynamic_pricing_mode: 'disabled' },
		} );
		expect(
			Array.from( container.querySelectorAll( '[role="status"]' ) ).some(
				( notice ) => notice.textContent.includes( 'Settings saved.' )
			)
		).toBe( true );
	} );

	it( 'preserves edited values when the PUT fails', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest
				.fn()
				.mockRejectedValue( { message: 'Falha ao salvar' } ),
		} );
		await flushPromises();

		change(
			container.querySelector( '[name="darven_epi_max_installments"]' ),
			'9'
		);
		click(
			Array.from( container.querySelectorAll( 'button' ) ).find(
				( button ) => 'Save settings' === button.textContent
			)
		);
		await flushPromises();

		expect(
			container.querySelector( '[name="darven_epi_max_installments"]' )
				.value
		).toBe( '9' );
		expect(
			container.querySelector( '[role="alert"]' ).textContent
		).toContain( 'Falha ao salvar' );
	} );

	it( 'disables editable controls while a settings save is pending', async () => {
		let resolveSave;
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(
				() =>
					new Promise( ( resolve ) => {
						resolveSave = resolve;
					} )
			),
		} );
		await flushPromises();

		click( findButton( 'Save settings' ) );

		const field = container.querySelector(
			'[name="darven_epi_max_installments"]'
		);
		const busyRegion = container.querySelector( 'fieldset[aria-busy]' );
		expect( field.disabled ).toBe( true );
		expect( findButton( 'Saving…' ).disabled ).toBe( true );
		expect( busyRegion.getAttribute( 'aria-busy' ) ).toBe( 'true' );

		resolveSave( settingsDocument );
		await flushPromises();
	} );

	it( 'moves roving tab focus with arrow, Home and End keys', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		const general = findButton( 'General' );
		const display = findButton( 'Display' );
		const compatibility = findButton( 'Compatibility' );
		expect( general.tabIndex ).toBe( 0 );
		expect( display.tabIndex ).toBe( -1 );

		general.focus();
		keyDown( general, 'ArrowRight' );
		expect( display.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( display.tabIndex ).toBe( 0 );
		expect( document.activeElement ).toBe( display );

		keyDown( display, 'End' );
		expect( compatibility.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe( compatibility );

		keyDown( compatibility, 'Home' );
		expect( general.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe( general );
	} );
} );
