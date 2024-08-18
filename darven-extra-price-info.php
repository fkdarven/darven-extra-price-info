<?php

use Darven\Epi\CoreInitializer;

/**
 * Plugin Name:         Darven - Extra Price Info
 * Plugin URI:          https://darven.zip
 * Description:         This plugin adds extra price info to the products. Includes price and installments info.
 * Author:              Darven
 * Author URI:          https://darzen.zip/
 * Text Domain:         darven-extra-price-info
 * Domain Path:         /languages
 * Version:             3.0.0
 * Requires at least:   6.0
 * Requires PHP:        8.0
 *
 * @package Darven
 * @subpackage Extra_Price_Info
 */

defined( 'ABSPATH' ) || exit();

if ( ! defined( 'DARVEN_EXTRA_PRICE_INFO_VERSION' ) ) {
	define( 'DARVEN_EXTRA_PRICE_INFO_VERSION', '0.1.0' );
}

if ( ! defined( 'DARVEN_EXTRA_PRICE_INFO_DOMAIN' ) ) {
	define( 'DARVEN_EXTRA_PRICE_INFO_DOMAIN', 'darven-extra-price-info' );
}

if ( ! defined( 'DARVEN_EXTRA_PRICE_INFO_PATH' ) ) {
	define( 'DARVEN_EXTRA_PRICE_INFO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DARVEN_EXTRA_PRICE_INFO_URL' ) ) {
	define( 'DARVEN_EXTRA_PRICE_INFO_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'DARVEN_EXTRA_PRICE_OPTIONS_BASE' ) ) {
	define( 'DARVEN_EXTRA_PRICE_OPTIONS_BASE', 'darven_epi' );
}

// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
if ( file_exists( DARVEN_EXTRA_PRICE_INFO_PATH . '/vendor/autoload.php' ) ) {
	require_once DARVEN_EXTRA_PRICE_INFO_PATH . '/vendor/autoload.php';
}

// Init class.
if ( class_exists( CoreInitializer::class ) ) {
	Darven\Epi\CoreInitializer::build();
}
