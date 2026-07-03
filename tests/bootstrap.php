<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DARVEN_EPI_DIR_PATH', dirname( __DIR__ ) . '/' );

$GLOBALS['darven_epi_test_options'] = array();

function get_option( $name, $default = false ) {
	return $GLOBALS['darven_epi_test_options'][ $name ] ?? $default;
}

class WC_Product {
	private $meta;
	private $price;
	private $type;
	private $variation_price;

	public function __construct( $price, $type = 'simple', $variation_price = null, $meta = array() ) {
		$this->meta            = $meta;
		$this->price           = $price;
		$this->type            = $type;
		$this->variation_price = $variation_price;
	}

	public function get_price() {
		return $this->price;
	}

	public function is_type( $type ) {
		return $this->type === $type;
	}

	public function get_variation_price( $min_or_max = 'min', $for_display = false ) {
		return $this->variation_price;
	}

	public function get_meta( $key, $single = true ) {
		return $this->meta[ $key ] ?? '';
	}
}

function is_admin() {
	return false;
}

function is_checkout() {
	return false;
}

function is_cart() {
	return false;
}

function is_product() {
	return true;
}

function is_product_category() {
	return false;
}

function is_shop() {
	return false;
}

function is_category() {
	return false;
}

function is_search() {
	return false;
}

function is_home() {
	return false;
}

function wc_price( $price ) {
	return 'R$ ' . number_format( (float) $price, 2, '.', '' );
}

function wp_strip_all_tags( $text ) {
	return strip_tags( $text );
}

function is_plugin_active( $plugin ): bool {
	return $GLOBALS['darven_epi_test_is_plugin_active'] ?? true;
}

function plugin_basename( $plugin_file ): string {
	$GLOBALS['darven_epi_test_plugin_basename_input'] = $plugin_file;

	return basename( $plugin_file );
}

function deactivate_plugins( $plugin ): void {
	$GLOBALS['darven_epi_test_deactivated_plugin'] = $plugin;
}

function wp_die( $message ): void {
	throw new RuntimeException( $message );
}

require_once DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-product-price.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/utils/class-darven-epi-html-generator.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-final-price.php';
