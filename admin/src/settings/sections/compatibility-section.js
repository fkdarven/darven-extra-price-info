/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const modeField = 'darven_epi_yith_dynamic_pricing_mode';
if ( ! fields.compatibility.includes( modeField ) ) {
	throw new Error( `Unknown compatibility settings field: ${ modeField }` );
}

class CompatibilitySection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div className="darven-precos-parcelados-admin__section">
				<fieldset className="darven-precos-parcelados-admin__group">
					<legend>
						{ __( 'YITH Dynamic Pricing', 'darven-epi' ) }
					</legend>
					<div className="darven-precos-parcelados-admin__field">
						<label htmlFor={ modeField }>
							{ __( 'Compatibility mode', 'darven-epi' ) }
						</label>
						<select
							id={ modeField }
							name={ modeField }
							value={ settings[ modeField ] || 'auto' }
							onChange={ ( event ) =>
								onChange( modeField, event.target.value )
							}
						>
							<option value="auto">
								{ __(
									'Automatic (recommended)',
									'darven-epi'
								) }
							</option>
							<option value="disabled">
								{ __( 'Disabled', 'darven-epi' ) }
							</option>
						</select>
					</div>
					<div
						className="darven-precos-parcelados-admin__notice darven-precos-parcelados-admin__notice--info"
						role="status"
					>
						{ __(
							'Automatic mode uses a valid YITH price when available and safely falls back to WooCommerce pricing.',
							'darven-epi'
						) }
					</div>
				</fieldset>
			</div>
		);
	}
}

export default CompatibilitySection;
