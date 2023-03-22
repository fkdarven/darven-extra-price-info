<?php
/*
 * Plugin Name: Darven Múltiplos Preços Informativos
 * @package darven-extra-price-info
 * Plugin URI:
 * Description: Esse plugin é utilizado para mostrar múltiplos preços para um produto. Como preço á vista e parcelado.
 * Version: 2.0.1
 * Author: Leticia Moreira
 * Author URI: https://darven.wtf
 * Text Domain: darven-epi
 * Domain Path: /i18n/languages/
*/

defined( 'ABSPATH' ) || exit();



define( 'DARVEN_EPI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'DARVEN_EPI_STYLES_PATH', '/' . str_replace( site_url() . '/', '', plugin_dir_url( __FILE__ ) ) );


/*
load_plugin_textdomain('darven-epi', false,  'darven-multiplos-precos-informativos/i18n/languages');



new Darven_Epi_Format_Final_Price();

function get_style(): void {
	include DARVEN_EPI_DIR_PATH . 'public/partials/darven-epi-custom-css.php';
	wp_enqueue_style( 'darven_epi_frontend_css_system', DARVEN_EPI_STYLES_PATH . 'public/css/styles.css' );
	wp_register_script( 'darven_epi_admin_script_frontend', DARVEN_EPI_STYLES_PATH . 'public/js/frontend.js' );
	wp_enqueue_script( 'darven_epi_admin_script_frontend', "", array( 'jquery' ) );


}

function hook_css(): void {
	//echo DARVEN_EPI_DIR_PATH . 'public/partials/darven-epi-custom-css.php';

}

add_action( 'wp_head', 'hook_css' );
function darven_epi_load_admin_scripts(): void {

	if ( ! $page = filter_input( INPUT_GET, 'page' ) ) {
		return;
	}
	if ( ! (string) $page == 'darven-epi-admin' ) {
		return;
	}

	wp_register_script( 'darven_epi_admin_script', DARVEN_EPI_STYLES_PATH . 'admin/js/admin.js' );
	wp_enqueue_script( 'darven_epi_admin_script', "", array( 'jquery' ) );

	printf(DARVEN_EPI_STYLES_PATH . 'public/js/positions.js' );
	wp_register_script('darven_epi_admin_script_rato', DARVEN_EPI_STYLES_PATH . 'admin/js/positions.js');
	wp_enqueue_script('darven_epi_admin_script_rato');

	wp_enqueue_style( 'darven_epi_admin_style', DARVEN_EPI_STYLES_PATH . 'admin/css/admin_styles.css' );


}


add_action( 'admin_enqueue_scripts', 'darven_epi_load_admin_scripts' );
add_action( 'wp_enqueue_scripts', 'get_style' );


*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DARVEN_EPI_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-name-activator.php';
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
register_deactivation_hook( DARVEN_EPI_DIR_PATH . '/includes', 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

require DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name() {

	$plugin = new Darven_Epi();
	$plugin->run();

}
run_plugin_name();