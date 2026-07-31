<?php

use Darven\ExtraPriceInfo\Admin\Assets;
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
	}

	public function test_enqueues_the_settings_bundle_with_asset_metadata_and_rest_configuration(): void {
		$_GET = array( 'page' => 'darven-epi-admin<em></em>' );

		( new Assets() )->enqueue();

		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_styles'] );
		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_scripts'] );
		self::assertSame( 'darven-precos-parcelados-settings', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['handle'] );
		self::assertContains( 'wp-api-fetch', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertContains( 'wp-components', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertContains( 'wp-element', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		self::assertContains( 'wp-i18n', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['deps'] );
		$asset = require DARVEN_EPI_DIR_PATH . 'build/settings/index.asset.php';
		self::assertSame( $asset['version'], $GLOBALS['darven_epi_test_enqueued_scripts'][0]['ver'] );
		self::assertSame( 'https://example.test/wp-json/darven-precos-parcelados/v1/', $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['restUrl'] );
		self::assertSame( 'test-rest-nonce', $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['nonce'] );
	}

	public function test_enqueues_the_product_bundle_only_on_the_woocommerce_product_editor(): void {
		$_GET = array( 'post' => '42' );
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'id' => 'product', 'post_type' => 'product', 'base' => 'post' );

		( new Assets() )->enqueue();

		self::assertSame( 'darven-precos-parcelados-product-options', $GLOBALS['darven_epi_test_enqueued_scripts'][0]['handle'] );
		self::assertSame( 42, $GLOBALS['darven_epi_test_localized_scripts'][0]['data']['productId'] );
		self::assertSame( 1, $GLOBALS['darven_epi_test_enqueued_media'] );
	}

	public function test_does_not_enqueue_react_assets_on_unrelated_admin_screens(): void {
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'id' => 'edit-post', 'post_type' => 'post', 'base' => 'edit' );

		( new Assets() )->enqueue();

		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_scripts'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_enqueued_styles'] );
	}

	public function test_serializes_mutable_interest_fee_javascript_values(): void {
		$fields = file_get_contents( DARVEN_EPI_DIR_PATH . 'src/Admin/SettingsFields/GeneralFields.php' );

		self::assertIsString( $fields );
		self::assertStringContainsString( 'let customized_values = <?php echo wp_json_encode(', $fields );
		self::assertStringContainsString( 'let max_install = <?php echo wp_json_encode(', $fields );
		self::assertStringContainsString( 'let first_install = <?php echo wp_json_encode(', $fields );
	}
}
