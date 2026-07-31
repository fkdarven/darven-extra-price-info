<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DARVEN_EPI_DIR_PATH', dirname( __DIR__ ) . '/' );
define( 'DARVEN_EPI_LANGUAGE_DOMAIN', 'darven-multiplos-precos-informativos' );

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
$GLOBALS['darven_epi_test_rest_routes'] = array();
$GLOBALS['darven_epi_test_rest_dispatch_log'] = array();
$GLOBALS['darven_epi_test_products'] = array();
$GLOBALS['darven_epi_test_localized_scripts'] = array();
$GLOBALS['darven_epi_test_enqueued_media'] = 0;
$GLOBALS['darven_epi_test_screen'] = null;
$GLOBALS['darven_epi_test_loaded_textdomains'] = array();

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

		$value    = darven_epi_test_sanitize_option( $name, $value );
		$has_old  = array_key_exists( $name, $GLOBALS['darven_epi_test_options'] );
		$old_value = $has_old ? $GLOBALS['darven_epi_test_options'][ $name ] : false;

		if ( $has_old && $old_value === $value ) {
			return false;
		}

		if ( ! $has_old ) {
			return add_option( $name, $value );
		}

		if ( in_array( $name, $GLOBALS['darven_epi_test_failing_options'], true ) ) {
			return false;
		}

		$GLOBALS['darven_epi_test_options'][ $name ] = $value;
		do_action( 'update_option_' . $name, $old_value, $value, $name );

		return true;
	} finally {
		$GLOBALS['darven_epi_test_update_option_depth']--;
	}
}

function add_option( $name, $value = '', $deprecated = '', $autoload = null ): bool {
	$value = darven_epi_test_sanitize_option( $name, $value );

	if ( array_key_exists( $name, $GLOBALS['darven_epi_test_options'] ) ) {
		return false;
	}

	if ( in_array( $name, $GLOBALS['darven_epi_test_failing_options'], true ) ) {
		return false;
	}

	$GLOBALS['darven_epi_test_options'][ $name ] = $value;
	do_action( 'add_option_' . $name, $name, $value );

	return true;
}

function darven_epi_test_sanitize_option( $name, $value ) {
	if ( ! isset( $GLOBALS['darven_epi_test_settings_sanitizers'][ $name ] ) ) {
		return $value;
	}

	foreach ( $GLOBALS['darven_epi_test_settings_sanitizers'][ $name ] as $sanitize_callback ) {
		$value = call_user_func( $sanitize_callback, $value );
	}

	return $value;
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

function remove_action( $hook, $callback, $priority = 10 ): bool {
	foreach ( $GLOBALS['darven_epi_test_actions'] as $index => $registration ) {
		if ( $hook === $registration['hook'] && $priority === $registration['priority'] && $callback === $registration['callback'] ) {
			unset( $GLOBALS['darven_epi_test_actions'][ $index ] );

			return true;
		}
	}

	return false;
}

function do_action( $hook, ...$args ): void {
	$registrations = array_values(
		array_filter(
			$GLOBALS['darven_epi_test_actions'],
			function ( array $registration ) use ( $hook ): bool {
				return $hook === $registration['hook'];
			}
		)
	);

	usort(
		$registrations,
		function ( array $left, array $right ): int {
			return $left['priority'] <=> $right['priority'];
		}
	);

	foreach ( $registrations as $registration ) {
		call_user_func_array( $registration['callback'], array_slice( $args, 0, $registration['accepted_args'] ) );
	}
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

function register_rest_route( $namespace, $route, $args ): void {
	$GLOBALS['darven_epi_test_rest_routes'][] = array(
		'namespace' => $namespace,
		'route'     => $route,
		'args'      => $args,
	);
}

class WP_REST_Request {
	private $params;
	private $json_params;

	public function __construct( array $params = array(), $json_params = array() ) {
		$this->params      = $params;
		$this->json_params = $json_params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}

	public function get_json_params() {
		return $this->json_params;
	}
}

class WP_REST_Response {
	private $data;
	private $status;

	public function __construct( $data, $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function get_data() {
		return $this->data;
	}

	public function get_status(): int {
		return $this->status;
	}
}

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_data() {
		return $this->data;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

function rest_ensure_response( $response ): WP_REST_Response {
	if ( $response instanceof WP_Error ) {
		$error_data = $response->get_error_data();
		$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? $error_data['status'] : 500;

		return new WP_REST_Response(
			array(
				'code'    => $response->get_error_code(),
				'message' => $response->get_error_message(),
				'data'    => $error_data,
			),
			$status
		);
	}

	return $response instanceof WP_REST_Response ? $response : new WP_REST_Response( $response );
}

function darven_epi_test_dispatch_rest_request( $namespace, $method, $path, WP_REST_Request $request ): WP_REST_Response {
	foreach ( $GLOBALS['darven_epi_test_rest_routes'] as $route ) {
		if ( $namespace !== $route['namespace'] || $method !== $route['args']['methods'] ) {
			continue;
		}

		if ( 1 !== preg_match( '#^' . $route['route'] . '$#', $path ) ) {
			continue;
		}

		$GLOBALS['darven_epi_test_rest_dispatch_log'][] = 'permission';
		$permission = call_user_func( $route['args']['permission_callback'], $request );
		if ( true !== $permission ) {
			return rest_ensure_response( $permission );
		}

		$GLOBALS['darven_epi_test_rest_dispatch_log'][] = 'callback';

		return rest_ensure_response( call_user_func( $route['args']['callback'], $request ) );
	}

	return new WP_REST_Response( array( 'code' => 'rest_no_route' ), 404 );
}

function wp_create_nonce( $action ): string {
	$GLOBALS['darven_epi_test_nonce_action'] = $action;

	return 'test-rest-nonce';
}

function wp_localize_script( $handle, $object_name, $data ): void {
	$GLOBALS['darven_epi_test_localized_scripts'][] = array(
		'handle'      => $handle,
		'object_name' => $object_name,
		'data'        => $data,
	);
}

function wp_enqueue_media(): void {
	$GLOBALS['darven_epi_test_enqueued_media']++;
}

function get_current_screen() {
	return $GLOBALS['darven_epi_test_screen'];
}

function wc_get_product( $product_id ) {
	return $GLOBALS['darven_epi_test_products'][ (int) $product_id ] ?? false;
}

function rest_url( $path = '' ): string {
	return 'https://example.test/wp-json/' . ltrim( $path, '/' );
}

function absint( $value ): int {
	return abs( (int) $value );
}

function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function wp_unique_id( $prefix = '' ): string {
	$GLOBALS['darven_epi_test_unique_id'] = ( $GLOBALS['darven_epi_test_unique_id'] ?? 0 ) + 1;

	return (string) $prefix . $GLOBALS['darven_epi_test_unique_id'];
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

function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ): bool {
	$GLOBALS['darven_epi_test_loaded_textdomains'][] = array(
		'domain' => $domain,
		'path'   => $plugin_rel_path,
	);

	return true;
}

function deactivate_plugins( $plugin ): void {
	$GLOBALS['darven_epi_test_deactivated_plugin'] = $plugin;
}

function wp_die( $message ): void {
	throw new RuntimeException( $message );
}
