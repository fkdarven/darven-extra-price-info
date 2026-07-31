/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const colorFields = [
	[
		'darven_epi_color_of_incash_price',
		__( 'Cash price color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_incash_suffix',
		__( 'Cash suffix color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_incash_prefix',
		__( 'Cash prefix color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_installments_price',
		__( 'Installment price color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_installments_suffix',
		__( 'Installment suffix color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_installments_prefix',
		__( 'Installment prefix color', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_color_of_installments_install',
		__( 'Installment number color', 'darven-multiplos-precos-informativos' ),
	],
];

const fontFields = [
	[
		'darven_epi_font_size_of_incash_price',
		__( 'Cash price font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_incash_suffix',
		__( 'Cash suffix font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_incash_prefix',
		__( 'Cash prefix font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_installments_price',
		__( 'Installment price font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_installments_suffix',
		__( 'Installment suffix font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_installments_prefix',
		__( 'Installment prefix font size', 'darven-multiplos-precos-informativos' ),
	],
	[
		'darven_epi_font_size_of_installments_install',
		__( 'Installment number font size', 'darven-multiplos-precos-informativos' ),
	],
];

const fontOptions = Array.from( { length: 11 }, ( unused, index ) => {
	const value = ( 1 + index / 10 ).toFixed( 1 );
	return { label: `${ 100 + index * 10 }%`, value };
} );

const assertField = ( name ) => {
	if ( ! fields.display.includes( name ) ) {
		throw new Error( `Unknown display settings field: ${ name }` );
	}

	return name;
};

class DisplaySection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div className="darven-precos-parcelados-admin__section">
				<fieldset className="darven-precos-parcelados-admin__group">
					<legend>{ __( 'Colors', 'darven-multiplos-precos-informativos' ) }</legend>
					{ colorFields.map( ( [ rawName, label ] ) => {
						const name = assertField( rawName );
						return (
							<div
								className="darven-precos-parcelados-admin__field"
								key={ name }
							>
								<label htmlFor={ name }>{ label }</label>
								<input
									id={ name }
									name={ name }
									type="color"
									value={ settings[ name ] || '#000000' }
									onChange={ ( event ) =>
										onChange( name, event.target.value )
									}
								/>
							</div>
						);
					} ) }
				</fieldset>
				<fieldset className="darven-precos-parcelados-admin__group">
					<legend>{ __( 'Font sizes', 'darven-multiplos-precos-informativos' ) }</legend>
					{ fontFields.map( ( [ rawName, label ] ) => {
						const name = assertField( rawName );
						return (
							<div
								className="darven-precos-parcelados-admin__field"
								key={ name }
							>
								<label htmlFor={ name }>{ label }</label>
								<select
									id={ name }
									name={ name }
									value={ settings[ name ] || '1.0' }
									onChange={ ( event ) =>
										onChange( name, event.target.value )
									}
								>
									{ fontOptions.map( ( option ) => (
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

export default DisplaySection;
