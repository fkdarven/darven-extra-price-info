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
	{ name: 'general', title: __( 'General', 'darven-epi' ) },
	{ name: 'display', title: __( 'Visual', 'darven-epi' ) },
	{ name: 'positions', title: __( 'Positions', 'darven-epi' ) },
	{ name: 'compatibility', title: __( 'Compatibility', 'darven-epi' ) },
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
								'darven-epi'
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
		this.setState( { activeTab } );
	};

	updateField = ( section, field, value ) => {
		this.setState( ( current ) => ( {
			document: updateSettingsField(
				current.document,
				section,
				field,
				value
			),
			notice: null,
		} ) );
	};

	save = async () => {
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
						message: __( 'Settings saved.', 'darven-epi' ),
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
								'darven-epi'
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
						aria-label={ __( 'Loading settings…', 'darven-epi' ) }
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
				<h1>{ __( 'Darven Preços Parcelados', 'darven-epi' ) }</h1>
				{ this.renderNotice( {
					status: 'info',
					message: __(
						'Saving here also keeps the legacy settings synchronized for compatibility.',
						'darven-epi'
					),
				} ) }
				{ notice && this.renderNotice( notice ) }
				<div
					className="darven-precos-parcelados-admin__tabs"
					role="tablist"
					aria-label={ __( 'Settings sections', 'darven-epi' ) }
				>
					{ tabs.map( ( tab ) => (
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
							onClick={ () => this.setActiveTab( tab.name ) }
						>
							{ tab.title }
						</button>
					) ) }
				</div>
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
						? __( 'Saving…', 'darven-epi' )
						: __( 'Save settings', 'darven-epi' ) }
				</button>
			</div>
		);
	}
}

export default SettingsApp;
