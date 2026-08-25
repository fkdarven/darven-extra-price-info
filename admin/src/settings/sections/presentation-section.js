import { Component, createElement } from '@wordpress/element';

import DisplaySection from './display-section';
import PositionsSection from './positions-section';

class PresentationSection extends Component {
	render() {
		const { document, onChange } = this.props;

		return (
			<div>
				<PositionsSection
					settings={ document.positions || {} }
					onChange={ ( field, value ) =>
						onChange( 'positions', field, value )
					}
				/>
				<DisplaySection
					settings={ document.display || {} }
					onChange={ ( field, value ) =>
						onChange( 'display', field, value )
					}
				/>
			</div>
		);
	}
}

export default PresentationSection;
