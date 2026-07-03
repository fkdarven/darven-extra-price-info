<?php
/*
 * Plugin Name: Darven Múltiplos Preços Informativos
 * @package darven-extra-price-info
 * Plugin URI: wordpress.org/plugins/darven-multiplos-precos-informativos/
 * Description: This plugin is used to show multiple prices for a product. Incash and installments price.
 * Version: 3.2.0
 * Author: Leticia Moreira
 * Author URI: https://darven.wtf
 * Text Domain: darven-epi
 * Domain Path: /i18n/languages/
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
*/

defined( 'ABSPATH' ) || exit();


define( 'DARVEN_EPI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'DARVEN_EPI_STYLES_PATH', '/' . str_replace( site_url() . '/', '', plugin_dir_url( __FILE__ ) ) );
const DARVEN_EPI_ADMIN_PAGE = 'darven-epi-admin';
const DARVEN_EPI_LANGUAGE_DOMAIN = 'darven-epi';
if ( ! defined( 'WPINC' ) ) {
	die;
}

const DARVEN_EPI_VERSION = '3.2.0';

require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi-lifecycle.php';
Darven_Epi_Lifecycle::register( __FILE__ );

require DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi.php';
function run_plugin_name() {

	$plugin = new Darven_Epi();
	$plugin->run();

}

run_plugin_name();
