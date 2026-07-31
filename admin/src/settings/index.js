/* @jsx createElement */
import './style.scss';

import { createElement, render } from '@wordpress/element';

import { createSettingsApi } from '../shared/api';
import SettingsApp from './app';

const rootNode = document.getElementById(
	'darven-precos-parcelados-settings-root'
);

if ( rootNode ) {
	const config = window.DarvenPrecosParceladosSettings || {};
	render(
		<SettingsApp apiClient={ createSettingsApi( config ) } />,
		rootNode
	);
}
