/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const options = [
	{
		value: 'first',
		label: __(
			'Original price, cash price, installments price',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		value: 'second',
		label: __(
			'Original price, installments price, cash price',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		value: 'third',
		label: __(
			'Cash price, original price, installments price',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		value: 'fourth',
		label: __(
			'Cash price, installments price, original price',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		value: 'fifth',
		label: __(
			'Installments price, original price, cash price',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		value: 'sixth',
		label: __(
			'Installments price, cash price, original price',
			'darven-multiplos-precos-informativos'
		),
	},
];

const positionFields = [
	[
		'darven_epi_single_product_position',
		__( 'Position on the single product page', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_catalog_product_position',
		__( 'Position on catalog pages', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_others_product_position',
		__( 'Position on other pages', 'darven-multiplos-precos-informativos' ),
	],
];

class PositionsSection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div className="darven-precos-parcelados-admin__section">
				<fieldset className="darven-precos-parcelados-admin__group">
					<legend>
						{ __( 'Statement positions', 'darven-multiplos-precos-informativos' ) }
					</legend>
					{ positionFields.map( ( [ name, label ] ) => {
						if ( ! fields.positions.includes( name ) ) {
							throw new Error(
								`Unknown positions settings field: ${ name }`
							);
						}

						return (
							<div
								className="darven-precos-parcelados-admin__field"
								key={ name }
							>
								<label htmlFor={ name }>{ label }</label>
								<select
									id={ name }
									name={ name }
									value={ settings[ name ] || 'first' }
									onChange={ ( event ) =>
										onChange( name, event.target.value )
									}
								>
									{ options.map( ( option ) => (
										<option
											key={ option.value }
											value={ option.value }
										>
											{ option.label }
										</option>
									) ) }
								</select>
							</div>
						);
					} ) }
				</fieldset>
			</div>
		);
	}
}

export default PositionsSection;
