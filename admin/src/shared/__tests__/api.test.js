import apiFetch from '@wordpress/api-fetch';

import { createSettingsApi, normalizeRestError } from '../api';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: Object.assign( jest.fn(), {
		createNonceMiddleware: jest.fn(),
		use: jest.fn(),
	} ),
} ), { virtual: true } );

describe( 'settings API client', () => {
	it( 'installs the nonce middleware and uses GET/PUT on the settings route', async () => {
		const middleware = jest.fn();
		const fetch = jest.fn().mockResolvedValue( { schema_version: 2 } );
		fetch.use = jest.fn();
		apiFetch.createNonceMiddleware.mockReturnValue( middleware );
		const client = createSettingsApi( {
			restUrl: 'https://example.test/wp-json/darven-precos-parcelados/v1/',
			nonce: 'rest-nonce',
		}, fetch );
		const document = { schema_version: 2, general: {}, display: {}, positions: {}, compatibility: {} };

		await client.loadSettings();
		await client.saveSettings( document );

		expect( apiFetch.createNonceMiddleware ).toHaveBeenCalledWith( 'rest-nonce' );
		expect( fetch.use ).toHaveBeenCalledWith( middleware );
		expect( fetch ).toHaveBeenNthCalledWith( 1, { url: 'https://example.test/wp-json/darven-precos-parcelados/v1/settings' } );
		expect( fetch ).toHaveBeenNthCalledWith( 2, {
			url: 'https://example.test/wp-json/darven-precos-parcelados/v1/settings',
			method: 'PUT',
			data: document,
		} );
	} );

	it( 'normalizes missing REST messages to a translated fallback', () => {
		expect( normalizeRestError( { code: 'rest_error' }, 'Fallback message' ) ).toBe( 'Fallback message' );
	} );
} );
