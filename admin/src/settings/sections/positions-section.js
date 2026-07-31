/* @jsx createElement */
import { Panel, PanelBody, SelectControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const options = [
	{
		value: 'first',
		label: __(
			'Original price, cash price, installments price',
			'darven-epi'
		),
	},
	{
		value: 'second',
		label: __(
			'Original price, installments price, cash price',
			'darven-epi'
		),
	},
	{
		value: 'third',
		label: __(
			'Cash price, original price, installments price',
			'darven-epi'
		),
	},
	{
		value: 'fourth',
		label: __(
			'Cash price, installments price, original price',
			'darven-epi'
		),
	},
	{
		value: 'fifth',
		label: __(
			'Installments price, original price, cash price',
			'darven-epi'
		),
	},
	{
		value: 'sixth',
		label: __(
			'Installments price, cash price, original price',
			'darven-epi'
		),
	},
];

const positionFields = [
	[
		'darven_epi_single_product_position',
		__( 'Position on the single product page', 'darven-epi' ),
	],
	[
		'darven_epi_catalog_product_position',
		__( 'Position on catalog pages', 'darven-epi' ),
	],
	[
		'darven_epi_others_product_position',
		__( 'Position on other pages', 'darven-epi' ),
	],
];

const PositionsSection = ( { settings, onChange } ) => (
	<Panel>
		<PanelBody
			title={ __( 'Statement positions', 'darven-epi' ) }
			initialOpen
		>
			{ positionFields.map( ( [ name, label ] ) => {
				if ( ! fields.positions.includes( name ) ) {
					throw new Error(
						`Unknown positions settings field: ${ name }`
					);
				}

				return (
					<SelectControl
						key={ name }
						name={ name }
						label={ label }
						value={ settings[ name ] || 'first' }
						options={ options }
						onChange={ ( next ) => onChange( name, next ) }
					/>
				);
			} ) }
		</PanelBody>
	</Panel>
);

export default PositionsSection;
