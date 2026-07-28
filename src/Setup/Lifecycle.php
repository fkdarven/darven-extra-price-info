<?php

namespace Darven\ExtraPriceInfo\Setup;

final class Lifecycle {
	/**
	 * @var string
	 */
	private static $plugin_file = '';

	public static function register( string $plugin_file ): void {
		self::$plugin_file = $plugin_file;

		register_activation_hook( $plugin_file, array( self::class, 'activate' ) );
		register_deactivation_hook( $plugin_file, array( self::class, 'deactivate' ) );
	}

	public static function activate(): void {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( plugin_basename( self::$plugin_file ) );
			wp_die( 'Sorry, but this plugin requires WooCommerce to be installed and activated. Please activate WooCommerce and try again.' );
		}
	}

	public static function deactivate(): void {
	}
}
