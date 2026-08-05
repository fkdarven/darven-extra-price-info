import { createElement } from '@wordpress/element';
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
	sprintf: ( text, ...values ) =>
		text.replace( /%(\d+)\$s/g, ( match, index ) => values[ index - 1 ] ),
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

const toggle = ( element, checked ) =>
	act( () => {
		if ( element.checked !== checked ) {
			element.click();
		}
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

	it( 'renders task-oriented settings tabs', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );

		await flushPromises();

		expect(
			Array.from( container.querySelectorAll( '[role="tab"]' ) ).map(
				( tab ) => tab.textContent
			)
		).toEqual( [ 'Pricing', 'Presentation', 'Advanced' ] );
	} );

	it( 'shows popup text only for popup display modes', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		expect(
			container.querySelector( '[name="darven_epi_popup_text"]' )
		).toBeNull();

		change(
			container.querySelector( '[name="darven_epi_mode_of_view"]' ),
			'popup'
		);

		expect(
			container.querySelector( '[name="darven_epi_popup_text"]' ).value
		).toBe( 'Ver parcelas' );
	} );

	it( 'shows the custom interest table only after customized fees are enabled', async () => {
		const documentWithoutCustomFees = {
			...settingsDocument,
			general: {
				...settingsDocument.general,
				darven_epi_installments_interest_fee_is_table_enabled: '',
			},
		};
		renderApp( {
			loadSettings: jest
				.fn()
				.mockResolvedValue( documentWithoutCustomFees ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		expect(
			container.querySelector(
				'[name="darven_epi_installments_interest_fee_table"]'
			)
		).toBeNull();

		toggle(
			container.querySelector(
				'[name="darven_epi_installments_interest_fee_is_table_enabled"]'
			),
			true
		);

		expect(
			container.querySelector(
				'[name="darven_epi_installments_interest_fee_table"]'
			).value
		).toBe( '1|2|3' );
	} );

	it( 'preserves hidden installment values when installments are disabled and saved', async () => {
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn().mockResolvedValue( settingsDocument ),
		};
		renderApp( apiClient );
		await flushPromises();

		toggle(
			container.querySelector(
				'[name="darven_epi_installments_is_enabled"]'
			),
			false
		);

		expect(
			container.querySelector( '[name="darven_epi_max_installments"]' )
		).toBeNull();
		click( findButton( 'Save settings' ) );
		await flushPromises();

		expect( apiClient.saveSettings.mock.calls[ 0 ][ 0 ].general ).toEqual( {
			...settingsDocument.general,
			darven_epi_installments_is_enabled: '',
		} );
	} );

	it( 'uses a keyboard-reachable native disclosure for advanced interest rules', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		const details = container.querySelector( 'details' );
		const summary = details && details.querySelector( 'summary' );
		expect( summary.textContent ).toBe( 'Custom rates configured' );
		expect(
			details.querySelector(
				'[name="darven_epi_installments_interest_fee_from"]'
			)
		).not.toBeNull();
		summary.focus();
		expect( document.activeElement ).toBe( summary );
	} );

	it( 'reorders a visual placement card and saves its legacy value', async () => {
		const apiClient = {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn().mockResolvedValue( settingsDocument ),
		};
		renderApp( apiClient );
		await flushPromises();

		click( findButton( 'Presentation' ) );

		const singleProduct = container.querySelector(
			'[data-position-field="darven_epi_single_product_position"]'
		);
		expect(
			Array.from(
				singleProduct.querySelectorAll( '[data-statement]' )
			).map( ( row ) => row.dataset.statement )
		).toEqual( [ 'original', 'installments', 'cash' ] );

		click(
			Array.from( singleProduct.querySelectorAll( 'button' ) ).find(
				( button ) =>
					'Move cash price up in Single product' ===
					button.getAttribute( 'aria-label' )
			)
		);

		expect(
			Array.from(
				singleProduct.querySelectorAll( '[data-statement]' )
			).map( ( row ) => row.dataset.statement )
		).toEqual( [ 'original', 'cash', 'installments' ] );

		click( findButton( 'Save settings' ) );
		await flushPromises();

		expect(
			apiClient.saveSettings.mock.calls[ 0 ][ 0 ].positions
				.darven_epi_single_product_position
		).toBe( 'first' );
	} );

	it( 'groups appearance controls by statement after visual placement', async () => {
		renderApp( {
			loadSettings: jest.fn().mockResolvedValue( settingsDocument ),
			saveSettings: jest.fn(),
		} );
		await flushPromises();

		click( findButton( 'Presentation' ) );

		const groups = Array.from(
			container.querySelectorAll(
				'.darven-precos-parcelados-admin__group'
			)
		);
		expect(
			groups.map(
				( group ) => group.querySelector( 'legend' ).textContent
			)
		).toEqual( [ 'Statement positions', 'Appearance' ] );

		const appearance = groups[ 1 ];
		const subgroups = Array.from(
			appearance.querySelectorAll(
				'.darven-precos-parcelados-admin__appearance-subgroup'
			)
		);
		expect(
			subgroups.map(
				( subgroup ) => subgroup.querySelector( 'h3' ).textContent
			)
		).toEqual( [ 'Cash price', 'Installment price' ] );

		[
			[
				'darven_epi_color_of_incash_price',
				'darven_epi_font_size_of_incash_price',
			],
			[
				'darven_epi_color_of_incash_prefix',
				'darven_epi_font_size_of_incash_prefix',
			],
			[
				'darven_epi_color_of_incash_suffix',
				'darven_epi_font_size_of_incash_suffix',
			],
			[
				'darven_epi_color_of_installments_price',
				'darven_epi_font_size_of_installments_price',
			],
			[
				'darven_epi_color_of_installments_install',
				'darven_epi_font_size_of_installments_install',
			],
			[
				'darven_epi_color_of_installments_prefix',
				'darven_epi_font_size_of_installments_prefix',
			],
			[
				'darven_epi_color_of_installments_suffix',
				'darven_epi_font_size_of_installments_suffix',
			],
		].forEach( ( [ colorName, fontSizeName ] ) => {
			const row = appearance.querySelector(
				`[data-appearance-row="${ colorName }"]`
			);
			expect(
				row.querySelector( `[name="${ colorName }"]` )
			).not.toBeNull();
			expect(
				row.querySelector( `[name="${ fontSizeName }"]` )
			).not.toBeNull();
		} );

		Object.keys( settingsDocument.display ).forEach( ( name ) => {
			expect(
				appearance.querySelectorAll( `[name="${ name }"]` )
			).toHaveLength( 1 );
		} );
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
		click( findButton( 'Presentation' ) );
		change(
			container.querySelector(
				'[name="darven_epi_font_size_of_incash_price"]'
			),
			'1.8'
		);
		click(
			Array.from(
				container
					.querySelector(
						'[data-position-field="darven_epi_single_product_position"]'
					)
					.querySelectorAll( 'button' )
			).find(
				( button ) =>
					'Move cash price up in Single product' ===
					button.getAttribute( 'aria-label' )
			)
		);
		click( findButton( 'Advanced' ) );
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
				darven_epi_single_product_position: 'first',
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

		const pricing = findButton( 'Pricing' );
		const presentation = findButton( 'Presentation' );
		const advanced = findButton( 'Advanced' );
		expect( pricing.tabIndex ).toBe( 0 );
		expect( presentation.tabIndex ).toBe( -1 );

		pricing.focus();
		keyDown( pricing, 'ArrowRight' );
		expect( presentation.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( presentation.tabIndex ).toBe( 0 );
		expect( document.activeElement ).toBe( presentation );

		keyDown( presentation, 'End' );
		expect( advanced.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe( advanced );

		keyDown( advanced, 'Home' );
		expect( pricing.getAttribute( 'aria-selected' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe( pricing );
	} );
} );
