<?php
/*
 * Plugin Name: Darven Múltiplos Preços Informativos
 * @package darven-extra-price-info
 * Plugin URI: wordpress.org/plugins/darven-multiplos-precos-informativos/
 * Description: This plugin is used to show multiple prices for a product. Incash and installments price.
 * Version: 3.1.4
 * Author: Leticia Moreira
 * Author URI: https://darven.wtf
 * Text Domain: darven-epi
 * Domain Path: /i18n/languages/
 * Requires WooCommerce
*/

defined( 'ABSPATH' ) || exit();


define( 'DARVEN_EPI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'DARVEN_EPI_STYLES_PATH', '/' . str_replace( site_url() . '/', '', plugin_dir_url( __FILE__ ) ) );
const DARVEN_EPI_ADMIN_PAGE = 'darven-epi-admin';
const DARVEN_EPI_LANGUAGE_DOMAIN = 'darven-epi';
if ( ! defined( 'WPINC' ) ) {
	die;
}

const DARVEN_EPI_VERSION = '3.1.4';

function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-darven-activator.php';
	Darven_Epi_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-name-deactivator.php';
	Darven_Epi_Deactivator::deactivate();
}

register_activation_hook( DARVEN_EPI_DIR_PATH . '/includes', 'activate_plugin_name' );
//register_deactivation_hook( DARVEN_EPI_DIR_PATH . '/includes', 'deactivate_plugin_name' );


require DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi.php';
function run_plugin_name() {

	$plugin = new Darven_Epi();
	$plugin->run();

}

run_plugin_name();