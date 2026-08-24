import { Component, createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';
import {
	getPositionOrder,
	movePositionStatement,
} from '../placement-order';

const positionFields = [
	{
		name: 'darven_epi_single_product_position',
		title: __( 'Single product', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_catalog_product_position',
		title: __( 'Catalog and shop', 'darven-multiplos-precos-informativos' ),
	},
	{
		name: 'darven_epi_others_product_position',
		title: __( 'Other pages', 'darven-multiplos-precos-informativos' ),
	},
];

const statementLabels = {
	original: __( 'Original price', 'darven-multiplos-precos-informativos' ),
	cash: __( 'Cash price', 'darven-multiplos-precos-informativos' ),
	installments: __( 'Installment price', 'darven-multiplos-precos-informativos' ),
};

const statementMoveLabels = {
	original: __( 'original price', 'darven-multiplos-precos-informativos' ),
	cash: __( 'cash price', 'darven-multiplos-precos-informativos' ),
	installments: __( 'installment price', 'darven-multiplos-precos-informativos' ),
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
				__( '%1$s order: %2$s.', 'darven-multiplos-precos-informativos' ),
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
						'darven-multiplos-precos-informativos'
					) }
				</p>
				<div className="darven-precos-parcelados-admin__position-preview">
					<div
						className="darven-precos-parcelados-admin__product-placeholder"
						aria-hidden="true"
					>
						{ __( 'Product', 'darven-multiplos-precos-informativos' ) }
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
											__( 'Move %1$s up in %2$s', 'darven-multiplos-precos-informativos' ),
											statementMoveLabels[ statement ],
											title
										) }
										onClick={ () => this.moveStatement( statement, -1 ) }
									>
										{ __( 'Move up', 'darven-multiplos-precos-informativos' ) }
									</button>
									<button
										type="button"
										disabled={ index === order.length - 1 }
										aria-label={ sprintf(
											__( 'Move %1$s down in %2$s', 'darven-multiplos-precos-informativos' ),
											statementMoveLabels[ statement ],
											title
										) }
										onClick={ () => this.moveStatement( statement, 1 ) }
									>
										{ __( 'Move down', 'darven-multiplos-precos-informativos' ) }
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
						{ __( 'Statement positions', 'darven-multiplos-precos-informativos' ) }
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
