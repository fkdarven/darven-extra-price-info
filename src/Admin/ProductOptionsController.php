<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;

final class ProductOptionsController {
	/**
	 * @var ProductSettingsRepository
	 */
	private $repository;

	public function __construct( ProductSettingsRepository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action(
			'woocommerce_product_options_general_product_data',
			array( $this, 'renderFields' )
		);
		add_action(
			'woocommerce_admin_process_product_object',
			array( $this, 'save' )
		);
	}

	public function renderFields(): void {
		echo '<div class="product_custom_field">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_darven_epi_is_incash_enabled',
				'placeholder' => '',
				'label'       => __( 'Disable in cash price for this product', 'woocommerce' ),
				'type'        => 'boolean',
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => '_darven_epi_is_installment_enabled',
				'placeholder' => '',
				'label'       => __( 'Disable installments price for this product', 'woocommerce' ),
				'type'        => 'boolean',
			)
		);
		echo '</div>';
	}

	public function save( \WC_Product $product ): void {
		if ( ! current_user_can( 'edit_post', $product->get_id() ) ) {
			return;
		}
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) {
			return;
		}

		$settings = array(
			'disable_incash'       => 'yes' === ( $_POST['_darven_epi_is_incash_enabled'] ?? '' ),
			'disable_installments' => 'yes' === ( $_POST['_darven_epi_is_installment_enabled'] ?? '' ),
		);

		$this->repository->save( $product, $settings );
	}
}
