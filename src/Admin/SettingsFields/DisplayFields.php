<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;
use Darven\ExtraPriceInfo\Repositories\SettingsSanitizer;

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
		$values = is_array( $input ) ? $input : array();

		return LegacySettingsSync::save( 'display', ( new SettingsSanitizer() )->sanitizeSection( 'display', $values ) );
	}

	private function registerFields(): void {
		$this->registerColorField( 'darven_epi_color_of_incash_price', __( 'Price Color', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_price', __( 'Color Font Size', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_incash_suffix', __( 'Suffix Color', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_suffix', __( 'Suffix Font Size', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_incash_prefix', __( 'Prefix Color', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_incash_prefix', __( 'Prefix Font Size', 'darven-epi' ), 'darven_epi_incash_settings_section' );

		$this->registerColorField( 'darven_epi_color_of_installments_price', __( 'Price Color', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_price', __( 'Color Font Size', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_suffix', __( 'Suffix Color', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_suffix', __( 'Suffix Font Size', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_prefix', __( 'Prefix Color', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_prefix', __( 'Prefix Font Size', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerColorField( 'darven_epi_color_of_installments_install', __( 'Installment Color', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerFontSizeField( 'darven_epi_font_size_of_installments_install', __( 'Installment Font Size', 'darven-epi' ), 'darven_epi_installments_settings_section' );
	}

	private function registerColorField( string $field, string $label, string $section ): void {
		add_settings_field(
			$field,
			$label,
			function () use ( $field ): void {
				printf(
					'<input class="regular" type="color" name="darven_epi_option_colorsandstyles[%1$s]" id="%1$s" value="%2$s">',
					esc_attr( $field ),
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
			$label,
			function () use ( $field ): void {
				printf(
					'<label for="%1$s"></label><select id="%1$s" name="darven_epi_option_colorsandstyles[%1$s]">',
					esc_attr( $field )
				);
				foreach ( $this->font_sizes as $key => $value ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $key ),
						selected( $this->options[ $field ] ?? null, $key, false ),
						esc_html( $value )
					);
				}
				echo '</select>';
			},
			'darven-epi-admin',
			$section
		);
	}
}
