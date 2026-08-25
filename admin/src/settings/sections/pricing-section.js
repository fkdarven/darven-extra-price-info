import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const cashEnablementField = {
	name: 'darven_epi_incash_is_enabled',
	type: 'checkbox',
	label: __( 'Enable cash discount', 'darven-multiplos-precos-informativos' ),
};

const cashFields = [
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

const installmentEnablementField = {
	name: 'darven_epi_installments_is_enabled',
	type: 'checkbox',
	label: __( 'Enable installments', 'darven-multiplos-precos-informativos' ),
};

const installmentFields = [
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
];

const popupTextField = {
	name: 'darven_epi_popup_text',
	type: 'textarea',
	label: __( 'Popup text', 'darven-multiplos-precos-informativos' ),
};

const interestFields = [
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

const customTableEnablementField = {
	name: 'darven_epi_installments_interest_fee_is_table_enabled',
	type: 'checkbox',
	label: __( 'Use customized interest fees', 'darven-multiplos-precos-informativos' ),
	help: __(
		'When enabled, only the values in the table below are considered.',
		'darven-multiplos-precos-informativos'
	),
};

const customTableField = {
	name: 'darven_epi_installments_interest_fee_table',
	type: 'textarea',
	label: __( 'Interest fees by installment', 'darven-multiplos-precos-informativos' ),
	help: __(
		'Separate each percentage with a vertical bar, for example: 8,25|9,50|10,12.',
		'darven-multiplos-precos-informativos'
	),
};

const assertField = ( name ) => {
	if ( ! fields.general.includes( name ) ) {
		throw new Error( `Unknown general settings field: ${ name }` );
	}

	return name;
};

const allFieldDefinitions = [
	cashEnablementField,
	...cashFields,
	installmentEnablementField,
	...installmentFields,
	popupTextField,
	...interestFields,
	customTableEnablementField,
	customTableField,
];

allFieldDefinitions.forEach( ( definition ) => assertField( definition.name ) );

const missingFieldDefinitions = fields.general.filter(
	( name ) => ! allFieldDefinitions.some( ( definition ) => definition.name === name )
);

if ( missingFieldDefinitions.length ) {
	throw new Error(
		`Missing general settings field definitions: ${ missingFieldDefinitions.join(
			', '
		) }`
	);
}

const isEnabled = ( settings, field ) => settings[ field ] === field;

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
					checked={ isEnabled( settings, name ) }
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

const renderFields = ( settings, onChange, definitions ) =>
	definitions.map( ( definition ) => renderField( settings, onChange, definition ) );

const PricingSection = ( { settings, onChange } ) => {
	const installmentsEnabled = isEnabled(
		settings,
		installmentEnablementField.name
	);
	const customTableEnabled = isEnabled(
		settings,
		customTableEnablementField.name
	);
	const showsPopupText = [ 'popup', 'nofee' ].includes(
		settings.darven_epi_mode_of_view
	);

	return (
		<div className="darven-precos-parcelados-admin__section">
			<fieldset className="darven-precos-parcelados-admin__group">
				<legend>
					{ __( 'Cash price', 'darven-multiplos-precos-informativos' ) }
					{ renderField( settings, onChange, cashEnablementField ) }
				</legend>
				{ isEnabled( settings, cashEnablementField.name ) &&
					renderFields( settings, onChange, cashFields ) }
			</fieldset>
			<fieldset className="darven-precos-parcelados-admin__group">
				<legend>
					{ __( 'Installments', 'darven-multiplos-precos-informativos' ) }
					{ renderField( settings, onChange, installmentEnablementField ) }
				</legend>
				{ installmentsEnabled && [
						...renderFields( settings, onChange, installmentFields ),
						showsPopupText
							? renderField( settings, onChange, popupTextField )
							: null,
						<details key="interest-rules">
							<summary>
								{ customTableEnabled
									? __( 'Custom rates configured', 'darven-multiplos-precos-informativos' )
									: __( 'Standard calculation', 'darven-multiplos-precos-informativos' ) }
							</summary>
							{ renderFields( settings, onChange, interestFields ) }
							{ renderField(
								settings,
								onChange,
								customTableEnablementField
							) }
							{ customTableEnabled &&
								renderField( settings, onChange, customTableField ) }
						</details>,
					] }
			</fieldset>
		</div>
	);
};

export default PricingSection;
