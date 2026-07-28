<?php

use Darven\ExtraPriceInfo\Admin\SettingsFields\CompatibilityFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\DisplayFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\PositionsFields;
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
	protected function tearDown(): void {
		do_action( 'shutdown' );
	}

	public function test_position_settings_only_keep_supported_ordination_values(): void {
		$subject = $this->get_subject( PositionsFields::class );

		$result = $subject->sanitize(
			array(
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
		$result = $this->get_subject( CompatibilityFields::class )->sanitize(
			array( 'darven_epi_yith_dynamic_pricing_mode' => 'auto' )
		);

		self::assertSame( 'auto', $result['darven_epi_yith_dynamic_pricing_mode'] );
		self::assertSame(
			'darven_epi_is_yith_dynamic_compatibility_enabled',
			$result['darven_epi_is_yith_dynamic_compatibility_enabled']
		);
	}

	public function test_compatibility_mode_saves_disabled_without_the_legacy_checkbox(): void {
		$result = $this->get_subject( CompatibilityFields::class )->sanitize(
			array( 'darven_epi_yith_dynamic_pricing_mode' => 'disabled' )
		);

		self::assertSame( 'disabled', $result['darven_epi_yith_dynamic_pricing_mode'] );
		self::assertArrayNotHasKey( 'darven_epi_is_yith_dynamic_compatibility_enabled', $result );
	}

	public function test_compatibility_mode_renders_automatic_as_the_default(): void {
		$GLOBALS['darven_epi_test_options'] = array();

		ob_start();
		$this->get_subject( CompatibilityFields::class )->renderYithDynamicCompatibility();
		$output = ob_get_clean();

		self::assertStringContainsString( 'name="darven_epi_option_compatibility[darven_epi_yith_dynamic_pricing_mode]"', $output );
		self::assertStringContainsString( 'Automatic (recommended)', $output );
		self::assertStringContainsString( 'Disabled', $output );
		self::assertStringContainsString( 'value="auto" selected', $output );
	}

	public function test_color_and_style_settings_validate_hex_colors_and_font_size_options(): void {
		$subject = $this->get_subject( DisplayFields::class );

		$result = $subject->sanitize(
			array(
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

	private function get_subject( string $class_name ) {
		return new $class_name();
	}
}
