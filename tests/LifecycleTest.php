<?php

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $plugin_file, $callback ): void {
		$GLOBALS['darven_epi_activation_hook'] = array( $plugin_file, $callback );
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $plugin_file, $callback ): void {
		$GLOBALS['darven_epi_deactivation_hook'] = array( $plugin_file, $callback );
	}
}

final class LifecycleTest extends TestCase {
	protected function setUp(): void {
		unset(
			$GLOBALS['darven_epi_activation_hook'],
			$GLOBALS['darven_epi_deactivation_hook']
		);
	}

	public function test_registers_lifecycle_hooks_for_the_main_plugin_file(): void {
		$lifecycle_file = DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi-lifecycle.php';

		if ( file_exists( $lifecycle_file ) ) {
			require_once $lifecycle_file;
		}

		self::assertTrue( class_exists( 'Darven_Epi_Lifecycle' ) );

		$plugin_file = DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php';
		Darven_Epi_Lifecycle::register( $plugin_file );

		self::assertSame( $plugin_file, $GLOBALS['darven_epi_activation_hook'][0] );
		self::assertSame(
			array( 'Darven_Epi_Lifecycle', 'activate' ),
			$GLOBALS['darven_epi_activation_hook'][1]
		);
		self::assertSame( $plugin_file, $GLOBALS['darven_epi_deactivation_hook'][0] );
		self::assertSame(
			array( 'Darven_Epi_Lifecycle', 'deactivate' ),
			$GLOBALS['darven_epi_deactivation_hook'][1]
		);
	}
}
