<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\YithDynamicPricingMode;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsSanitizer;

final class CompatibilityFields {
	public function register(): void {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_compatibility', array( $this, 'sanitize' ) );

		add_settings_field(
			YithDynamicPricingMode::FIELD,
			__( 'YITH Dynamic Pricing', 'darven-epi' ),
			array( $this, 'renderYithDynamicCompatibility' ),
			'darven-epi-admin',
			'darven_epi_incash_settings_section'
		);
	}

	public function renderYithDynamicCompatibility(): void {
		$mode = ( new SettingsRepository( new LegacySettingsAdapter() ) )->getYithDynamicPricingMode();

		printf(
			'<select name="darven_epi_option_compatibility[%1$s]" id="%1$s"><option value="auto" %2$s>%3$s</option><option value="disabled" %4$s>%5$s</option></select><p class="description">%6$s</p>',
			esc_attr( YithDynamicPricingMode::FIELD ),
			YithDynamicPricingMode::AUTO === $mode ? 'selected' : '',
			esc_html__( 'Automatic (recommended)', 'darven-epi' ),
			YithDynamicPricingMode::DISABLED === $mode ? 'selected' : '',
			esc_html__( 'Disabled', 'darven-epi' ),
			esc_html__( 'Automatic mode uses a valid YITH price when available and otherwise uses WooCommerce pricing.', 'darven-epi' )
		);
	}

	public function sanitize( $input ): array {
		$values = is_array( $input ) ? $input : array();

		return LegacySettingsSync::save( 'compatibility', ( new SettingsSanitizer() )->sanitizeSection( 'compatibility', $values ) );
	}
}
