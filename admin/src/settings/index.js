import './style.scss';

import { createElement, createRoot, render } from '@wordpress/element';

import { createSettingsApi } from '../shared/api';
import SettingsApp from './app';

const rootNode = document.getElementById(
	'darven-precos-parcelados-settings-root'
);

if ( rootNode ) {
	const config = window.DarvenPrecosParceladosSettings || {};
	const app = <SettingsApp apiClient={ createSettingsApi( config ) } />;
	if ( typeof createRoot === 'function' ) {
		createRoot( rootNode ).render( app );
	} else {
		render( app, rootNode );
	}
}
