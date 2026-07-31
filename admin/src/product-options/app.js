/* @jsx createElement */
import apiFetch from '@wordpress/api-fetch';
import { Component, createElement } from '@wordpress/element';
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

class ProductOptionsApp extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			settings: null,
			loadError: '',
			notice: null,
			isSaving: false,
		};
		this.isActive = false;
	}

	componentDidMount() {
		this.isActive = true;
		if ( this.props.productId <= 0 || ! this.props.apiClient ) {
			return;
		}

		this.props.apiClient
			.loadSettings()
			.then( ( settings ) => {
				if ( this.isActive ) {
					this.setState( { settings } );
				}
			} )
			.catch( ( error ) => {
				if ( this.isActive ) {
					this.setState( {
						loadError: normalizeRestError(
							error,
							__(
								'The product options could not be loaded.',
								'darven-multiplos-precos-informativos'
							)
						),
					} );
				}
			} );
	}

	componentWillUnmount() {
		this.isActive = false;
	}

	persistSettings = async ( changed ) => {
		if ( this.state.isSaving ) {
			return;
		}

		this.setState( {
			settings: changed,
			isSaving: true,
			notice: null,
		} );

		try {
			const settings = await this.props.apiClient.saveSettings( changed );
			if ( this.isActive ) {
				this.setState( {
					settings,
					notice: {
						status: 'success',
						message: __( 'Product options saved.', 'darven-multiplos-precos-informativos' ),
					},
				} );
			}
		} catch ( error ) {
			if ( this.isActive ) {
				this.setState( {
					notice: {
						status: 'error',
						message: normalizeRestError(
							error,
							__(
								'The product options could not be saved.',
								'darven-multiplos-precos-informativos'
							)
						),
						canRetry: true,
						pendingSettings: changed,
					},
				} );
			}
		} finally {
			if ( this.isActive ) {
				this.setState( { isSaving: false } );
			}
		}
	};

	saveFlag = ( name, value ) => {
		this.persistSettings( { ...this.state.settings, [ name ]: value } );
	};

	retrySave = () => {
		if ( this.state.notice && this.state.notice.pendingSettings ) {
			this.persistSettings( this.state.notice.pendingSettings );
		}
	};

	renderNotice( notice ) {
		return (
			<div
				className={ `darven-precos-parcelados-product-options__notice darven-precos-parcelados-product-options__notice--${ notice.status }` }
				role={ 'error' === notice.status ? 'alert' : 'status' }
			>
				<p>{ notice.message }</p>
				{ notice.canRetry && (
					<div>
						<p>
							{ __(
								'The selected change has not been saved yet.',
								'darven-multiplos-precos-informativos'
							) }
						</p>
						<button
							className="button"
							type="button"
							disabled={ this.state.isSaving }
							onClick={ this.retrySave }
						>
							{ __( 'Retry save', 'darven-multiplos-precos-informativos' ) }
						</button>
					</div>
				) }
			</div>
		);
	}

	renderToggle( name, label, help ) {
		const helpId = `${ name }-description`;

		return (
			<div className="darven-precos-parcelados-product-options__field">
				<label htmlFor={ name }>
					<input
						id={ name }
						type="checkbox"
						checked={ Boolean( this.state.settings[ name ] ) }
						disabled={ this.state.isSaving }
						aria-describedby={ helpId }
						onChange={ ( event ) =>
							this.saveFlag( name, event.target.checked )
						}
					/>
					<span>{ label }</span>
				</label>
				<p id={ helpId } className="description">
					{ help }
				</p>
			</div>
		);
	}

	render() {
		const { settings, loadError, notice, isSaving } = this.state;

		if ( this.props.productId <= 0 ) {
			return (
				<div className="darven-precos-parcelados-product-options">
					{ this.renderNotice( {
						status: 'info',
						message: __(
							'Save the product before editing Darven options.',
							'darven-multiplos-precos-informativos'
						),
					} ) }
				</div>
			);
		}

		if ( null === settings && ! loadError ) {
			return (
				<div className="darven-precos-parcelados-product-options darven-precos-parcelados-product-options--loading">
					<span
						className="darven-precos-parcelados-product-options__spinner"
						role="progressbar"
						aria-label={ __(
							'Loading product options…',
							'darven-multiplos-precos-informativos'
						) }
					/>
				</div>
			);
		}

		if ( loadError ) {
			return (
				<div className="darven-precos-parcelados-product-options">
					{ this.renderNotice( {
						status: 'error',
						message: loadError,
					} ) }
				</div>
			);
		}

		return (
			<div className="darven-precos-parcelados-product-options">
				{ notice && this.renderNotice( notice ) }
				<fieldset disabled={ isSaving }>
					<legend>
						{ __( 'Installment prices', 'darven-multiplos-precos-informativos' ) }
					</legend>
					{ this.renderToggle(
						'disable_incash',
						__(
							'Disable cash price for this product',
							'darven-multiplos-precos-informativos'
						),
						__(
							'Hides the cash price only for the current product.',
							'darven-multiplos-precos-informativos'
						)
					) }
					{ this.renderToggle(
						'disable_installments',
						__(
							'Disable installment price for this product',
							'darven-multiplos-precos-informativos'
						),
						__(
							'Hides the installment price only for the current product.',
							'darven-multiplos-precos-informativos'
						)
					) }
				</fieldset>
				{ isSaving && (
					<div
						className="darven-precos-parcelados-product-options__saving"
						aria-live="polite"
					>
						<span className="darven-precos-parcelados-product-options__spinner" />
						<span>{ __( 'Saving…', 'darven-multiplos-precos-informativos' ) }</span>
					</div>
				) }
			</div>
		);
	}
}

export default ProductOptionsApp;
