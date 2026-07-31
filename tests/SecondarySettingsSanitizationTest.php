<?php

use Darven\ExtraPriceInfo\Repositories\SettingsSanitizer;
use PHPUnit\Framework\TestCase;

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

final class SecondarySettingsSanitizationTest extends TestCase {
	public function test_position_settings_only_keep_supported_ordination_values(): void {
		$subject = new SettingsSanitizer();

		$result = $subject->sanitizeSection(
			'positions', array(
				'darven_epi_single_product_position'  => 'sixth',
				'darven_epi_catalog_product_position' => 'ninth<script>alert(1)</script>',
				'darven_epi_others_product_position'  => '<b>second</b>',
				'darven_epi_unknown_position'         => 'third',
			)
		);

		self::assertSame( 'sixth', $result['darven_epi_single_product_position'] );
		self::assertSame( 'first', $result['darven_epi_catalog_product_position'] );
		self::assertSame( 'second', $result['darven_epi_others_product_position'] );
		self::assertArrayNotHasKey( 'darven_epi_unknown_position', $result );
	}

	public function test_compatibility_mode_saves_auto_and_mirrors_the_legacy_checkbox(): void {
		$result = ( new SettingsSanitizer() )->sanitizeSection(
			'compatibility', array( 'darven_epi_yith_dynamic_pricing_mode' => 'auto' )
		);

		self::assertSame( 'auto', $result['darven_epi_yith_dynamic_pricing_mode'] );
		self::assertSame(
			'darven_epi_is_yith_dynamic_compatibility_enabled',
			$result['darven_epi_is_yith_dynamic_compatibility_enabled']
		);
	}

	public function test_compatibility_mode_saves_disabled_without_the_legacy_checkbox(): void {
		$result = ( new SettingsSanitizer() )->sanitizeSection(
			'compatibility', array( 'darven_epi_yith_dynamic_pricing_mode' => 'disabled' )
		);

		self::assertSame( 'disabled', $result['darven_epi_yith_dynamic_pricing_mode'] );
		self::assertArrayNotHasKey( 'darven_epi_is_yith_dynamic_compatibility_enabled', $result );
	}

	public function test_color_and_style_settings_validate_hex_colors_and_font_size_options(): void {
		$subject = new SettingsSanitizer();

		$result = $subject->sanitizeSection(
			'display', array(
				'darven_epi_color_of_incash_price'              => '#abcDEF',
				'darven_epi_color_of_installments_price'        => 'red<script>alert(1)</script>',
				'darven_epi_font_size_of_incash_price'          => '<b>1.2</b>',
				'darven_epi_font_size_of_installments_price'    => '3.0',
				'darven_epi_font_size_of_installments_prefix'   => '1.7',
				'darven_epi_color_of_installments_install'      => '#12345g',
				'darven_epi_unknown_color_or_style_option'      => '#000000',
			)
		);

		self::assertSame( '#abcdef', $result['darven_epi_color_of_incash_price'] );
		self::assertSame( '', $result['darven_epi_color_of_installments_price'] );
		self::assertSame( '1.2', $result['darven_epi_font_size_of_incash_price'] );
		self::assertSame( '1.0', $result['darven_epi_font_size_of_installments_price'] );
		self::assertSame( '1.7', $result['darven_epi_font_size_of_installments_prefix'] );
		self::assertSame( '', $result['darven_epi_color_of_installments_install'] );
		self::assertArrayNotHasKey( 'darven_epi_unknown_color_or_style_option', $result );
	}

	public function test_react_fields_match_every_key_accepted_by_the_settings_sanitizer(): void {
		$contract_file = DARVEN_EPI_DIR_PATH . 'admin/src/shared/settings-fields.json';

		self::assertFileExists( $contract_file );
		$contract  = json_decode( (string) file_get_contents( $contract_file ), true, 512, JSON_THROW_ON_ERROR );
		$sanitizer = new SettingsSanitizer();
		$valid_values = array(
			'general' => array_fill_keys( $contract['general'], '1' ),
			'display' => array_fill_keys( $contract['display'], '1.0' ),
			'positions' => array_fill_keys( $contract['positions'], 'first' ),
			'compatibility' => array_fill_keys( $contract['compatibility'], 'disabled' ),
		);
		$valid_values['general']['darven_epi_incash_is_enabled'] = 'darven_epi_incash_is_enabled';
		$valid_values['general']['darven_epi_installments_is_enabled'] = 'darven_epi_installments_is_enabled';
		$valid_values['general']['darven_epi_installments_interest_fee_is_table_enabled'] = 'darven_epi_installments_interest_fee_is_table_enabled';
		$valid_values['general']['darven_epi_type_of_discount'] = 'percent';
		$valid_values['general']['darven_epi_mode_of_view'] = 'default';
		foreach ( $contract['display'] as $field ) {
			if ( false !== strpos( $field, '_color_' ) ) {
				$valid_values['display'][ $field ] = '#123456';
			}
		}

		foreach ( array( 'general', 'display', 'positions', 'compatibility' ) as $section ) {
			self::assertSame(
				$contract[ $section ], array_keys( $sanitizer->sanitizeSection( $section, $valid_values[ $section ] ) ), $section . ' field contract drifted.'
			);
		}
	}
}
