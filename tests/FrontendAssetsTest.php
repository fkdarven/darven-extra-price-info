<?php

use Darven\ExtraPriceInfo\Admin\Assets as AdminAssets;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Frontend\Assets;
use Darven\ExtraPriceInfo\Frontend\InlineStyles;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AdminAssetsDoubles.php';

final class FrontendAssetsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_enqueued_styles']  = array();
		$GLOBALS['darven_epi_test_enqueued_scripts'] = array();
		$GLOBALS['darven_epi_test_inline_styles']    = array();
		$GLOBALS['darven_epi_test_style_data']       = array();
		$GLOBALS['darven_epi_test_options']          = array();
		$GLOBALS['darven_epi_test_screen']           = null;
		$_GET = array();
	}

	public function test_enqueue_attaches_the_sanitized_display_css_to_the_public_handle(): void {
		$assets = new Assets( new InlineStyles( $this->settingsRepositoryWith( '#123456' ) ) );
		$assets->enqueue();

		self::assertSame( 'darven-epi', $GLOBALS['darven_epi_test_inline_styles'][0]['handle'] );
		self::assertStringContainsString( 'color: #123456 !important;', $GLOBALS['darven_epi_test_inline_styles'][0]['css'] );
		self::assertStringNotContainsString( '<style', $GLOBALS['darven_epi_test_inline_styles'][0]['css'] );
	}

	public function test_admin_bundles_register_rtl_replacements(): void {
		$_GET = array( 'page' => 'darven-epi-admin' );
		( new AdminAssets() )->enqueue();

		$_GET = array( 'post' => '42' );
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'post_type' => 'product', 'base' => 'post' );
		( new AdminAssets() )->enqueue();

		self::assertSame(
			array(
				array( 'handle' => 'darven-precos-parcelados-settings', 'key' => 'rtl', 'value' => 'replace' ),
				array( 'handle' => 'darven-precos-parcelados-product-options', 'key' => 'rtl', 'value' => 'replace' ),
			), $GLOBALS['darven_epi_test_style_data']
		);
	}

	public function test_frontend_script_has_no_jquery_dependency(): void {
		( new Assets( new InlineStyles( $this->settingsRepositoryWith( '#123456' ) ) ) )->enqueue();

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

	private function settingsRepositoryWith( string $incash_price_color ): SettingsRepository {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_colorsandstyles'] = array(
			'darven_epi_color_of_incash_prefix'            => '#111111',
			'darven_epi_font_size_of_incash_prefix'        => '1.0',
			'darven_epi_color_of_incash_price'             => $incash_price_color,
			'darven_epi_font_size_of_incash_price'         => '1.0',
			'darven_epi_color_of_incash_suffix'            => '#222222',
			'darven_epi_font_size_of_incash_suffix'        => '1.0',
			'darven_epi_color_of_installments_prefix'      => '#333333',
			'darven_epi_font_size_of_installments_prefix'  => '1.0',
			'darven_epi_color_of_installments_install'     => '#444444',
			'darven_epi_font_size_of_installments_install' => '1.0',
			'darven_epi_color_of_installments_price'       => '#555555',
			'darven_epi_font_size_of_installments_price'   => '1.0',
			'darven_epi_color_of_installments_suffix'      => '#666666',
			'darven_epi_font_size_of_installments_suffix'  => '1.0',
		);

		return new SettingsRepository( new LegacySettingsAdapter() );
	}
}
