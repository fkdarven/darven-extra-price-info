import { createRoot } from 'react-dom/client';
import { act } from 'react-dom/test-utils';

import SettingsApp from '../app';

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
		const control = ( tag, type ) =>
			function Control( {
				label,
				value,
				checked,
				onChange,
				options = [],
				children,
				...props
			} ) {
				if ( 'select' === tag ) {
					return element.createElement(
						'label',
						null,
						label,
						element.createElement(
							'select',
							{
								...props,
								value: value || '',
								onChange: ( event ) =>
									onChange( event.target.value ),
							},
							options.map( ( option ) =>
								element.createElement(
									'option',
									{ key: option.value, value: option.value },
									option.label
								)
							)
						)
					);
				}

				return element.createElement(
					'label',
					null,
					label,
					element.createElement( tag, {
						...props,
						type,
						value: 'checkbox' === type ? undefined : value || '',
						checked:
							'checkbox' === type
								? Boolean( checked )
								: undefined,
						onChange: ( event ) =>
							onChange(
								'checkbox' === type
									? event.target.checked
									: event.target.value
							),
					} ),
					children
				);
			};

		return {
			Button: ( { children, isBusy, variant, ...props } ) =>
				element.createElement( 'button', props, children ),
			Notice: ( { children, status = 'info' } ) =>
				element.createElement(
					'div',
					{ role: 'error' === status ? 'alert' : 'status' },
					children
				),
			Panel: ( { children } ) =>
				element.createElement( 'div', null, children ),
			PanelBody: ( { children, title } ) =>
				element.createElement(
					'section',
					null,
					element.createElement( 'h2', null, title ),
					children
				),
			SelectControl: control( 'select' ),
			Spinner: () =>
				element.createElement( 'div', { role: 'progressbar' } ),
			TabPanel: ( { tabs, children } ) => {
				const [ active, setActive ] = element.useState( tabs[ 0 ] );
				return element.createElement(
					'div',
					null,
					...tabs.map( ( tab ) =>
						element.createElement(
							'button',
							{
								key: tab.name,
								type: 'button',
								onClick: () => setActive( tab ),
							},
							tab.title
						)
					),
					children( active )
				);
			},
			TextareaControl: control( 'textarea' ),
			TextControl: control( 'input', 'text' ),
			ToggleControl: control( 'input', 'checkbox' ),
		};
	},
	{ virtual: true }
);

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
				( button ) => 'Visual' === button.textContent
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
} );
