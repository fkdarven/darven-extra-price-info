<?php

use PHPUnit\Framework\TestCase;

require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-activator.php';

final class ActivatorTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_is_plugin_active'] = false;
		unset(
			$GLOBALS['darven_epi_test_plugin_basename_input'],
			$GLOBALS['darven_epi_test_deactivated_plugin']
		);
	}

	public function test_deactivates_the_main_plugin_when_woocommerce_is_unavailable(): void {
		$plugin_file = DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php';

		try {
			Darven_Epi_Activator::activate( $plugin_file );
			self::fail( 'Activation should stop when WooCommerce is unavailable.' );
		} catch ( RuntimeException $exception ) {
			self::assertStringContainsString( 'requires WooCommerce', $exception->getMessage() );
		}

		self::assertSame( $plugin_file, $GLOBALS['darven_epi_test_plugin_basename_input'] );
		self::assertSame( 'darven-extra-price-info.php', $GLOBALS['darven_epi_test_deactivated_plugin'] );
	}
}
