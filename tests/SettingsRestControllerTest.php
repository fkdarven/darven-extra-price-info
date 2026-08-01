<?php

use Darven\ExtraPriceInfo\Admin\SettingsRestController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

final class SettingsRestControllerTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();
		$GLOBALS['darven_epi_test_failing_options'] = array();
		$GLOBALS['darven_epi_test_option_reads'] = array();
		$GLOBALS['darven_epi_test_rest_routes'] = array();
		$GLOBALS['darven_epi_test_rest_dispatch_log'] = array();
		$GLOBALS['darven_epi_test_products'] = array();
		$GLOBALS['darven_epi_test_current_user_can'] = true;
		unset( $GLOBALS['darven_epi_test_capability_check'] );
	}

	public function test_registers_the_four_authorized_routes_under_the_v1_namespace(): void {
		$this->getSubject()->register();

		self::assertSame(
			array( '/settings', '/settings', '/products/(?P<id>\\d+)/settings', '/products/(?P<id>\\d+)/settings' ),
			array_column( $GLOBALS['darven_epi_test_rest_routes'], 'route' )
		);
		foreach ( $GLOBALS['darven_epi_test_rest_routes'] as $route ) {
			self::assertSame( 'darven-precos-parcelados/v1', $route['namespace'] );
			self::assertArrayHasKey( 'permission_callback', $route['args'] );
		}
	}

	public function test_denied_settings_request_does_not_read_the_repository(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;

		$result = $this->getSubject()->canManageSettings();

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'darven_epi_forbidden', $result->get_error_code() );
		self::assertSame( array(), $GLOBALS['darven_epi_test_option_reads'] );
	}

	public function test_rest_dispatch_runs_permission_before_the_settings_callback_and_preserves_the_error_status(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$this->getSubject()->register();

		$response = darven_epi_test_dispatch_rest_request(
			'darven-precos-parcelados/v1', 'GET', '/settings', new WP_REST_Request()
		);

		self::assertSame( 403, $response->get_status() );
		self::assertSame( 'darven_epi_forbidden', $response->get_data()['code'] );
		self::assertSame( array( 'permission' ), $GLOBALS['darven_epi_test_rest_dispatch_log'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_option_reads'] );
	}

	public function test_rest_dispatch_does_not_reveal_an_unknown_product_to_an_unauthorized_user(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$this->getSubject()->register();

		$response = darven_epi_test_dispatch_rest_request(
			'darven-precos-parcelados/v1', 'GET', '/products/404/settings', new WP_REST_Request( array( 'id' => 404 ) )
		);

		self::assertSame( 403, $response->get_status() );
		self::assertSame( 'darven_epi_forbidden', $response->get_data()['code'] );
		self::assertSame( array( 'permission' ), $GLOBALS['darven_epi_test_rest_dispatch_log'] );
		self::assertSame( array( 'edit_post', 404 ), $GLOBALS['darven_epi_test_capability_check'] );
	}

	public function test_product_permission_does_not_reveal_an_unknown_id_to_an_unauthorized_user(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;

		$result = $this->getSubject()->canEditProduct( new WP_REST_Request( array( 'id' => 999 ) ) );

		self::assertSame( 'darven_epi_forbidden', $result->get_error_code() );
	}

	public function test_rest_dispatch_keeps_unauthorized_existing_products_out_of_handlers(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$GLOBALS['darven_epi_test_products'][42] = new WC_Product( '100.00', 'simple', null, array(), 42 );
		$this->getSubject()->register();

		$response = darven_epi_test_dispatch_rest_request(
			'darven-precos-parcelados/v1', 'GET', '/products/42/settings', new WP_REST_Request( array( 'id' => 42 ) )
		);

		self::assertSame( 403, $response->get_status() );
		self::assertSame( 'darven_epi_forbidden', $response->get_data()['code'] );
		self::assertSame( array( 'permission' ), $GLOBALS['darven_epi_test_rest_dispatch_log'] );
		self::assertSame( array( 'edit_post', 42 ), $GLOBALS['darven_epi_test_capability_check'] );
	}

	public function test_get_settings_normalizes_legacy_storage_without_writing_it(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array(
			'darven_epi_max_installments' => '9',
		);

		$response = $this->getSubject()->getSettings();

		self::assertSame( 200, $response->get_status() );
		self::assertSame( 2, $response->get_data()['schema_version'] );
		self::assertSame( '9', $response->get_data()['general']['darven_epi_max_installments'] );
		self::assertArrayNotHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
	}

	public function test_put_settings_persists_v2_and_mirrors_legacy_options(): void {
		$response = $this->getSubject()->updateSettings(
			new WP_REST_Request( array(), array( 'general' => array( 'darven_epi_max_installments' => '12' ) ) )
		);

		self::assertSame( 200, $response->get_status() );
		self::assertSame( 2, $response->get_data()['schema_version'] );
		self::assertSame( '12', $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['general']['darven_epi_max_installments'] );
		self::assertSame( '12', $GLOBALS['darven_epi_test_options']['darven_epi_option_general']['darven_epi_max_installments'] );
	}

	public function test_put_settings_rejects_a_non_object_payload(): void {
		$result = $this->getSubject()->updateSettings( new WP_REST_Request( array(), 'not-an-object' ) );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'darven_epi_invalid_settings', $result->get_error_code() );
		self::assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_put_settings_reports_persistence_failures(): void {
		$GLOBALS['darven_epi_test_failing_options'] = array( SettingsRepository::OPTION_NAME );

		$result = $this->getSubject()->updateSettings( new WP_REST_Request( array(), array( 'general' => array() ) ) );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'darven_epi_settings_save_failed', $result->get_error_code() );
		self::assertSame( 500, $result->get_error_data()['status'] );
	}

	public function test_product_routes_return_and_save_normalized_product_settings(): void {
		$product = new WC_Product( '100.00', 'simple', null, array(), 42 );
		$GLOBALS['darven_epi_test_products'][42] = $product;

		$get_response = $this->getSubject()->getProductSettings( new WP_REST_Request( array( 'id' => 42 ) ) );
		$put_response = $this->getSubject()->updateProductSettings(
			new WP_REST_Request( array( 'id' => 42 ), array( 'disable_incash' => true ) )
		);

		self::assertSame( false, $get_response->get_data()['disable_incash'] );
		self::assertSame( true, $put_response->get_data()['disable_incash'] );
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
	}

	public function test_product_request_returns_not_found_when_woocommerce_cannot_resolve_it(): void {
		$result = $this->getSubject()->getProductSettings( new WP_REST_Request( array( 'id' => 404 ) ) );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'darven_epi_product_not_found', $result->get_error_code() );
	}

	private function getSubject(): SettingsRestController {
		return new SettingsRestController(
			new SettingsRepository( new LegacySettingsAdapter() ),
			new ProductSettingsRepository( new LegacyProductSettingsAdapter() )
		);
	}
}
