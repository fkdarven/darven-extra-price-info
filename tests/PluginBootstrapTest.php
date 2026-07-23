<?php

use Darven\ExtraPriceInfo\Setup\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginBootstrapTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_actions']       = array();
		$GLOBALS['darven_epi_test_filters']       = array();
		$GLOBALS['darven_epi_test_submenu_pages'] = array();
	}

	public function test_boot_registers_each_runtime_action_once(): void {
		Plugin::boot();

		$hooks = array_column( $GLOBALS['darven_epi_test_actions'], 'hook' );

		foreach ( array(
			'plugins_loaded',
			'admin_enqueue_scripts',
			'wp_enqueue_scripts',
			'woocommerce_product_options_general_product_data',
			'woocommerce_admin_process_product_object',
		) as $hook ) {
			self::assertSame( 1, count( array_keys( $hooks, $hook, true ) ), $hook . ' should register once.' );
		}
	}

	public function test_admin_menu_keeps_the_woocommerce_route_and_capability(): void {
		Plugin::boot();

		$callbacks = array_filter(
			$GLOBALS['darven_epi_test_actions'],
			static function ( array $registration ): bool {
				return 'admin_menu' === $registration['hook'];
			}
		);

		self::assertCount( 1, $callbacks );
		$callback = reset( $callbacks )['callback'];
		call_user_func( $callback );

		self::assertSame(
			array(
				'parent_slug' => 'woocommerce',
				'capability'  => 'manage_options',
				'menu_slug'   => 'darven-epi-admin',
			),
			array_intersect_key(
				$GLOBALS['darven_epi_test_submenu_pages'][0],
				array_flip( array( 'parent_slug', 'capability', 'menu_slug' ) )
			)
		);
	}
}
