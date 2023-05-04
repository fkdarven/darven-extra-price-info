<?php

defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'Darven_Epi_Positions_Fields' ) ) {
	class Darven_Epi_Positions_Fields {
		private $darven_epi_options;
		private array $items = [
			'first'  => 'Original Price, In Cash Price, Installments Price',
			'second' => 'Original Price, Installments Price, In Cash price',
			'third'  => 'In Cash Price, Original Price, Installments Price',
			'fourth' => 'In Cash Price, Installments Price, Original Price',
			'fifth'  => 'Installments Price, Original Price, In Cash Price',
			'sixth'  => 'Installments Price, In Cash Price, Original Price'
		];

		private function register_settings_field( $id, $text, $section ): void {
			add_settings_field(
				$id,
				__( $text , DARVEN_EPI_LANGUAGE_DOMAIN),
				[ $this, $id . '_callback' ],
				DARVEN_EPI_ADMIN_PAGE,
				$section );

		}

		public function __construct() {

			register_setting( 'darven_epi_option_group',
				'darven_epi_option_positions',
				array( $this, 'darven_epi_sanitize' ) );
			$this->darven_epi_options = get_option( 'darven_epi_option_positions' );
			$this->darven_epi_positions_settings();
		}

		private function darven_epi_positions_settings(): void {
			$this->register_settings_field('darven_epi_single_product_position', 'Position in single product page', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_catalog_product_position', 'Position in catalog page', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_others_product_position', 'Position in other pages', 'darven_epi_incash_settings_section');
		}

		public function darven_epi_single_product_position_callback(): void {

			echo " <label for='darven_epi_single_product_position'></label><select id='darven_epi_single_product_position' name='darven_epi_option_positions[darven_epi_single_product_position]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_single_product_position'], $key, false ) . ">" . esc_html( __( $value, DARVEN_EPI_LANGUAGE_DOMAIN ) ) .
				     "</option>";
			}
			echo "</select>";
		}

		public function darven_epi_others_product_position_callback(): void {
			echo " <label for='darven_epi_others_product_position'></label><select id='darven_epi_others_product_position' name='darven_epi_option_positions[darven_epi_others_product_position]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_others_product_position'] ?? null, $key, false ) . ">" . esc_html( __( $value, DARVEN_EPI_LANGUAGE_DOMAIN ) ) .
				     "</option>";
			}
			echo "</select>";

		}
		public function darven_epi_catalog_product_position_callback(): void {

			echo " <label for='darven_epi_catalog_product_position'></label><select id='darven_epi_catalog_product_position' name='darven_epi_option_positions[darven_epi_catalog_product_position]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_catalog_product_position'] ?? null, $key, false ) . ">" . esc_html( __( $value, DARVEN_EPI_LANGUAGE_DOMAIN ) ) .
				     "</option>";

			}
			echo "</select>";

		}

		public function darven_epi_sanitize( $input ): array {
			$sanitary_values = ['darven_epi_others_product_position','darven_epi_single_product_position', 'darven_epi_catalog_product_position'];
			$sanitized_values = [];
			foreach ( $sanitary_values as $sanitary_value ) {
				if ( isset( $input[ $sanitary_value ] ) ) {
					$sanitized_values[ $sanitary_value ] = sanitize_text_field( $input[ $sanitary_value ] );
				}
			}

			return $sanitized_values;
		}
	}
}

