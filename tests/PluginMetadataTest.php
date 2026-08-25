<?php

use PHPUnit\Framework\TestCase;

final class PluginMetadataTest extends TestCase {
	public function test_uses_the_darven_precos_parcelados_public_name_without_changing_the_technical_slug(): void {
		$plugin_headers = file_get_contents(
			DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php',
			false,
			null,
			0,
			8192
		);
		$readme         = file_get_contents( DARVEN_EPI_DIR_PATH . 'readme.txt' );

		self::assertIsString( $plugin_headers );
		self::assertIsString( $readme );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Plugin Name:\s*Darven Preços Parcelados\s*$/mi', $plugin_headers );
		self::assertStringStartsWith( "=== Darven Preços Parcelados ===\n", str_replace( "\r\n", "\n", $readme ) );
		self::assertStringContainsString( 'Anteriormente: Darven Múltiplos Preços Informativos', $readme );
		self::assertFileExists( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		self::assertStringContainsString( "const DARVEN_EPI_RELEASE_SLUG = 'darven-extra-price-info';", file_get_contents( DARVEN_EPI_DIR_PATH . 'scripts/build-release.php' ) );
	}

	public function test_uses_the_public_name_in_the_woocommerce_admin_menu(): void {
		$GLOBALS['darven_epi_test_submenu_pages'] = array();

		$page = new \Darven\ExtraPriceInfo\Admin\ReactPage();
		$page->addMenuPage();

		self::assertCount( 1, $GLOBALS['darven_epi_test_submenu_pages'] );
		self::assertSame( 'Darven Preços Parcelados', $GLOBALS['darven_epi_test_submenu_pages'][0]['page_title'] );
		self::assertSame( 'Darven Preços Parcelados', $GLOBALS['darven_epi_test_submenu_pages'][0]['menu_title'] );
		self::assertSame( 'darven-epi-admin', $GLOBALS['darven_epi_test_submenu_pages'][0]['menu_slug'] );
	}

	public function test_declares_runtime_and_plugin_requirements(): void {
		$plugin_headers = file_get_contents(
			DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php',
			false,
			null,
			0,
			8192
		);

		self::assertIsString( $plugin_headers );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Requires at least:\s*5\.0\s*$/mi', $plugin_headers );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Requires PHP:\s*8\.0\s*$/mi', $plugin_headers );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Requires Plugins:\s*woocommerce\s*$/mi', $plugin_headers );
	}
}
