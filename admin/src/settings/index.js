import './style.scss';

import { createRoot } from '@wordpress/element';

import { createSettingsApi } from '../shared/api';
import SettingsApp from './app';

const rootNode = document.getElementById( 'darven-precos-parcelados-settings-root' );

if ( rootNode ) {
	const config = window.DarvenPrecosParceladosSettings || {};
	createRoot( rootNode ).render( <SettingsApp apiClient={ createSettingsApi( config ) } /> );
}
