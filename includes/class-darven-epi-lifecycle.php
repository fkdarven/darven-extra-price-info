<?php
/**
 * Plugin activation and deactivation hooks.
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Lifecycle' ) ) {
	/**
	 * Registers and handles the plugin lifecycle callbacks.
	 */
	class Darven_Epi_Lifecycle {
		/**
		 * Absolute path to the main plugin file.
		 *
		 * @var string
		 */
		private static $plugin_file = '';

		/**
		 * Register lifecycle hooks against the main plugin file.
		 *
		 * @param string $plugin_file Absolute path to the main plugin file.
		 */
		public static function register( string $plugin_file ): void {
			self::$plugin_file = $plugin_file;

			register_activation_hook( $plugin_file, array( self::class, 'activate' ) );
			register_deactivation_hook( $plugin_file, array( self::class, 'deactivate' ) );
		}

		/**
		 * Run activation requirements.
		 */
		public static function activate(): void {
			require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-activator.php';
			Darven_Epi_Activator::activate( self::$plugin_file );
		}

		/**
		 * Run deactivation requirements.
		 */
		public static function deactivate(): void {
			require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-deactivator.php';
			Darven_Epi_Deactivator::deactivate();
		}
	}
}
