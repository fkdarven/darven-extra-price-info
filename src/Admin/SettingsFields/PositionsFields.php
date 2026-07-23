<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;

final class PositionsFields {
	/**
	 * @var array<string,string>
	 */
	private $options = array();

	/**
	 * @var array<string,string>
	 */
	private $items = array(
		'first'  => 'Original Price, In Cash Price, Installments Price',
		'second' => 'Original Price, Installments Price, In Cash price',
		'third'  => 'In Cash Price, Original Price, Installments Price',
		'fourth' => 'In Cash Price, Installments Price, Original Price',
		'fifth'  => 'Installments Price, Original Price, In Cash Price',
		'sixth'  => 'Installments Price, In Cash Price, Original Price',
	);

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
		$allowed_values   = array_keys( $this->items );

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
			'Position in single product page',
			array( $this, 'renderSingleProductPosition' )
		);
		$this->registerSettingsField(
			'darven_epi_catalog_product_position',
			'Position in catalog page',
			array( $this, 'renderCatalogProductPosition' )
		);
		$this->registerSettingsField(
			'darven_epi_others_product_position',
			'Position in other pages',
			array( $this, 'renderOtherProductPosition' )
		);
	}

	private function registerSettingsField( string $id, string $label, array $callback ): void {
		add_settings_field(
			$id,
			__( $label, 'darven-epi' ),
			$callback,
			'darven-epi-admin',
			'darven_epi_incash_settings_section'
		);
	}

	private function renderSelect( string $field ): void {
		echo " <label for='" . $field . "'></label><select id='" . $field . "' name='darven_epi_option_positions[" . $field . "]'>";
		foreach ( $this->items as $key => $value ) {
			echo "<option value='" . $key . "' "
				. selected( $this->options[ $field ] ?? null, $key, false ) . '>'
				. esc_html( __( $value, 'darven-epi' ) )
				. '</option>';
		}
		echo '</select>';
	}
}
