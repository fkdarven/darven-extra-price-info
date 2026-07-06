<?php

use PHPUnit\Framework\TestCase;

final class FrontendAssetsTest extends TestCase {
	public function test_popup_script_uses_delegated_product_scoped_selectors(): void {
		$script = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/js/frontend.js' );

		self::assertIsString( $script );
		self::assertStringNotContainsString( 'window.onload', $script );
		self::assertStringContainsString( '.darven-epi-installments-toggle', $script );
		self::assertStringContainsString( '.darven-epi-installments-price-statement', $script );
		self::assertStringContainsString( '.darven-epi-installments-popup', $script );
		self::assertStringContainsString( '.closest(', $script );
		self::assertStringContainsString( '.find(', $script );
		self::assertStringContainsString( 'aria-expanded', $script );
		self::assertStringContainsString( 'aria-hidden', $script );
	}

	public function test_frontend_styles_target_repeatable_component_classes(): void {
		$styles        = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/css/styles.css' );
		$custom_styles = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/partials/darven-epi-custom-css.php' );

		self::assertIsString( $styles );
		self::assertIsString( $custom_styles );
		self::assertStringNotContainsString( '#installments_table', $styles );
		self::assertStringContainsString( '.darven-epi-installments-table', $styles );
		self::assertStringNotContainsString( '#incash-prefix', $custom_styles );
		self::assertStringNotContainsString( '#installment-price', $custom_styles );
		self::assertStringContainsString( '.darven-epi-incash-prefix', $custom_styles );
		self::assertStringContainsString( '.darven-epi-installment-price', $custom_styles );
	}
}
