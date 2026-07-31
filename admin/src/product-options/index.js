/* @jsx createElement */
import './style.scss';

import { createElement, render } from '@wordpress/element';

import ProductOptionsApp, { createProductOptionsApi } from './app';

const panelNode = document.getElementById(
	'darven-precos-parcelados-product-options-panel'
);
const rootNode = panelNode
	? panelNode.querySelector(
			'#darven-precos-parcelados-product-options-root'
	  )
	: null;

if ( rootNode ) {
	const config = window.DarvenPrecosParceladosProductOptions || {};
	const productId = Number( config.productId ) || 0;
	render(
		<ProductOptionsApp
			apiClient={
				productId > 0 ? createProductOptionsApi( config ) : null
			}
			productId={ productId }
		/>,
		rootNode
	);
}
