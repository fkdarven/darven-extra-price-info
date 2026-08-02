/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	createSettingsState,
	updateSettingsField,
} from '../shared/settings-store';
import { normalizeRestError } from '../shared/api';
import CompatibilitySection from './sections/compatibility-section';
import DisplaySection from './sections/display-section';
import GeneralSection from './sections/general-section';
import PositionsSection from './sections/positions-section';

const tabs = [
	{ name: 'general', title: __( 'General', 'darven-multiplos-precos-informativos' ) },
	{ name: 'display', title: __( 'Display', 'darven-multiplos-precos-informativos' ) },
	{ name: 'positions', title: __( 'Positions', 'darven-multiplos-precos-informativos' ) },
	{ name: 'compatibility', title: __( 'Compatibility', 'darven-multiplos-precos-informativos' ) },
];

const sections = {
	general: GeneralSection,
	display: DisplaySection,
	positions: PositionsSection,
	compatibility: CompatibilitySection,
};

class SettingsApp extends Component {
	constructor( props ) {
		super( props );
		this.state = createSettingsState();
		this.isActive = false;
		this.editableFieldset = null;
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

	componentDidUpdate( previousProps, previousState ) {
		if (
			previousState.isSaving !== this.state.isSaving &&
			this.editableFieldset
		) {
			this.editableFieldset
				.querySelectorAll( 'input, select, textarea, button' )
				.forEach( ( control ) => {
					control.disabled = this.state.isSaving;
				} );
		}
	}

	setActiveTab = ( activeTab ) => {
		this.setState( { activeTab } );
	};

	handleTabKeyDown = ( event, currentIndex ) => {
		let nextIndex;

		switch ( event.key ) {
			case 'ArrowLeft':
				nextIndex = ( currentIndex - 1 + tabs.length ) % tabs.length;
				break;
			case 'ArrowRight':
				nextIndex = ( currentIndex + 1 ) % tabs.length;
				break;
			case 'Home':
				nextIndex = 0;
				break;
			case 'End':
				nextIndex = tabs.length - 1;
				break;
			default:
				return;
		}

		event.preventDefault();
		const nextTab = tabs[ nextIndex ];
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
		const { document, loadError, isSaving, notice, activeTab } = this.state;

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

		const Section = sections[ activeTab ];
		const tabId = `darven-precos-parcelados-tab-${ activeTab }`;
		const panelId = `darven-precos-parcelados-panel-${ activeTab }`;

		return (
			<div className="darven-precos-parcelados-admin">
				<h1>Darven Preços Parcelados</h1>
				{ this.renderNotice( {
					status: 'info',
					message: __(
						'Saving here also keeps the legacy settings synchronized for compatibility.',
						'darven-multiplos-precos-informativos'
					),
				} ) }
				{ notice && this.renderNotice( notice ) }
				<div
					className="darven-precos-parcelados-admin__tabs"
					role="tablist"
					aria-label={ __( 'Settings sections', 'darven-multiplos-precos-informativos' ) }
				>
					{ tabs.map( ( tab, index ) => (
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
					ref={ ( element ) => {
						this.editableFieldset = element;
					} }
					disabled={ isSaving }
					aria-busy={ isSaving }
				>
					<div
						id={ panelId }
						role="tabpanel"
						aria-labelledby={ tabId }
						tabIndex="0"
					>
						<Section
							settings={ document[ activeTab ] || {} }
							onChange={ ( field, value ) =>
								this.updateField( activeTab, field, value )
							}
						/>
					</div>
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
