<?php

use Darven\ExtraPriceInfo\Admin\Assets;
use Darven\ExtraPriceInfo\Setup\Plugin;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AdminAssetsDoubles.php';

final class AdminAssetsTest extends TestCase {
	protected function setUp(): void {
		$_GET = array();
		$GLOBALS['darven_epi_test_enqueued_styles']  = array();
		$GLOBALS['darven_epi_test_enqueued_scripts'] = array();
		$GLOBALS['darven_epi_test_localized_scripts'] = array();
		$GLOBALS['darven_epi_test_enqueued_media'] = 0;
		$GLOBALS['darven_epi_test_screen'] = null;
		$GLOBALS['darven_epi_test_script_translations'] = array();
	}

	public function test_enqueues_the_settings_bundle_with_asset_metadata_and_rest_configuration(): void {
		$_GET = array( 'page' => 'darven-epi-admin<em></em>' );

		( new Assets() )->enqueue();

		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_styles'] );
		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_scripts'] );
		self::assertSame( 'darven-precos-parcelados-settings', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['handle'] );
		self::assertContains( 'wp-api-fetch', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertNotContains( 'wp-components', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertContains( 'wp-element', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertContains( 'wp-i18n', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_styles'][0]['deps'] );
		$asset = require DARVEN_EPI_DIR_PATH . 'build/settings/index.asset.php';
		self::assertSame( $asset['version'], $GLOBALS['darven_epi_test_enqueued_scripts'][0]['ver'] );
		self::assertSame( 'https://example.test/wp-json/darven-precos-parcelados/v1/', $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['restUrl'] );
		self::assertSame( 'test-rest-nonce', $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['nonce'] );
		self::assertSame(
			array(
				'handle' => 'darven-precos-parcelados-settings',
				'domain' => 'darven-multiplos-precos-informativos',
				'path'   => DARVEN_EPI_DIR_PATH . 'languages',
			), $GLOBALS['darven_epi_test_script_translations'][0]
		);
	}

	public function test_enqueues_the_product_bundle_only_on_the_woocommerce_product_editor(): void {
		$_GET = array( 'post' => '42' );
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'id' => 'product', 'post_type' => 'product', 'base' => 'post' );

		( new Assets() )->enqueue();

		self::assertSame( 'darven-precos-parcelados-product-options', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['handle'] );
		self::assertSame( 42, $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['productId'] );
		self::assertSame( 1, $GLOBALS['darven_epi_test_enqueued_media'] );
		self::assertNotEmpty( $GLOBALS['darven_epi_test_script_translations'] );
		self::assertSame( 'darven-precos-parcelados-product-options', $GLOBALS['darven_epi_test_script_translations'][0]['handle'] );
		self::assertSame( 'darven-multiplos-precos-informativos', $GLOBALS['darven_epi_test_script_translations'][0]['domain'] );
	}

	public function test_does_not_enqueue_react_assets_on_unrelated_admin_screens(): void {
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'id' => 'edit-post', 'post_type' => 'post', 'base' => 'edit' );

		( new Assets() )->enqueue();

		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_scripts'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_styles'] );
	}

	public function test_admin_bundle_manifests_remain_compatible_with_wordpress_5(): void {
		$settings_asset = require DARVEN_EPI_DIR_PATH . 'build/settings/index.asset.php';
		$product_asset  = require DARVEN_EPI_DIR_PATH . 'build/product-options/index.asset.php';

		foreach ( array( 'react', 'react-dom', 'react-jsx-runtime', 'wp-components' ) as $unsupported_dependency ) {
			self::assertNotContains( $unsupported_dependency, $settings_asset['dependencies'] );
			self::assertNotContains( $unsupported_dependency, $product_asset['dependencies'] );
		}
		self::assertContains( 'wp-element', $settings_asset['dependencies'] );
		self::assertContains( 'wp-api-fetch', $product_asset['dependencies'] );
		self::assertContains( 'wp-element', $product_asset['dependencies'] );
		self::assertContains( 'wp-i18n', $product_asset['dependencies'] );
	}

	public function test_registered_translation_handles_have_wordpress_resolvable_catalogues(): void {
		$_GET = array( 'page' => 'darven-epi-admin' );
		( new Assets() )->enqueue();

		$_GET = array( 'post' => '42' );
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'post_type' => 'product', 'base' => 'post' );
		( new Assets() )->enqueue();

		$bundles = array(
			'darven-precos-parcelados-settings'        => 'settings',
			'darven-precos-parcelados-product-options' => 'product-options',
		);

		foreach ( $GLOBALS['darven_epi_test_script_translations'] as $registration ) {
			$catalogue = $registration['path'] . DIRECTORY_SEPARATOR . $registration['domain'] . '-pt_BR-' . $registration['handle'] . '.json';

			self::assertFileExists( $catalogue, 'WordPress cannot resolve translations for ' . $registration['handle'] );
			self::assertFileExists(
				DARVEN_EPI_DIR_PATH . 'build/' . $bundles[ $registration['handle'] ] . '/index.js'
			);
		}
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_boot_does_not_register_or_load_the_legacy_settings_api(): void {
		$GLOBALS['darven_epi_test_actions'] = array();
		$GLOBALS['darven_epi_test_settings_sanitizers'] = array();

		Plugin::boot();

		self::assertNotContains( 'admin_init', array_column( $GLOBALS['darven_epi_test_actions'], 'hook' ) );
		self::assertSame( array(), $GLOBALS['darven_epi_test_settings_sanitizers'] );
		self::assertFalse( class_exists( 'Darven\\ExtraPriceInfo\\Admin\\LegacySettingsSync', false ) );
	}
}
