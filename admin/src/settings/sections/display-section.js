import { Component, createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import fields from '../../shared/settings-fields.json';

const appearanceGroups = [
	{
		id: 'cash-price',
		title: __( 'Cash price', 'darven-multiplos-precos-informativos' ),
		parts: [
			[
				__( 'Price', 'darven-multiplos-precos-informativos' ),
				'darven_epi_color_of_incash_price',
				'darven_epi_font_size_of_incash_price',
			],
			[
				__(
					'Text before price',
					'darven-multiplos-precos-informativos'
				),
				'darven_epi_color_of_incash_prefix',
				'darven_epi_font_size_of_incash_prefix',
			],
			[
				__(
					'Text after price',
					'darven-multiplos-precos-informativos'
				),
				'darven_epi_color_of_incash_suffix',
				'darven_epi_font_size_of_incash_suffix',
			],
		],
	},
	{
		id: 'installment-price',
		title: __(
			'Installment price',
			'darven-multiplos-precos-informativos'
		),
		parts: [
			[
				__( 'Price', 'darven-multiplos-precos-informativos' ),
				'darven_epi_color_of_installments_price',
				'darven_epi_font_size_of_installments_price',
			],
			[
				__(
					'Installment number',
					'darven-multiplos-precos-informativos'
				),
				'darven_epi_color_of_installments_install',
				'darven_epi_font_size_of_installments_install',
			],
			[
				__(
					'Text before price',
					'darven-multiplos-precos-informativos'
				),
				'darven_epi_color_of_installments_prefix',
				'darven_epi_font_size_of_installments_prefix',
			],
			[
				__(
					'Text after price',
					'darven-multiplos-precos-informativos'
				),
				'darven_epi_color_of_installments_suffix',
				'darven_epi_font_size_of_installments_suffix',
			],
		],
	},
];

const fontOptions = Array.from( { length: 11 }, ( unused, index ) => {
	const value = ( 1 + index / 10 ).toFixed( 1 );
	return { label: `${ 100 + index * 10 }%`, value };
} );

const assertField = ( name ) => {
	if ( ! fields.display.includes( name ) ) {
		throw new Error( `Unknown display settings field: ${ name }` );
	}

	return name;
};

const getColorLabel = ( statement, part ) => {
	return sprintf(
		/* translators: 1: price statement, 2: statement part, such as Price or Text before price. */
		__( '%1$s — color for %2$s', 'darven-multiplos-precos-informativos' ),
		statement,
		part
	);
};

const getFontSizeLabel = ( statement, part ) => {
	return sprintf(
		/* translators: 1: price statement, 2: statement part, such as Price or Text before price. */
		__(
			'%1$s — font size for %2$s',
			'darven-multiplos-precos-informativos'
		),
		statement,
		part
	);
};

class DisplaySection extends Component {
	render() {
		const { settings, onChange } = this.props;

		return (
			<div className="darven-precos-parcelados-admin__section">
				<fieldset className="darven-precos-parcelados-admin__group darven-precos-parcelados-admin__appearance-group">
					<legend>
						{ __(
							'Appearance',
							'darven-multiplos-precos-informativos'
						) }
					</legend>
					{ appearanceGroups.map( ( group ) => (
						<section
							aria-labelledby={ `darven-appearance-${ group.id }-heading` }
							className="darven-precos-parcelados-admin__appearance-subgroup"
							key={ group.title }
						>
							<h3
								id={ `darven-appearance-${ group.id }-heading` }
							>
								{ group.title }
							</h3>
							<div className="darven-precos-parcelados-admin__appearance-rows">
								{ group.parts.map(
									( [
										part,
										rawColorName,
										rawFontSizeName,
									] ) => {
										const colorName =
											assertField( rawColorName );
										const fontSizeName =
											assertField( rawFontSizeName );

										return (
											<div
												className="darven-precos-parcelados-admin__appearance-row"
												data-appearance-row={
													colorName
												}
												key={ colorName }
											>
												<p className="darven-precos-parcelados-admin__appearance-part">
													{ part }
												</p>
												<div className="darven-precos-parcelados-admin__appearance-control">
													<label
														htmlFor={ colorName }
													>
														{ getColorLabel(
															group.title,
															part
														) }
													</label>
													<input
														id={ colorName }
														name={ colorName }
														type="color"
														value={
															settings[
																colorName
															] || '#000000'
														}
														onChange={ ( event ) =>
															onChange(
																colorName,
																event.target
																	.value
															)
														}
													/>
												</div>
												<div className="darven-precos-parcelados-admin__appearance-control">
													<label
														htmlFor={ fontSizeName }
													>
														{ getFontSizeLabel(
															group.title,
															part
														) }
													</label>
													<select
														id={ fontSizeName }
														name={ fontSizeName }
														value={
															settings[
																fontSizeName
															] || '1.0'
														}
														onChange={ ( event ) =>
															onChange(
																fontSizeName,
																event.target
																	.value
															)
														}
													>
														{ fontOptions.map(
															( option ) => (
																<option
																	key={
																		option.value
																	}
																	value={
																		option.value
																	}
																>
																	{
																		option.label
																	}
																</option>
															)
														) }
													</select>
												</div>
											</div>
										);
									}
								) }
							</div>
						</section>
					) ) }
				</fieldset>
			</div>
		);
	}
}

export default DisplaySection;
