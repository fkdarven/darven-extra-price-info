<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;

final class PositionsFields {
	/**
	 * @var array<string,string>
	 */
	private $options = array();

	public function register(): void {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_positions', array( $this, 'sanitize' ) );
		$options       = get_option( 'darven_epi_option_positions' );
		$this->options = is_array( $options ) ? $options : array();

		$this->registerSettingsFields();
	}

	public function renderSingleProductPosition(): void {
		$this->renderSelect( 'darven_epi_single_product_position' );
	}

	public function renderCatalogProductPosition(): void {
		$this->renderSelect( 'darven_epi_catalog_product_position' );
	}

	public function renderOtherProductPosition(): void {
		$this->renderSelect( 'darven_epi_others_product_position' );
	}

	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return LegacySettingsSync::save( 'positions', array() );
		}

		$sanitized_values = array();
		$allowed_values   = array_keys( $this->getItems() );

		foreach ( array(
			'darven_epi_others_product_position',
			'darven_epi_single_product_position',
			'darven_epi_catalog_product_position',
		) as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = sanitize_text_field( $input[ $field ] );

				$sanitized_values[ $field ] = in_array( $value, $allowed_values, true ) ? $value : 'first';
			}
		}

		return LegacySettingsSync::save( 'positions', $sanitized_values );
	}

	private function registerSettingsFields(): void {
		$this->registerSettingsField(
			'darven_epi_single_product_position',
			__( 'Position in single product page', 'darven-epi' ),
			array( $this, 'renderSingleProductPosition' )
		);
		$this->registerSettingsField(
			'darven_epi_catalog_product_position',
			__( 'Position in catalog page', 'darven-epi' ),
			array( $this, 'renderCatalogProductPosition' )
		);
		$this->registerSettingsField(
			'darven_epi_others_product_position',
			__( 'Position in other pages', 'darven-epi' ),
			array( $this, 'renderOtherProductPosition' )
		);
	}

	private function registerSettingsField( string $id, string $label, array $callback ): void {
		add_settings_field(
			$id,
			$label,
			$callback,
			'darven-epi-admin',
			'darven_epi_incash_settings_section'
		);
	}

	private function renderSelect( string $field ): void {
		printf(
			' <label for="%1$s"></label><select id="%1$s" name="darven_epi_option_positions[%1$s]">',
			esc_attr( $field )
		);
		foreach ( $this->getItems() as $key => $value ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $key ),
				selected( $this->options[ $field ] ?? null, $key, false ),
				esc_html( $value )
			);
		}
		echo '</select>';
	}

	/**
	 * @return array<string,string>
	 */
	private function getItems(): array {
		return array(
			'first'  => __( 'Original Price, In Cash Price, Installments Price', 'darven-epi' ),
			'second' => __( 'Original Price, Installments Price, In Cash price', 'darven-epi' ),
			'third'  => __( 'In Cash Price, Original Price, Installments Price', 'darven-epi' ),
			'fourth' => __( 'In Cash Price, Installments Price, Original Price', 'darven-epi' ),
			'fifth'  => __( 'Installments Price, Original Price, In Cash Price', 'darven-epi' ),
			'sixth'  => __( 'Installments Price, In Cash Price, Original Price', 'darven-epi' ),
		);
	}
}
