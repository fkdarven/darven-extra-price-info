<?php

use Darven\ExtraPriceInfo\Admin\Assets;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AdminAssetsDoubles.php';

final class AdminAssetsTest extends TestCase {
	protected function setUp(): void {
		$_GET = array();
		$GLOBALS['darven_epi_test_enqueued_styles']  = array();
		$GLOBALS['darven_epi_test_enqueued_scripts'] = array();
	}

	public function test_enqueues_admin_assets_after_sanitizing_the_current_page(): void {
		$_GET = array(
			'page' => 'darven-epi-admin<em></em>',
			'tab'  => 'positions',
		);

		( new Assets() )->enqueue();

		self::assertCount( 1, $GLOBALS['darven_epi_test_enqueued_styles'] );
		self::assertCount( 0, $GLOBALS['darven_epi_test_enqueued_scripts'] );
	}

	public function test_serializes_mutable_interest_fee_javascript_values(): void {
		$fields = file_get_contents( DARVEN_EPI_DIR_PATH . 'src/Admin/SettingsFields/GeneralFields.php' );

		self::assertIsString( $fields );
		self::assertStringContainsString( 'let customized_values = <?php echo wp_json_encode(', $fields );
		self::assertStringContainsString( 'let max_install = <?php echo wp_json_encode(', $fields );
		self::assertStringContainsString( 'let first_install = <?php echo wp_json_encode(', $fields );
	}
}
