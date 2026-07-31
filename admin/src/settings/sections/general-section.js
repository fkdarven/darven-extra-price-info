/* @jsx createElement */
import {
	Panel,
	PanelBody,
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const field = ( name ) => {
	if ( ! fields.general.includes( name ) ) {
		throw new Error( `Unknown general settings field: ${ name }` );
	}

	return name;
};

const value = ( settings, name ) => settings[ name ] || '';

const ToggleField = ( { settings, onChange, name, label, help } ) => (
	<ToggleControl
		name={ name }
		label={ label }
		help={ help }
		checked={ value( settings, name ) === name }
		onChange={ ( enabled ) => onChange( name, enabled ? name : '' ) }
	/>
);

const GeneralSection = ( { settings, onChange } ) => (
	<Panel>
		<PanelBody title={ __( 'Cash price', 'darven-epi' ) } initialOpen>
			<ToggleField
				settings={ settings }
				onChange={ onChange }
				name={ field( 'darven_epi_incash_is_enabled' ) }
				label={ __( 'Enable cash discount', 'darven-epi' ) }
			/>
			<SelectControl
				name={ field( 'darven_epi_type_of_discount' ) }
				label={ __( 'Discount type', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_type_of_discount' )
				) }
				options={ [
					{ label: __( 'Percent', 'darven-epi' ), value: 'percent' },
					{ label: __( 'Fixed', 'darven-epi' ), value: 'fixed' },
				] }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_type_of_discount' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_value_of_incash_discount' ) }
				label={ __( 'Discount value', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_value_of_incash_discount' )
				) }
				onChange={ ( next ) =>
					onChange(
						field( 'darven_epi_value_of_incash_discount' ),
						next
					)
				}
			/>
			<TextControl
				name={ field( 'darven_epi_minimum_incash_value' ) }
				label={ __( 'Minimum price for the discount', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_minimum_incash_value' )
				) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_minimum_incash_value' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_incash_prefix' ) }
				label={ __( 'Cash price prefix', 'darven-epi' ) }
				value={ value( settings, field( 'darven_epi_incash_prefix' ) ) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_incash_prefix' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_incash_suffix' ) }
				label={ __( 'Cash price suffix', 'darven-epi' ) }
				value={ value( settings, field( 'darven_epi_incash_suffix' ) ) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_incash_suffix' ), next )
				}
			/>
		</PanelBody>
		<PanelBody title={ __( 'Installments', 'darven-epi' ) } initialOpen>
			<ToggleField
				settings={ settings }
				onChange={ onChange }
				name={ field( 'darven_epi_installments_is_enabled' ) }
				label={ __( 'Enable installments', 'darven-epi' ) }
			/>
			<SelectControl
				name={ field( 'darven_epi_mode_of_view' ) }
				label={ __( 'Display mode', 'darven-epi' ) }
				value={ value( settings, field( 'darven_epi_mode_of_view' ) ) }
				options={ [
					{
						label: __( 'Maximum installments', 'darven-epi' ),
						value: 'default',
					},
					{
						label: __(
							'Maximum installments with popup',
							'darven-epi'
						),
						value: 'popup',
					},
					{
						label: __(
							'Without interest with popup',
							'darven-epi'
						),
						value: 'nofee',
					},
				] }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_mode_of_view' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_minimum_installments_value' ) }
				label={ __( 'Minimum price for installments', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_minimum_installments_value' )
				) }
				onChange={ ( next ) =>
					onChange(
						field( 'darven_epi_minimum_installments_value' ),
						next
					)
				}
			/>
			<TextControl
				name={ field( 'darven_epi_max_installments' ) }
				label={ __( 'Maximum installments', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_max_installments' )
				) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_max_installments' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_installments_prefix' ) }
				label={ __( 'Installments prefix', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_installments_prefix' )
				) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_installments_prefix' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_installments_suffix' ) }
				label={ __( 'Installments suffix', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_installments_suffix' )
				) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_installments_suffix' ), next )
				}
			/>
			<TextareaControl
				name={ field( 'darven_epi_popup_text' ) }
				label={ __( 'Popup text', 'darven-epi' ) }
				value={ value( settings, field( 'darven_epi_popup_text' ) ) }
				onChange={ ( next ) =>
					onChange( field( 'darven_epi_popup_text' ), next )
				}
			/>
			<TextControl
				name={ field( 'darven_epi_installments_interest_fee_from' ) }
				label={ __( 'Interest starts at installment', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_installments_interest_fee_from' )
				) }
				onChange={ ( next ) =>
					onChange(
						field( 'darven_epi_installments_interest_fee_from' ),
						next
					)
				}
			/>
			<TextControl
				name={ field(
					'darven_epi_installments_interest_fee_first_install'
				) }
				label={ __( 'First interest fee (%)', 'darven-epi' ) }
				value={ value(
					settings,
					field(
						'darven_epi_installments_interest_fee_first_install'
					)
				) }
				onChange={ ( next ) =>
					onChange(
						field(
							'darven_epi_installments_interest_fee_first_install'
						),
						next
					)
				}
			/>
			<TextControl
				name={ field( 'darven_epi_installments_interest_fee' ) }
				label={ __( 'Incremental interest fee (%)', 'darven-epi' ) }
				value={ value(
					settings,
					field( 'darven_epi_installments_interest_fee' )
				) }
				onChange={ ( next ) =>
					onChange(
						field( 'darven_epi_installments_interest_fee' ),
						next
					)
				}
			/>
		</PanelBody>
		<PanelBody
			title={ __( 'Interest fee table', 'darven-epi' ) }
			initialOpen={ false }
		>
			<ToggleField
				settings={ settings }
				onChange={ onChange }
				name={ field(
					'darven_epi_installments_interest_fee_is_table_enabled'
				) }
				label={ __( 'Use customized interest fees', 'darven-epi' ) }
				help={ __(
					'When enabled, only the values in the table below are considered.',
					'darven-epi'
				) }
			/>
			<TextareaControl
				name={ field( 'darven_epi_installments_interest_fee_table' ) }
				label={ __( 'Interest fees by installment', 'darven-epi' ) }
				help={ __(
					'Separate each percentage with a vertical bar, for example: 8,25|9,50|10,12.',
					'darven-epi'
				) }
				value={ value(
					settings,
					field( 'darven_epi_installments_interest_fee_table' )
				) }
				onChange={ ( next ) =>
					onChange(
						field( 'darven_epi_installments_interest_fee_table' ),
						next
					)
				}
			/>
		</PanelBody>
	</Panel>
);

export default GeneralSection;
