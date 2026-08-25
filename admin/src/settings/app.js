import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	createSettingsState,
	updateSettingsField,
} from '../shared/settings-store';
import { normalizeRestError } from '../shared/api';
import PricingSection from './sections/pricing-section';
import PresentationSection from './sections/presentation-section';
import AdvancedSection from './sections/advanced-section';

const tabs = [
	{ name: 'pricing', title: __( 'Pricing', 'darven-multiplos-precos-informativos' ) },
	{ name: 'presentation', title: __( 'Presentation', 'darven-multiplos-precos-informativos' ) },
	{ name: 'advanced', title: __( 'Advanced', 'darven-multiplos-precos-informativos' ) },
];

const hasVisiblePrice = ( settings, field ) => settings[ field ] === field;

export const getAvailableTabs = ( document ) => {
	const general = document?.general || {};
	const hasPrice =
		hasVisiblePrice( general, 'darven_epi_incash_is_enabled' ) ||
		hasVisiblePrice( general, 'darven_epi_installments_is_enabled' );

	return hasPrice
		? tabs
		: tabs.filter( ( tab ) => 'presentation' !== tab.name );
};

class SettingsApp extends Component {
	constructor( props ) {
		super( props );
		this.state = createSettingsState();
		this.isActive = false;
	}

	componentDidMount() {
		this.isActive = true;
		this.props.apiClient
			.loadSettings()
			.then( ( document ) => {
				if ( this.isActive ) {
					this.setState( { document } );
				}
			} )
			.catch( ( error ) => {
				if ( this.isActive ) {
					this.setState( {
						loadError: normalizeRestError(
							error,
							__(
								'The settings could not be loaded.',
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

	setActiveTab = ( activeTab ) => {
		this.setState( ( current ) => {
			const availableTabs = getAvailableTabs( current.document );
			const isAvailable = availableTabs.some(
				( tab ) => tab.name === activeTab
			);

			return {
				activeTab: isAvailable ? activeTab : availableTabs[ 0 ].name,
			};
		} );
	};

	handleTabKeyDown = ( event, currentIndex ) => {
		const availableTabs = getAvailableTabs( this.state.document );
		let nextIndex;

		switch ( event.key ) {
			case 'ArrowLeft':
				nextIndex =
					( currentIndex - 1 + availableTabs.length ) %
					availableTabs.length;
				break;
			case 'ArrowRight':
				nextIndex = ( currentIndex + 1 ) % availableTabs.length;
				break;
			case 'Home':
				nextIndex = 0;
				break;
			case 'End':
				nextIndex = availableTabs.length - 1;
				break;
			default:
				return;
		}

		event.preventDefault();
		const nextTab = availableTabs[ nextIndex ];
		this.setState( { activeTab: nextTab.name }, () => {
			const target = document.getElementById(
				`darven-precos-parcelados-tab-${ nextTab.name }`
			);
			if ( target ) {
				target.focus();
			}
		} );
	};

	updateField = ( section, field, value ) => {
		this.setState( ( current ) => {
			if ( current.isSaving ) {
				return null;
			}

			return {
				document: updateSettingsField(
					current.document,
					section,
					field,
					value
				),
				notice: null,
			};
		} );
	};

	save = async () => {
		if ( this.state.isSaving ) {
			return;
		}

		this.setState( { isSaving: true, notice: null } );

		try {
			const document = await this.props.apiClient.saveSettings(
				this.state.document
			);
			if ( this.isActive ) {
				this.setState( {
					document,
					notice: {
						status: 'success',
						message: __( 'Settings saved.', 'darven-multiplos-precos-informativos' ),
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
								'The settings could not be saved.',
								'darven-multiplos-precos-informativos'
							)
						),
					},
				} );
			}
		} finally {
			if ( this.isActive ) {
				this.setState( { isSaving: false } );
			}
		}
	};

	renderNotice( notice ) {
		return (
			<div
				className={ `darven-precos-parcelados-admin__notice darven-precos-parcelados-admin__notice--${ notice.status }` }
				role={ 'error' === notice.status ? 'alert' : 'status' }
			>
				{ notice.message }
			</div>
		);
	}

	render() {
		const {
			document,
			loadError,
			isSaving,
			notice,
			activeTab: requestedActiveTab,
		} = this.state;

		if ( null === document && ! loadError ) {
			return (
				<div className="darven-precos-parcelados-admin darven-precos-parcelados-admin--loading">
					<span
						className="darven-precos-parcelados-admin__spinner"
						role="progressbar"
						aria-label={ __( 'Loading settings…', 'darven-multiplos-precos-informativos' ) }
					/>
				</div>
			);
		}

		if ( loadError ) {
			return (
				<div className="darven-precos-parcelados-admin">
					{ this.renderNotice( {
						status: 'error',
						message: loadError,
					} ) }
				</div>
			);
		}

		const availableTabs = getAvailableTabs( document );
		const activeTab = availableTabs.some(
			( tab ) => tab.name === requestedActiveTab
		)
			? requestedActiveTab
			: availableTabs[ 0 ].name;
		return (
			<div className="darven-precos-parcelados-admin">
				<h1>Darven Preços Parcelados</h1>
				{ notice && this.renderNotice( notice ) }
				<div
					className="darven-precos-parcelados-admin__tabs"
					role="tablist"
					aria-label={ __( 'Settings sections', 'darven-multiplos-precos-informativos' ) }
				>
					{ availableTabs.map( ( tab, index ) => (
						<button
							key={ tab.name }
							id={ `darven-precos-parcelados-tab-${ tab.name }` }
							className={ `darven-precos-parcelados-admin__tab${
								activeTab === tab.name ? ' is-active' : ''
							}` }
							type="button"
							role="tab"
							aria-selected={ activeTab === tab.name }
							aria-controls={ `darven-precos-parcelados-panel-${ tab.name }` }
							tabIndex={ activeTab === tab.name ? 0 : -1 }
							onClick={ () => this.setActiveTab( tab.name ) }
							onKeyDown={ ( event ) =>
								this.handleTabKeyDown( event, index )
							}
						>
							{ tab.title }
						</button>
					) ) }
				</div>
				<fieldset
					disabled={ isSaving }
					aria-busy={ isSaving }
				>
					{ availableTabs.map( ( tab ) => (
						<div
							key={ tab.name }
							id={ `darven-precos-parcelados-panel-${ tab.name }` }
							role="tabpanel"
							aria-labelledby={ `darven-precos-parcelados-tab-${ tab.name }` }
							tabIndex="0"
							hidden={ activeTab !== tab.name }
						>
							{ 'pricing' === tab.name && (
								<PricingSection
									settings={ document.general || {} }
									onChange={ ( field, value ) =>
										this.updateField( 'general', field, value )
									}
								/>
							) }
							{ 'presentation' === tab.name && (
								<PresentationSection
									document={ document }
									onChange={ this.updateField }
								/>
							) }
							{ 'advanced' === tab.name && (
								<AdvancedSection
									settings={ document.compatibility || {} }
									onChange={ ( field, value ) =>
										this.updateField( 'compatibility', field, value )
									}
								/>
							) }
						</div>
					) ) }
					<button
						className="button button-primary"
						type="button"
						disabled={ isSaving }
						onClick={ this.save }
					>
						{ isSaving
							? __( 'Saving…', 'darven-multiplos-precos-informativos' )
							: __( 'Save settings', 'darven-multiplos-precos-informativos' ) }
					</button>
				</fieldset>
			</div>
		);
	}
}

export default SettingsApp;
