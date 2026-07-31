import { Button, Notice, Spinner, TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useSettingsStore } from '../shared/settings-store';
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

const SettingsApp = ( { apiClient } ) => {
	const store = useSettingsStore( apiClient );

	if ( store.isLoading ) {
		return <div className="darven-precos-parcelados-admin darven-precos-parcelados-admin--loading"><Spinner /></div>;
	}

	if ( store.loadError ) {
		return (
			<div className="darven-precos-parcelados-admin">
				<Notice status="error" isDismissible={ false }>{ store.loadError }</Notice>
			</div>
		);
	}

	return (
		<div className="darven-precos-parcelados-admin">
			<h1>{ __( 'Darven Preços Parcelados', 'darven-epi' ) }</h1>
			<Notice status="info" isDismissible={ false }>
				{ __( 'Saving here also keeps the legacy settings synchronized for compatibility.', 'darven-epi' ) }
			</Notice>
			{ store.notice && (
				<Notice status={ store.notice.status } isDismissible={ false }>{ store.notice.message }</Notice>
			) }
			<TabPanel tabs={ tabs }>
				{ ( tab ) => {
					const Section = sections[ tab.name ];
					return (
						<Section
							settings={ store.document[ tab.name ] || {} }
							onChange={ ( field, value ) => store.updateField( tab.name, field, value ) }
						/>
					);
				} }
			</TabPanel>
			<Button variant="primary" isBusy={ store.isSaving } disabled={ store.isSaving } onClick={ store.save }>
				{ store.isSaving ? __( 'Saving…', 'darven-epi' ) : __( 'Save settings', 'darven-epi' ) }
			</Button>
		</div>
	);
};

export default SettingsApp;
