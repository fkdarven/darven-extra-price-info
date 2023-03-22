<?php
if(!class_exists('Darven_Epi_Colorsandstyles_Settings_Fields')){
	class Darven_Epi_Colorsandstyles_Settings_Fields{

		private $darven_epi_options;

		public function __construct() {
			register_setting(
				'darven_epi_option_group',
				'darven_epi_option_' . 'colorsandstyles',
				array( $this, 'darven_epi_sanitize' )
			);
			$this->darven_epi_options = get_option( 'darven_epi_option_' . 'colorsandstyles' );
			$this->darven_epi_colorsandstyles_settings();
		}

		public function darven_epi_colorsandstyles_settings(): void {

			add_settings_field(
				'darven_epi_color_of_incash_price', 
				__('Price Color'),
				array( $this, 'darven_epi_color_of_incash_price_callback' ), 
				'darven-epi-admin', 
				'darven_epi_incash_settings_section' 
			);


			add_settings_field(
				'darven_epi_color_of_incash_suffix', 
				__('Suffix Color'),
				array( $this, 'darven_epi_color_of_incash_suffix_callback' ), 
				'darven-epi-admin', 
				'darven_epi_incash_settings_section' 
			);

			add_settings_field(
				'darven_epi_color_of_incash_prefix', 
				__('Prefix Color'),
				array( $this, 'darven_epi_color_of_incash_prefix_callback' ), 
				'darven-epi-admin', 
				'darven_epi_incash_settings_section' 
			);

			add_settings_field(
				'darven_epi_color_of_installments_price', 
				__('Price Color'),
				array( $this, 'darven_epi_color_of_installments_price_callback' ), 
				'darven-epi-admin', 
				'darven_epi_installments_settings_section' 
			);

			add_settings_field(
				'darven_epi_color_of_installments_install',
				__('Installment Color'),
				array( $this, 'darven_epi_color_of_installments_install_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);

			add_settings_field(
				'darven_epi_color_of_installments_suffix',
				__('Suffix Color'),
				array( $this, 'darven_epi_color_of_installments_suffix_callback' ), 
				'darven-epi-admin', 
				'darven_epi_installments_settings_section' 
			);

			add_settings_field(
				'darven_epi_color_of_installments_prefix',
				__('Prefix Color'),
				array( $this, 'darven_epi_color_of_installments_prefix_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);

		}


		public function darven_epi_color_of_incash_price_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_price]" id="darven_epi_color_of_incash_price" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_price'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_price'] ) : ''
			);
		}

		public function darven_epi_color_of_incash_suffix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_suffix]" id="darven_epi_color_of_incash_suffix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_suffix'] ) : ''
			);
		}

		public function darven_epi_color_of_incash_prefix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_prefix]" id="darven_epi_color_of_incash_prefix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_prefix'] ) : ''
			);
		}

		public function darven_epi_color_of_installments_price_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_price]" id="darven_epi_color_of_installments_price" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_price'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_price'] ) : ''
			);
		}

		public function darven_epi_color_of_installments_install_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_install]" id="darven_epi_color_of_installments_install" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_install'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_install'] ) : ''
			);
		}

		public function darven_epi_color_of_installments_suffix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_suffix]" id="darven_epi_color_of_installments_suffix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_suffix'] ) : ''
			);
		}

		public function darven_epi_color_of_installments_prefix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_prefix]" id="darven_epi_color_of_installments_prefix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_prefix'] ) : ''
			);
		}



		public function darven_epi_sanitize( $input ): array {
			$sanitary_values = array();

			if ( isset( $input['darven_epi_color_of_incash_price'] ) ) {
				$sanitary_values['darven_epi_color_of_incash_price'] = sanitize_text_field( $input['darven_epi_color_of_incash_price'] );
			}

			if ( isset( $input['darven_epi_color_of_incash_suffix'] ) ) {
				$sanitary_values['darven_epi_color_of_incash_suffix'] = sanitize_text_field( $input['darven_epi_color_of_incash_suffix'] );
			}

			if ( isset( $input['darven_epi_color_of_incash_prefix'] ) ) {
				$sanitary_values['darven_epi_color_of_incash_prefix'] = sanitize_text_field( $input['darven_epi_color_of_incash_prefix'] );
			}

			if ( isset( $input['darven_epi_color_of_installments_price'] ) ) {
				$sanitary_values['darven_epi_color_of_installments_price'] = sanitize_text_field( $input['darven_epi_color_of_installments_price'] );
			}

			if ( isset( $input['darven_epi_color_of_installments_suffix'] ) ) {
				$sanitary_values['darven_epi_color_of_installments_suffix'] = sanitize_text_field( $input['darven_epi_color_of_installments_suffix'] );
			}

			if ( isset( $input['darven_epi_color_of_installments_prefix'] ) ) {
				$sanitary_values['darven_epi_color_of_installments_prefix'] = sanitize_text_field( $input['darven_epi_color_of_installments_prefix'] );
			}
			if ( isset( $input['darven_epi_color_of_installments_install'] ) ) {
				$sanitary_values['darven_epi_color_of_installments_install'] = sanitize_text_field( $input['darven_epi_color_of_installments_install'] );
			}

			return $sanitary_values;
		}

	}
}