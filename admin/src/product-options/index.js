/* @jsx createElement */
import './style.scss';

import { createElement, render } from '@wordpress/element';

import ProductOptionsApp, { createProductOptionsApi } from './app';

const rootNode = document.getElementById(
	'darven-precos-parcelados-product-options-root'
);

if ( rootNode ) {
	const config = window.DarvenPrecosParceladosProductOptions || {};
	render(
		<ProductOptionsApp apiClient={ createProductOptionsApi( config ) } />,
		rootNode
	);
}
