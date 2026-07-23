<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;

final class DisplayFields {
	/**
	 * @var array<string,string>
	 */
	private $options = array();

	/**
	 * @var array<string,string>
	 */
	private $font_sizes = array(
		'1.0' => '100%',
		'1.1' => '110%',
		'1.2' => '120%',
		'1.3' => '130%',
		'1.4' => '140%',
		'1.5' => '150%',
		'1.6' => '160%',
		'1.7' => '170%',
		'1.8' => '180%',
		'1.9' => '190%',
		'2.0' => '200%',
	);

	public function register(): void {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_colorsandstyles', array( $this, 'sanitize' ) );
		$options       = get_option( 'darven_epi_option_colorsandstyles' );
		$this->options = is_array( $options ) ? $options : array();

		$this->registerFields();
	}

	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return LegacySettingsSync::save( 'display', array() );
		}

		$sanitized_values = array();
		foreach ( $this->getColorFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = strtolower( sanitize_text_field( $input[ $field ] ) );

				$sanitized_values[ $field ] = preg_match( '/^#(?:[0-9a-f]{3}){1,2}$/', $value ) ? $value : '';
			}
		}

		$allowed_font_sizes = array_keys( $this->font_sizes );
		foreach ( $this->getFontSizeFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = sanitize_text_field( $input[ $field ] );

				$sanitized_values[ $field ] = in_array( $value, $allowed_font_sizes, true ) ? $value : '1.0';
			}
		}

		return LegacySettingsSync::save( 'display', $sanitized_values );
	}

	private function registerFields(): void {
		$this->registerColorField( 'darven_epi_color_of_incash_price', 'Price Color', 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_price', 'Color Font Size', 'darven_epi_incash_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_incash_suffix', 'Suffix Color', 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_suffix', 'Suffix Font Size', 'darven_epi_incash_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_incash_prefix', 'Prefix Color', 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_prefix', 'Prefix Font Size', 'darven_epi_incash_settings_section' );

		$this->registerColorField( 'darven_epi_color_of_installments_price', 'Price Color', 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_price', 'Color Font Size', 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_suffix', 'Suffix Color', 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_suffix', 'Suffix Font Size', 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_prefix', 'Prefix Color', 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_prefix', 'Prefix Font Size', 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_install', 'Installment Color', 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_install', 'Installment Font Size', 'darven_epi_installments_settings_section' );
	}

	private function registerColorField( string $field, string $label, string $section ): void {
		add_settings_field(
			$field,
			__( $label, 'darven-epi' ),
			function () use ( $field ): void {
				printf(
					'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[%1$s]" id="%1$s" value="%2$s">',
					$field,
					isset( $this->options[ $field ] ) ? esc_attr( $this->options[ $field ] ) : ''
				);
			},
			'darven-epi-admin',
			$section
		);
	}

	private function registerFontSizeField( string $field, string $label, string $section ): void {
		add_settings_field(
			$field,
			__( $label, 'darven-epi' ),
			function () use ( $field ): void {
				echo "<label for='" . $field . "'></label><select id='" . $field . "' name='darven_epi_option_colorsandstyles[" . $field . "]'>";
				foreach ( $this->font_sizes as $key => $value ) {
					echo "<option value='" . $key . "' "
						. selected( $this->options[ $field ] ?? null, $key, false ) . '>'
						. esc_html( $value )
						. '</option>';
				}
				echo '</select>';
			},
			'darven-epi-admin',
			$section
		);
	}

	/**
	 * @return array<int,string>
	 */
	private function getColorFields(): array {
		return array(
			'darven_epi_color_of_installments_install',
			'darven_epi_color_of_installments_prefix',
			'darven_epi_color_of_installments_suffix',
			'darven_epi_color_of_installments_price',
			'darven_epi_color_of_incash_prefix',
			'darven_epi_color_of_incash_suffix',
			'darven_epi_color_of_incash_price',
		);
	}

	/**
	 * @return array<int,string>
	 */
	private function getFontSizeFields(): array {
		return array(
			'darven_epi_font_size_of_incash_price',
			'darven_epi_font_size_of_incash_suffix',
			'darven_epi_font_size_of_incash_prefix',
			'darven_epi_font_size_of_installments_price',
			'darven_epi_font_size_of_installments_suffix',
			'darven_epi_font_size_of_installments_prefix',
			'darven_epi_font_size_of_installments_install',
		);
	}
}
