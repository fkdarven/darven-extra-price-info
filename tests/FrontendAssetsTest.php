<?php

use Darven\ExtraPriceInfo\Frontend\Assets;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AdminAssetsDoubles.php';

final class FrontendAssetsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_enqueued_styles']  = array();
		$GLOBALS['darven_epi_test_enqueued_scripts'] = array();
	}

	public function test_frontend_script_has_no_jquery_dependency(): void {
		( new Assets() )->enqueue();

		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_scripts'] );
		self::assertSame( 'darven-epi', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['handle'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
	}

	public function test_popup_script_uses_delegated_product_scoped_selectors(): void {
		$script = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/js/frontend.js' );

		self::assertIsString( $script );
		self::assertStringNotContainsString( 'window.onload', $script );
		self::assertStringContainsString( '.darven-epi-installments-toggle', $script );
		self::assertStringContainsString( '.darven-epi-installments-popup', $script );
		self::assertStringContainsString( '.closest(', $script );
		self::assertStringContainsString( 'aria-expanded', $script );
		self::assertStringContainsString( 'aria-hidden', $script );
		self::assertStringNotContainsString( 'jQuery', $script );
		self::assertStringNotContainsString( '$( ', $script );
	}

	public function test_frontend_styles_target_repeatable_component_classes(): void {
		$styles        = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/css/styles.css' );
		$custom_styles = file_get_contents( DARVEN_EPI_DIR_PATH . 'public/partials/darven-epi-custom-css.php' );
		$inline_styles = file_get_contents( DARVEN_EPI_DIR_PATH . 'src/Frontend/InlineStyles.php' );

		self::assertIsString( $styles );
		self::assertIsString( $custom_styles );
		self::assertIsString( $inline_styles );
		self::assertStringNotContainsString( '#installments_table', $styles );
		self::assertStringContainsString( '.darven-epi-installments-table', $styles );
		self::assertStringContainsString( 'InlineStyles', $custom_styles );
		self::assertStringNotContainsString( '#incash-prefix', $inline_styles );
		self::assertStringNotContainsString( '#installment-price', $inline_styles );
		self::assertStringContainsString( '.darven-epi-incash-prefix', $inline_styles );
		self::assertStringContainsString( '.darven-epi-installment-price', $inline_styles );
		self::assertStringNotContainsString( "\nlabel {", $styles );
		self::assertStringContainsString( '.darven-epi-installments-backdrop', $styles );
		self::assertStringContainsString( ':focus-visible', $styles );
		self::assertStringContainsString( 'prefers-reduced-motion', $styles );
	}
}
