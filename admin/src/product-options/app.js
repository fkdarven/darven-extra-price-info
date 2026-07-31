/* @jsx createElement */
import apiFetch from '@wordpress/api-fetch';
import { Notice, Spinner, ToggleControl } from '@wordpress/components';
import {
	createElement,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { normalizeRestError } from '../shared/api';

const trimTrailingSlash = ( value ) =>
	String( value || '' ).replace( /\/+$/, '' );

export const createProductOptionsApi = ( config, fetch = apiFetch ) => {
	if ( config.nonce ) {
		fetch.use( apiFetch.createNonceMiddleware( config.nonce ) );
	}

	const url = `${ trimTrailingSlash( config.restUrl ) }/products/${ Number(
		config.productId
	) }/settings`;

	return {
		loadSettings: () => fetch( { url } ),
		saveSettings: ( settings ) =>
			fetch( { url, method: 'PUT', data: settings } ),
	};
};

const useProductOptions = ( apiClient ) => {
	const [ settings, setSettings ] = useState( null );
	const [ loadError, setLoadError ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		let active = true;

		apiClient
			.loadSettings()
			.then( ( current ) => {
				if ( active ) {
					setSettings( current );
				}
			} )
			.catch( ( error ) => {
				if ( active ) {
					setLoadError(
						normalizeRestError(
							error,
							__(
								'The product options could not be loaded.',
								'darven-precos-parcelados'
							)
						)
					);
				}
			} );

		return () => {
			active = false;
		};
	}, [ apiClient ] );

	const saveFlag = useCallback(
		async ( name, value ) => {
			if ( isSaving ) {
				return;
			}

			const changed = { ...settings, [ name ]: value };
			setIsSaving( true );
			setNotice( null );

			try {
				const saved = await apiClient.saveSettings( changed );
				setSettings( saved );
				setNotice( {
					status: 'success',
					message: __(
						'Product options saved.',
						'darven-precos-parcelados'
					),
				} );
			} catch ( error ) {
				setNotice( {
					status: 'error',
					message: normalizeRestError(
						error,
						__(
							'The product options could not be saved.',
							'darven-precos-parcelados'
						)
					),
				} );
			} finally {
				setIsSaving( false );
			}
		},
		[ apiClient, isSaving, settings ]
	);

	return {
		settings,
		isLoading: null === settings && ! loadError,
		loadError,
		isSaving,
		notice,
		saveFlag,
	};
};

const ProductOptionsApp = ( { apiClient } ) => {
	const store = useProductOptions( apiClient );

	if ( store.isLoading ) {
		return (
			<div className="darven-precos-parcelados-product-options darven-precos-parcelados-product-options--loading">
				<Spinner />
			</div>
		);
	}

	if ( store.loadError ) {
		return (
			<div className="darven-precos-parcelados-product-options">
				<Notice status="error" isDismissible={ false }>
					{ store.loadError }
				</Notice>
			</div>
		);
	}

	return (
		<div className="darven-precos-parcelados-product-options">
			<h4>{ __( 'Installment prices', 'darven-precos-parcelados' ) }</h4>
			{ store.notice && (
				<Notice status={ store.notice.status } isDismissible={ false }>
					{ store.notice.message }
				</Notice>
			) }
			<fieldset disabled={ store.isSaving }>
				<ToggleControl
					label={ __(
						'Disable cash price for this product',
						'darven-precos-parcelados'
					) }
					help={ __(
						'Hides the cash price only for the current product.',
						'darven-precos-parcelados'
					) }
					checked={ Boolean( store.settings.disable_incash ) }
					disabled={ store.isSaving }
					onChange={ ( value ) =>
						store.saveFlag( 'disable_incash', value )
					}
				/>
				<ToggleControl
					label={ __(
						'Disable installment price for this product',
						'darven-precos-parcelados'
					) }
					help={ __(
						'Hides the installment price only for the current product.',
						'darven-precos-parcelados'
					) }
					checked={ Boolean( store.settings.disable_installments ) }
					disabled={ store.isSaving }
					onChange={ ( value ) =>
						store.saveFlag( 'disable_installments', value )
					}
				/>
			</fieldset>
			{ store.isSaving && (
				<div
					className="darven-precos-parcelados-product-options__saving"
					aria-live="polite"
				>
					<Spinner />
					<span>{ __( 'Saving…', 'darven-precos-parcelados' ) }</span>
				</div>
			) }
		</div>
	);
};

export default ProductOptionsApp;
