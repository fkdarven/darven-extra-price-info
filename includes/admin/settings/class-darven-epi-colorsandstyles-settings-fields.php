<?php
/*
 *
 * Class responsible for generating all settings associated with colors and styles
 */
if ( ! class_exists( 'Darven_Epi_Colorsandstyles_Settings_Fields' ) ) {
	class Darven_Epi_Colorsandstyles_Settings_Fields {

		private $darven_epi_options;
		private array $items = [
			'1.0' => "100%",
			'1.1' => "110%",
			'1.2' => "120%",
			'1.3' => "130%",
			'1.4' => "140%",
			'1.5' => "150%",
			'1.6' => "160%",
			'1.7' => "170%",
			'1.8' => "180%",
			'1.9' => "190%",
			'2.0' => "200%"
		];

		public function __construct() {
			register_setting(
				'darven_epi_option_group',
				'darven_epi_option_' . 'colorsandstyles',
				array( $this, 'darven_epi_sanitize' )
			);
			$this->darven_epi_options = get_option( 'darven_epi_option_' . 'colorsandstyles' );
			$this->darven_epi_colorsandstyles_settings();
		}

		private function register_settings_field( $id, $text, $section ): void {
			add_settings_field(
				$id,
				__( $text, DARVEN_EPI_LANGUAGE_DOMAIN ),
				[ $this, $id . '_callback' ],
				DARVEN_EPI_ADMIN_PAGE,
				$section );

		}

		public function darven_epi_colorsandstyles_settings(): void {

			// In Cash Section
			$this->register_settings_field( 'darven_epi_color_of_incash_price', 'Price Color', 'darven_epi_incash_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_incash_price', 'Color Font Size', 'darven_epi_incash_settings_section' );
			$this->register_settings_field( 'darven_epi_color_of_incash_suffix', 'Suffix Color', 'darven_epi_incash_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_incash_suffix', 'Suffix Font Size', 'darven_epi_incash_settings_section' );
			$this->register_settings_field( 'darven_epi_color_of_incash_prefix', 'Prefix Color', 'darven_epi_incash_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_incash_prefix', 'Prefix Font Size', 'darven_epi_incash_settings_section' );

			//Installments Section
			$this->register_settings_field( 'darven_epi_color_of_installments_price', 'Price Color', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_installments_price', 'Color Font Size', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_color_of_installments_suffix', 'Suffix Color', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_installments_suffix', 'Suffix Font Size', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_color_of_installments_prefix', 'Prefix Color', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_installments_prefix', 'Prefix Font Size', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_color_of_installments_install', 'Installment Color', 'darven_epi_installments_settings_section' );
			$this->register_settings_field( 'darven_epi_font_size_of_installments_install', 'Installment Font Size', 'darven_epi_installments_settings_section' );


			/*add_settings_field(
				'darven_epi_color_of_incash_price',
				__( 'Price Color' ),
				array( $this, 'darven_epi_color_of_incash_price_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'
			);
			add_settings_field( 'darven_epi_font_size_of_incash_price',
				__( 'Price font size' ),
				array( $this, 'darven_epi_font_size_of_incash_price_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'
			);
			add_settings_field(
				'darven_epi_color_of_incash_suffix',
				__( 'Suffix Color' ),
				array( $this, 'darven_epi_color_of_incash_suffix_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'
			);
			add_settings_field( 'darven_epi_font_size_of_incash_suffix',
				__( 'Suffix font size' ),
				array( $this, 'darven_epi_font_size_of_incash_suffix_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'
			);
			add_settings_field(
				'darven_epi_color_of_incash_prefix',
				__( 'Prefix Color' ),
				array( $this, 'darven_epi_color_of_incash_prefix_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'
			);
			add_settings_field( 'darven_epi_font_size_of_incash_prefix',
				__( 'Prefix font size' ),
				array( $this, 'darven_epi_font_size_of_incash_prefix_callback' ),
				'darven-epi-admin',
				'darven_epi_incash_settings_section'

			);
			add_settings_field(
				'darven_epi_color_of_installments_price',
				__( 'Price Color' ),
				array( $this, 'darven_epi_color_of_installments_price_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);
			add_settings_field(
				'darven_epi_color_of_installments_install',
				__( 'Installment Color' ),
				array( $this, 'darven_epi_color_of_installments_install_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);
			add_settings_field(
				'darven_epi_color_of_installments_suffix',
				__( 'Suffix Color' ),
				array( $this, 'darven_epi_color_of_installments_suffix_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);
			add_settings_field(
				'darven_epi_color_of_installments_prefix',
				__( 'Prefix Color' ),
				array( $this, 'darven_epi_color_of_installments_prefix_callback' ),
				'darven-epi-admin',
				'darven_epi_installments_settings_section'
			);
			*/
		}

		public function darven_epi_color_of_incash_price_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_price]" id="darven_epi_color_of_incash_price" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_price'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_price'] ) : ''
			);
		}

		public function darven_epi_font_size_of_incash_price_callback(): void {

			echo "<select id='darven_epi_font_size_of_incash_price' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_incash_price]'>";
			foreach ( $this->items as $key => $value ) {

				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_incash_price'], $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";

		}

		public function darven_epi_color_of_incash_suffix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_suffix]" id="darven_epi_color_of_incash_suffix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_suffix'] ) : ''
			);
		}

		public function darven_epi_font_size_of_incash_suffix_callback(): void {

			echo "<label for='darven_epi_font_size_of_incash_suffix'></label><select id='darven_epi_font_size_of_incash_suffix' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_incash_suffix]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_incash_suffix'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";
		}

		public function darven_epi_color_of_incash_prefix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_incash_prefix]" id="darven_epi_color_of_incash_prefix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_incash_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_incash_prefix'] ) : ''
			);
		}

		public function darven_epi_font_size_of_incash_prefix_callback(): void {


			echo "<select id='darven_epi_font_size_of_incash_prefix' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_incash_prefix]'>";

			foreach ( $this->items as $key => $value ) {

				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_incash_prefix'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";

			}

			echo "</select>";
		}

		public function darven_epi_color_of_installments_price_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_price]" id="darven_epi_color_of_installments_price" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_price'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_price'] ) : ''
			);
		}
		public function darven_epi_font_size_of_installments_price_callback(): void {

			echo "<label for='darven_epi_font_size_of_installments_price'></label><select id='darven_epi_font_size_of_installments_price' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_installments_price]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_installments_price'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";
		}

		public function darven_epi_color_of_installments_install_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_install]" id="darven_epi_color_of_installments_install" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_install'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_install'] ) : ''
			);
		}
		public function darven_epi_font_size_of_installments_install_callback(): void {

			echo "<label for='darven_epi_font_size_of_installments_install'></label><select id='darven_epi_font_size_of_installments_install' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_installments_install]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_installments_install'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";
		}

		public function darven_epi_color_of_installments_suffix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_suffix]" id="darven_epi_color_of_installments_suffix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_suffix'] ) : ''
			);
		}
		public function darven_epi_font_size_of_installments_suffix_callback(): void {

			echo "<label for='darven_epi_font_size_of_installments_suffix'></label><select id='darven_epi_font_size_of_installments_suffix' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_installments_suffix]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_installments_suffix'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";
		}

		public function darven_epi_color_of_installments_prefix_callback(): void {
			printf(
				'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[darven_epi_color_of_installments_prefix]" id="darven_epi_color_of_installments_prefix" value="%s">',
				isset( $this->darven_epi_options['darven_epi_color_of_installments_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_color_of_installments_prefix'] ) : ''
			);
		}
		
		public function darven_epi_font_size_of_installments_prefix_callback(): void {

			echo "<label for='darven_epi_font_size_of_installments_prefix'></label><select id='darven_epi_font_size_of_installments_prefix' name='darven_epi_option_colorsandstyles[darven_epi_font_size_of_installments_prefix]'>";
			foreach ( $this->items as $key => $value ) {
				echo "<option value='" . $key . "' "
				     . selected( $this->darven_epi_options['darven_epi_font_size_of_installments_prefix'] ?? null, $key, false ) . ">" . esc_html( $value ) .
				     "</option>";
			}
			echo "</select>";
		}
		


		public function darven_epi_sanitize( $input ): array {
			$sanitary_values  = [
				'darven_epi_color_of_installments_install',
				'darven_epi_color_of_installments_prefix',
				'darven_epi_color_of_installments_suffix',
				'darven_epi_color_of_installments_price',
				'darven_epi_color_of_incash_prefix',
				'darven_epi_color_of_incash_suffix',
				'darven_epi_color_of_incash_price',
				'darven_epi_font_size_of_incash_price',
				'darven_epi_font_size_of_incash_suffix',
				'darven_epi_font_size_of_incash_prefix',
				'darven_epi_font_size_of_installments_price',
				'darven_epi_font_size_of_installments_suffix',
				'darven_epi_font_size_of_installments_prefix',
				'darven_epi_font_size_of_installments_install',
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