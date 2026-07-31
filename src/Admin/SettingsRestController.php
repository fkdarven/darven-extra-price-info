<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class SettingsRestController {
	private const NAMESPACE = 'darven-precos-parcelados/v1';

	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var ProductSettingsRepository
	 */
	private $product_settings_repository;

	public function __construct( SettingsRepository $settings_repository, ProductSettingsRepository $product_settings_repository ) {
		$this->settings_repository         = $settings_repository;
		$this->product_settings_repository = $product_settings_repository;
	}

	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getSettings' ),
				'permission_callback' => array( $this, 'canManageSettings' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'updateSettings' ),
				'permission_callback' => array( $this, 'canManageSettings' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\\d+)/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getProductSettings' ),
				'permission_callback' => array( $this, 'canEditProduct' ),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\\d+)/settings',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'updateProductSettings' ),
				'permission_callback' => array( $this, 'canEditProduct' ),
			)
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function canManageSettings() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->forbiddenError();
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function canEditProduct( \WP_REST_Request $request ) {
		$product_id = absint( $request->get_param( 'id' ) );
		if ( ! wc_get_product( $product_id ) ) {
			return true;
		}

		if ( current_user_can( 'edit_post', $product_id ) ) {
			return true;
		}

		return $this->forbiddenError();
	}

	public function getSettings(): \WP_REST_Response {
		return rest_ensure_response( $this->settings_repository->getNormalizedSettings() );
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function updateSettings( \WP_REST_Request $request ) {
		$document = $request->get_json_params();
		if ( ! is_array( $document ) ) {
			return new \WP_Error(
				'darven_epi_invalid_settings',
				__( 'The settings payload must be an object.', 'darven-multiplos-precos-informativos' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $this->settings_repository->saveDocument( $document ) ) {
			return new \WP_Error(
				'darven_epi_settings_save_failed',
				__( 'The settings could not be saved.', 'darven-multiplos-precos-informativos' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $this->settings_repository->getNormalizedSettings() );
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function getProductSettings( \WP_REST_Request $request ) {
		$product = $this->getProduct( $request );
		if ( $product instanceof \WP_Error ) {
			return $product;
		}

		return rest_ensure_response( $this->product_settings_repository->getSettings( $product ) );
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function updateProductSettings( \WP_REST_Request $request ) {
		$settings = $request->get_json_params();
		if ( ! is_array( $settings ) ) {
			return new \WP_Error(
				'darven_epi_invalid_product_settings',
				__( 'The product settings payload must be an object.', 'darven-multiplos-precos-informativos' ),
				array( 'status' => 400 )
			);
		}

		$product = $this->getProduct( $request );
		if ( $product instanceof \WP_Error ) {
			return $product;
		}

		if ( ! $this->product_settings_repository->save( $product, $settings ) ) {
			return new \WP_Error(
				'darven_epi_product_settings_save_failed',
				__( 'The product settings could not be saved.', 'darven-multiplos-precos-informativos' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $this->product_settings_repository->getSettings( $product ) );
	}

	/**
	 * @return \WC_Product|\WP_Error
	 */
	private function getProduct( \WP_REST_Request $request ) {
		$product = wc_get_product( absint( $request->get_param( 'id' ) ) );
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		return new \WP_Error(
			'darven_epi_product_not_found',
			__( 'The requested product was not found.', 'darven-multiplos-precos-informativos' ),
			array( 'status' => 404 )
		);
	}

	private function forbiddenError(): \WP_Error {
		return new \WP_Error(
			'darven_epi_forbidden',
			__( 'You are not allowed to manage these settings.', 'darven-multiplos-precos-informativos' ),
			array( 'status' => 403 )
		);
	}
}
