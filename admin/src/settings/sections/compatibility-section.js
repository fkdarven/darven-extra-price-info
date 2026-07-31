/* @jsx createElement */
import { Notice, Panel, PanelBody, SelectControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const modeField = 'darven_epi_yith_dynamic_pricing_mode';
if ( ! fields.compatibility.includes( modeField ) ) {
	throw new Error( `Unknown compatibility settings field: ${ modeField }` );
}

const CompatibilitySection = ( { settings, onChange } ) => (
	<Panel>
		<PanelBody
			title={ __( 'YITH Dynamic Pricing', 'darven-epi' ) }
			initialOpen
		>
			<SelectControl
				name={ modeField }
				label={ __( 'Compatibility mode', 'darven-epi' ) }
				value={ settings[ modeField ] || 'auto' }
				options={ [
					{
						label: __( 'Automatic (recommended)', 'darven-epi' ),
						value: 'auto',
					},
					{
						label: __( 'Disabled', 'darven-epi' ),
						value: 'disabled',
					},
				] }
				onChange={ ( next ) => onChange( modeField, next ) }
			/>
			<Notice status="info" isDismissible={ false }>
				{ __(
					'Automatic mode uses a valid YITH price when available and safely falls back to WooCommerce pricing.',
					'darven-epi'
				) }
			</Notice>
		</PanelBody>
	</Panel>
);

export default CompatibilitySection;
