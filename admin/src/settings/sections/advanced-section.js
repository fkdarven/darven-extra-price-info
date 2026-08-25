import { Component, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import CompatibilitySection from './compatibility-section';

class AdvancedSection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div>
				<CompatibilitySection settings={ settings } onChange={ onChange } />
				<div className="darven-precos-parcelados-admin__notice darven-precos-parcelados-admin__notice--info">
					{ __(
						'Existing legacy settings remain synchronized automatically. No action is normally required.',
						'darven-multiplos-precos-informativos'
					) }
				</div>
			</div>
		);
	}
}

export default AdvancedSection;
