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
			<div className="darven-precos-parcelados-admin__section darven-precos-parcelados-admin__compatibility-section">
				<fieldset className="darven-precos-parcelados-admin__group darven-precos-parcelados-admin__compatibility-group">
					<legend>
						{ __(
							'YITH Dynamic Pricing',
							'darven-multiplos-precos-informativos'
						) }
					</legend>
					<p className="darven-precos-parcelados-admin__compatibility-description">
						{ __(
							'Choose how this integration should resolve prices when YITH Dynamic Pricing is active.',
							'darven-multiplos-precos-informativos'
						) }
					</p>
					<div className="darven-precos-parcelados-admin__field">
						<label htmlFor={ modeField }>
							{ __(
								'Compatibility mode',
								'darven-multiplos-precos-informativos'
							) }
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
									'darven-multiplos-precos-informativos'
								) }
							</option>
							<option value="disabled">
								{ __(
									'Disabled',
									'darven-multiplos-precos-informativos'
								) }
							</option>
						</select>
					</div>
					<div
						className="darven-precos-parcelados-admin__notice darven-precos-parcelados-admin__notice--info"
						role="status"
					>
						{ __(
							'Automatic mode uses a valid YITH price when available and safely falls back to WooCommerce pricing.',
							'darven-multiplos-precos-informativos'
						) }
					</div>
				</fieldset>
			</div>
		);
	}
}

export default CompatibilitySection;
