<?php

defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'Darven_Epi_Compatibility_Settings_Fields' ) ) {
	class Darven_Epi_Compatibility_Settings_Fields {
		private $darven_epi_options;

		public function __construct() {
			register_setting( 'darven_epi_option_group', 'darven_epi_option_' . 'compatibility', array(
				$this,
				'darven_epi_sanitize'
			) );

			$this->darven_epi_options = get_option( 'darven_epi_option_compatibility' );
			$this->darven_epi_compatibility_settings();
		}

		private function register_settings_field( $id, $text, $section ): void {
			add_settings_field(
				$id,
				__( $text, DARVEN_EPI_LANGUAGE_DOMAIN ),
				[ $this, $id . '_callback' ],
				DARVEN_EPI_ADMIN_PAGE,
				$section );
		}

		private function darven_epi_compatibility_settings(): void {
			$this->register_settings_field( 'darven_epi_is_yith_dynamic_compatibility_enabled', 'Enable YITH Compatibility', 'darven_epi_incash_settings_section' );
			//$this->register_settings_field( 'darven_epi_is_enabled_for_all_products', 'Enable for all products', 'darven_epi_compatibility_settings_section' );
		}
		public function darven_epi_is_yith_dynamic_compatibility_enabled_callback(): void{
			printf( '<input type="checkbox" name="darven_epi_option_compatibility[darven_epi_is_yith_dynamic_compatibility_enabled]" id="darven_epi_is_yith_dynamic_compatibility_enabled" value="darven_epi_is_yith_dynamic_compatibility_enabled" %s><p class="description">%s</p>', ( isset( $this->darven_epi_options['darven_epi_is_yith_dynamic_compatibility_enabled'] ) && $this->darven_epi_options['darven_epi_is_yith_dynamic_compatibility_enabled'] === 'darven_epi_is_yith_dynamic_compatibility_enabled' ) ? 'checked' : '', esc_attr( __( 'If enabled, the plugin will consider the price defined by YITH WooCommerce Dynamic Pricing and Discounts!', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}
		public function darven_epi_sanitize( $input ): array {
			$sanitary_values = [
				'darven_epi_is_yith_dynamic_compatibility_enabled'
			];
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