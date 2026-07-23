<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;

final class CompatibilityFields {
	/**
	 * @var array<string,string>
	 */
	private $options = array();

	public function register(): void {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_compatibility', array( $this, 'sanitize' ) );
		$options       = get_option( 'darven_epi_option_compatibility' );
		$this->options = is_array( $options ) ? $options : array();

		add_settings_field(
			'darven_epi_is_yith_dynamic_compatibility_enabled',
			__( 'Enable YITH Compatibility', 'darven-epi' ),
			array( $this, 'renderYithDynamicCompatibility' ),
			'darven-epi-admin',
			'darven_epi_incash_settings_section'
		);
	}

	public function renderYithDynamicCompatibility(): void {
		$field   = 'darven_epi_is_yith_dynamic_compatibility_enabled';
		$checked = isset( $this->options[ $field ] ) && $field === $this->options[ $field ] ? 'checked' : '';

		printf(
			'<input type="checkbox" name="darven_epi_option_compatibility[%1$s]" id="%1$s" value="%1$s" %2$s><p class="description">%3$s</p>',
			$field,
			$checked,
			esc_attr(
				__(
					'If enabled, the plugin will consider the price defined by YITH WooCommerce Dynamic Pricing and Discounts!',
					'darven-epi'
				)
			)
		);
	}

	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return LegacySettingsSync::save( 'compatibility', array() );
		}

		$field            = 'darven_epi_is_yith_dynamic_compatibility_enabled';
		$sanitized_values = array();
		if ( array_key_exists( $field, $input ) && $field === sanitize_text_field( $input[ $field ] ) ) {
			$sanitized_values[ $field ] = $field;
		}

		return LegacySettingsSync::save( 'compatibility', $sanitized_values );
	}
}
