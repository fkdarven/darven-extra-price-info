/* @jsx createElement */
import { Component, createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';
import {
	getPositionOrder,
	movePositionStatement,
} from '../placement-order';

const domain = 'darven-multiplos-precos-informativos';

const positionFields = [
	{
		name: 'darven_epi_single_product_position',
		title: __( 'Single product', domain ),
	},
	{
		name: 'darven_epi_catalog_product_position',
		title: __( 'Catalog and shop', domain ),
	},
	{
		name: 'darven_epi_others_product_position',
		title: __( 'Other pages', domain ),
	},
];

const statementLabels = {
	original: __( 'Original price', domain ),
	cash: __( 'Cash price', domain ),
	installments: __( 'Installment price', domain ),
};

const statementMoveLabels = {
	original: __( 'original price', domain ),
	cash: __( 'cash price', domain ),
	installments: __( 'installment price', domain ),
};

class PositionCard extends Component {
	constructor( props ) {
		super( props );
		this.state = { announcement: '' };
	}

	moveStatement = ( statement, offset ) => {
		const { name, onChange, title, value } = this.props;
		const nextValue = movePositionStatement( value, statement, offset );
		const nextOrder = getPositionOrder( nextValue );

		onChange( name, nextValue );
		this.setState( {
			announcement: sprintf(
				__( '%1$s order: %2$s.', domain ),
				title,
				nextOrder
					.map( ( item ) => statementLabels[ item ] )
					.join( ', ' )
			),
		} );
	};

	render() {
		const { name, title, value } = this.props;
		const { announcement } = this.state;
		const order = getPositionOrder( value || 'first' );
		const headingId = `${ name }-heading`;

		return (
			<section
				className="darven-precos-parcelados-admin__position-card"
				data-position-field={ name }
				aria-labelledby={ headingId }
			>
				<h3 id={ headingId }>{ title }</h3>
				<p className="darven-precos-parcelados-admin__position-description">
					{ __(
						'This preview shows statement order only and does not represent your theme.',
						domain
					) }
				</p>
				<div className="darven-precos-parcelados-admin__position-preview">
					<div
						className="darven-precos-parcelados-admin__product-placeholder"
						aria-hidden="true"
					>
						{ __( 'Product', domain ) }
					</div>
					<ul className="darven-precos-parcelados-admin__position-statements">
						{ order.map( ( statement, index ) => (
							<li
								className="darven-precos-parcelados-admin__position-statement"
								data-statement={ statement }
								key={ statement }
							>
								<span>{ statementLabels[ statement ] }</span>
								<span className="darven-precos-parcelados-admin__position-actions">
									<button
										type="button"
										disabled={ 0 === index }
										aria-label={ sprintf(
											__( 'Move %1$s up in %2$s', domain ),
											statementMoveLabels[ statement ],
											title
										) }
										onClick={ () => this.moveStatement( statement, -1 ) }
									>
										{ __( 'Move up', domain ) }
									</button>
									<button
										type="button"
										disabled={ index === order.length - 1 }
										aria-label={ sprintf(
											__( 'Move %1$s down in %2$s', domain ),
											statementMoveLabels[ statement ],
											title
										) }
										onClick={ () => this.moveStatement( statement, 1 ) }
									>
										{ __( 'Move down', domain ) }
									</button>
								</span>
							</li>
						) ) }
					</ul>
				</div>
				<div aria-live="polite" className="darven-precos-parcelados-admin__position-status">
					{ announcement }
				</div>
			</section>
		);
	}
}

class PositionsSection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div className="darven-precos-parcelados-admin__section">
				<fieldset className="darven-precos-parcelados-admin__group">
					<legend>
						{ __( 'Statement positions', domain ) }
					</legend>
					{ positionFields.map( ( { name, title } ) => {
						if ( ! fields.positions.includes( name ) ) {
							throw new Error(
								`Unknown positions settings field: ${ name }`
							);
						}

						return (
							<PositionCard
								key={ name }
								name={ name }
								title={ title }
								value={ settings[ name ] || 'first' }
								onChange={ onChange }
							/>
						);
					} ) }
				</fieldset>
			</div>
		);
	}
}

export default PositionsSection;
