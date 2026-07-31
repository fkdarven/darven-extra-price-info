<?php
/*
 * Plugin Name: Darven Múltiplos Preços Informativos
 * @package darven-extra-price-info
 * Plugin URI: wordpress.org/plugins/darven-multiplos-precos-informativos/
 * Description: This plugin is used to show multiple prices for a product. Incash and installments price.
 * Version: 4.0.0
 * Author: Leticia Moreira
 * Author URI: https://darven.wtf
 * Text Domain: darven-multiplos-precos-informativos
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
*/

defined( 'ABSPATH' ) || exit();


define( 'DARVEN_EPI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'DARVEN_EPI_STYLES_PATH', '/' . str_replace( site_url() . '/', '', plugin_dir_url( __FILE__ ) ) );
const DARVEN_EPI_ADMIN_PAGE = 'darven-epi-admin';
const DARVEN_EPI_LANGUAGE_DOMAIN = 'darven-multiplos-precos-informativos';
if ( ! defined( 'WPINC' ) ) {
	die;
}

const DARVEN_EPI_VERSION = '4.0.0';

$darven_epi_autoload_file = DARVEN_EPI_DIR_PATH . 'vendor/autoload.php';

if ( ! is_readable( $darven_epi_autoload_file ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'Darven Extra Price Info is missing its runtime dependencies.', DARVEN_EPI_LANGUAGE_DOMAIN )
				. '</p></div>';
		}
	);

	return;
}

require_once $darven_epi_autoload_file;

\Darven\ExtraPriceInfo\Setup\Lifecycle::register( __FILE__ );
\Darven\ExtraPriceInfo\Setup\Plugin::boot();
