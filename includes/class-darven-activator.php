<?php

defined( 'ABSPATH' ) || exit();
if(!class_exists("Darven_Epi_Activator")){
	class Darven_Epi_Activator {

		/**
		 * Short Description. (use period)
		 *
		 * Long Description.
		 *
		 * @since    1.0.0
		 */
		public static function activate() {
			var_dump('activate');
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				// WooCommerce is not active, so we cannot activate the plugin.
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( 'Sorry, but this plugin requires WooCommerce to be installed and activated. Please activate WooCommerce and try again.' );
			}
		}

	}
}
