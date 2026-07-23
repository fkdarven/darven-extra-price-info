<?php

use Darven\ExtraPriceInfo\Admin\Assets;
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ): string {
		return 'https://example.test/wp-content/plugins/darven-extra-price-info/';
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ): void {
		$GLOBALS['darven_epi_test_enqueued_styles'][] = compact( 'handle', 'src', 'deps', 'ver', 'media' );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ): void {
		$GLOBALS['darven_epi_test_enqueued_scripts'][] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
	}
}

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
