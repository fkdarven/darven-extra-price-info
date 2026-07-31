/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const cashFields = [
	{
		name: 'darven_epi_incash_is_enabled',
		type: 'checkbox',
		label: __( 'Enable cash discount', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_type_of_discount',
		type: 'select',
		label: __( 'Discount type', 'darven-multiplos-precos-informativos' ),
		options: [
			{ label: __( 'Percent', 'darven-multiplos-precos-informativos' ), value: 'percent' },
			{ label: __( 'Fixed', 'darven-multiplos-precos-informativos' ), value: 'fixed' },
		],
	},
	{
		name: 'darven_epi_value_of_incash_discount',
		label: __( 'Discount value', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_minimum_incash_value',
		label: __( 'Minimum price for the discount', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_incash_prefix',
		label: __( 'Cash price prefix', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_incash_suffix',
		label: __( 'Cash price suffix', 'darven-multiplos-precos-informativos' ),
	},
];

const installmentFields = [
	{
		name: 'darven_epi_installments_is_enabled',
		type: 'checkbox',
		label: __( 'Enable installments', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_mode_of_view',
		type: 'select',
		label: __( 'Display mode', 'darven-multiplos-precos-informativos' ),
		options: [
			{
				label: __( 'Maximum installments', 'darven-multiplos-precos-informativos' ),
				value: 'default',
			},
			{
				label: __( 'Maximum installments with popup', 'darven-multiplos-precos-informativos' ),
				value: 'popup',
			},
			{
				label: __( 'Without interest with popup', 'darven-multiplos-precos-informativos' ),
				value: 'nofee',
			},
		],
	},
	{
		name: 'darven_epi_minimum_installments_value',
		label: __( 'Minimum price for installments', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_max_installments',
		label: __( 'Maximum installments', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_installments_prefix',
		label: __( 'Installments prefix', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_installments_suffix',
		label: __( 'Installments suffix', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_popup_text',
		type: 'textarea',
		label: __( 'Popup text', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_installments_interest_fee_from',
		label: __( 'Interest starts at installment', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_installments_interest_fee_first_install',
		label: __( 'First interest fee (%)', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_installments_interest_fee',
		label: __( 'Incremental interest fee (%)', 'darven-multiplos-precos-informativos' ),
	},
];

const tableFields = [
	{
		name: 'darven_epi_installments_interest_fee_is_table_enabled',
		type: 'checkbox',
		label: __( 'Use customized interest fees', 'darven-multiplos-precos-informativos' ),
		help: __(
			'When enabled, only the values in the table below are considered.',
			'darven-multiplos-precos-informativos'
		),
	},
	{
		name: 'darven_epi_installments_interest_fee_table',
		type: 'textarea',
		label: __( 'Interest fees by installment', 'darven-multiplos-precos-informativos' ),
		help: __(
			'Separate each percentage with a vertical bar, for example: 8,25|9,50|10,12.',
			'darven-multiplos-precos-informativos'
		),
	},
];

const assertField = ( name ) => {
	if ( ! fields.general.includes( name ) ) {
		throw new Error( `Unknown general settings field: ${ name }` );
	}

	return name;
};

const renderField = ( settings, onChange, definition ) => {
	const name = assertField( definition.name );
	const helpId = definition.help ? `${ name }-description` : undefined;
	let control;

	if ( 'checkbox' === definition.type ) {
		control = (
			<label
				className="darven-precos-parcelados-admin__toggle"
				htmlFor={ name }
			>
				<input
					id={ name }
					name={ name }
					type="checkbox"
					checked={ settings[ name ] === name }
					aria-describedby={ helpId }
					onChange={ ( event ) =>
						onChange( name, event.target.checked ? name : '' )
					}
				/>
				<span>{ definition.label }</span>
			</label>
		);
	} else if ( 'select' === definition.type ) {
		control = (
			<select
				id={ name }
				name={ name }
				value={ settings[ name ] || '' }
				onChange={ ( event ) => onChange( name, event.target.value ) }
			>
				{ definition.options.map( ( option ) => (
					<option key={ option.value } value={ option.value }>
						{ option.label }
					</option>
				) ) }
			</select>
		);
	} else if ( 'textarea' === definition.type ) {
		control = (
			<textarea
				id={ name }
				name={ name }
				value={ settings[ name ] || '' }
				aria-describedby={ helpId }
				onChange={ ( event ) => onChange( name, event.target.value ) }
			/>
		);
	} else {
		control = (
			<input
				id={ name }
				name={ name }
				type="text"
				value={ settings[ name ] || '' }
				onChange={ ( event ) => onChange( name, event.target.value ) }
			/>
		);
	}

	return (
		<div className="darven-precos-parcelados-admin__field" key={ name }>
			{ 'checkbox' !== definition.type && (
				<label htmlFor={ name }>{ definition.label }</label>
			) }
			{ control }
			{ definition.help && (
				<p id={ helpId } className="description">
					{ definition.help }
				</p>
			) }
		</div>
	);
};

class GeneralSection extends Component {
	renderGroup( title, definitions ) {
		const { settings, onChange } = this.props;

		return (
			<fieldset className="darven-precos-parcelados-admin__group">
				<legend>{ title }</legend>
				{ definitions.map( ( definition ) =>
					renderField( settings, onChange, definition )
				) }
			</fieldset>
		);
	}

	render() {
		return (
			<div className="darven-precos-parcelados-admin__section">
				{ this.renderGroup(
					__( 'Cash price', 'darven-multiplos-precos-informativos' ),
					cashFields
				) }
				{ this.renderGroup(
					__( 'Installments', 'darven-multiplos-precos-informativos' ),
					installmentFields
				) }
				{ this.renderGroup(
					__( 'Interest fee table', 'darven-multiplos-precos-informativos' ),
					tableFields
				) }
			</div>
		);
	}
}

export default GeneralSection;
