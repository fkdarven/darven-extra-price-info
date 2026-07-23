<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DARVEN_EPI_DIR_PATH', dirname( __DIR__ ) . '/' );

require_once DARVEN_EPI_DIR_PATH . 'vendor/autoload.php';

$GLOBALS['darven_epi_test_options'] = array();
$GLOBALS['darven_epi_test_actions'] = array();
$GLOBALS['darven_epi_test_filters'] = array();
$GLOBALS['darven_epi_test_submenu_pages'] = array();
$GLOBALS['darven_epi_test_failing_options'] = array();
$GLOBALS['darven_epi_test_option_reads'] = array();
$GLOBALS['darven_epi_test_settings_sanitizers'] = array();
$GLOBALS['darven_epi_test_update_option_calls'] = array();
$GLOBALS['darven_epi_test_update_option_depth'] = 0;

function get_option( $name, $default = false ) {
	$GLOBALS['darven_epi_test_option_reads'][] = $name;

	return $GLOBALS['darven_epi_test_options'][ $name ] ?? $default;
}

function update_option( $name, $value ): bool {
	$GLOBALS['darven_epi_test_update_option_calls'][] = $name;
	$GLOBALS['darven_epi_test_update_option_depth']++;

	try {
		if ( $GLOBALS['darven_epi_test_update_option_depth'] > 20 ) {
			throw new RuntimeException( 'Settings sanitizer recursion limit reached.' );
		}

		if ( in_array( $name, $GLOBALS['darven_epi_test_failing_options'], true ) ) {
			return false;
		}

		if ( isset( $GLOBALS['darven_epi_test_settings_sanitizers'][ $name ] ) ) {
			foreach ( $GLOBALS['darven_epi_test_settings_sanitizers'][ $name ] as $sanitize_callback ) {
				$value = call_user_func( $sanitize_callback, $value );
			}
		}

		if ( array_key_exists( $name, $GLOBALS['darven_epi_test_options'] ) && $GLOBALS['darven_epi_test_options'][ $name ] === $value ) {
			return false;
		}

		$GLOBALS['darven_epi_test_options'][ $name ] = $value;

		return true;
	} finally {
		$GLOBALS['darven_epi_test_update_option_depth']--;
	}
}

function register_setting( $option_group, $option_name, $args = array() ): void {
	$sanitize_callback = is_callable( $args )
		? $args
		: ( isset( $args['sanitize_callback'] ) ? $args['sanitize_callback'] : null );

	if ( null !== $sanitize_callback ) {
		$GLOBALS['darven_epi_test_settings_sanitizers'][ $option_name ][] = $sanitize_callback;
	}
}

function add_settings_field( $id, $title, $callback, $page, $section ): void {
}

function delete_option( $name ): bool {
	if ( in_array( $name, $GLOBALS['darven_epi_test_failing_options'], true ) ) {
		return false;
	}

	unset( $GLOBALS['darven_epi_test_options'][ $name ] );

	return true;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	$GLOBALS['darven_epi_test_actions'][] = array(
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	$GLOBALS['darven_epi_test_filters'][] = array(
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback ): void {
	$GLOBALS['darven_epi_test_submenu_pages'][] = array(
		'parent_slug' => $parent_slug,
		'page_title'  => $page_title,
		'menu_title'  => $menu_title,
		'capability'  => $capability,
		'menu_slug'   => $menu_slug,
		'callback'    => $callback,
	);
}

class WC_Product {
	private $id;
	private $meta;
	private $price;
	private $type;
	private $variation_price;
	private $save_count = 0;

	public function __construct( $price, $type = 'simple', $variation_price = null, $meta = array(), $id = 1 ) {
		$this->id              = $id;
		$this->meta            = $meta;
		$this->price           = $price;
		$this->type            = $type;
		$this->variation_price = $variation_price;
	}

	public function get_id() {
		return $this->id;
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

	public function update_meta_data( $key, $value ): void {
		$this->meta[ $key ] = $value;
	}

	public function save(): void {
		$this->save_count++;
	}

	public function get_save_count(): int {
		return $this->save_count;
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

function wp_unslash( $value ) {
	return $value;
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function __( $text, $domain = null ): string {
	return (string) $text;
}

function wp_kses( $value, $allowed_html ): string {
	$allowed_tags = '';

	foreach ( array_keys( $allowed_html ) as $tag ) {
		$allowed_tags .= '<' . $tag . '>';
	}

	return strip_tags( (string) $value, $allowed_tags );
}

function current_user_can( $capability, $object_id = null ): bool {
	$GLOBALS['darven_epi_test_capability_check'] = array( $capability, $object_id );

	return $GLOBALS['darven_epi_test_current_user_can'] ?? true;
}

function wp_verify_nonce( $nonce, $action ): bool {
	$GLOBALS['darven_epi_test_nonce_check'] = array( $nonce, $action );

	return $GLOBALS['darven_epi_test_nonce_is_valid'] ?? true;
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
