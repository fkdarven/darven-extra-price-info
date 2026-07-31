import apiFetch from '@wordpress/api-fetch';

const trimTrailingSlash = ( value ) => String( value || '' ).replace( /\/+$/, '' );

export const normalizeRestError = ( error, fallback ) => {
	if ( error && 'string' === typeof error.message && error.message.trim() ) {
		return error.message.trim();
	}

	return fallback;
};

export const createSettingsApi = ( config, fetch = apiFetch ) => {
	if ( config.nonce ) {
		fetch.use( apiFetch.createNonceMiddleware( config.nonce ) );
	}

	const url = `${ trimTrailingSlash( config.restUrl ) }/settings`;

	return {
		loadSettings: () => fetch( { url } ),
		saveSettings: ( document ) => fetch( { url, method: 'PUT', data: document } ),
	};
};
