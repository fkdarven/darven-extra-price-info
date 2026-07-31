import { Panel, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const colorFields = [
	[ 'darven_epi_color_of_incash_price', __( 'Cash price color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_incash_suffix', __( 'Cash suffix color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_incash_prefix', __( 'Cash prefix color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_installments_price', __( 'Installment price color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_installments_suffix', __( 'Installment suffix color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_installments_prefix', __( 'Installment prefix color', 'darven-epi' ) ],
	[ 'darven_epi_color_of_installments_install', __( 'Installment number color', 'darven-epi' ) ],
];

const fontFields = [
	[ 'darven_epi_font_size_of_incash_price', __( 'Cash price font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_incash_suffix', __( 'Cash suffix font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_incash_prefix', __( 'Cash prefix font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_installments_price', __( 'Installment price font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_installments_suffix', __( 'Installment suffix font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_installments_prefix', __( 'Installment prefix font size', 'darven-epi' ) ],
	[ 'darven_epi_font_size_of_installments_install', __( 'Installment number font size', 'darven-epi' ) ],
];

const fontOptions = Array.from( { length: 11 }, ( unused, index ) => {
	const value = ( 1 + ( index / 10 ) ).toFixed( 1 );
	return { label: `${ 100 + ( index * 10 ) }%`, value };
} );

const assertField = ( name ) => {
	if ( ! fields.display.includes( name ) ) {
		throw new Error( `Unknown display settings field: ${ name }` );
	}

	return name;
};

const DisplaySection = ( { settings, onChange } ) => (
	<Panel>
		<PanelBody title={ __( 'Colors', 'darven-epi' ) } initialOpen>
			{ colorFields.map( ( [ name, label ] ) => (
				<TextControl
					key={ name }
					name={ assertField( name ) }
					type="color"
					label={ label }
					value={ settings[ name ] || '#000000' }
					onChange={ ( next ) => onChange( name, next ) }
				/>
			) ) }
		</PanelBody>
		<PanelBody title={ __( 'Font sizes', 'darven-epi' ) } initialOpen>
			{ fontFields.map( ( [ name, label ] ) => (
				<SelectControl
					key={ name }
					name={ assertField( name ) }
					label={ label }
					value={ settings[ name ] || '1.0' }
					options={ fontOptions }
					onChange={ ( next ) => onChange( name, next ) }
				/>
			) ) }
		</PanelBody>
	</Panel>
);

export default DisplaySection;
