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
		add_filter(
			'woocommerce_product_data_tabs', array( $this, 'addTab' )
		);
		add_action(
			'woocommerce_product_data_panels', array( $this, 'renderPanel' )
		);
		add_action(
			'woocommerce_admin_process_product_object',
			array( $this, 'save' )
		);
	}

	public function addTab( array $tabs ): array {
		$tabs['darven-precos-parcelados'] = array(
			'label'  => __( 'Darven', 'darven-multiplos-precos-informativos' ),
			'target' => 'darven-precos-parcelados-product-options-panel',
			'class'  => array(),
		);

		return $tabs;
	}

	public function renderPanel(): void {
		global $post;

		$product_id = isset( $post->ID ) ? absint( $post->ID ) : 0;
		printf(
			'<div id="darven-precos-parcelados-product-options-panel" class="panel woocommerce_options_panel hidden"><div id="darven-precos-parcelados-product-options-root" data-product-id="%s"></div></div>', esc_attr( (string) $product_id )
		);
	}

	public function save( \WC_Product $product ): void {
		if ( ! array_key_exists( '_darven_epi_is_incash_enabled', $_POST )
			&& ! array_key_exists( '_darven_epi_is_installment_enabled', $_POST ) ) {
			return;
		}

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

		$incash_value = isset( $_POST['_darven_epi_is_incash_enabled'] ) && is_string( $_POST['_darven_epi_is_incash_enabled'] )
			? sanitize_text_field( wp_unslash( $_POST['_darven_epi_is_incash_enabled'] ) )
			: '';
		$installments_value = isset( $_POST['_darven_epi_is_installment_enabled'] ) && is_string( $_POST['_darven_epi_is_installment_enabled'] )
			? sanitize_text_field( wp_unslash( $_POST['_darven_epi_is_installment_enabled'] ) )
			: '';

		$settings = array(
			'disable_incash'       => 'yes' === $incash_value,
			'disable_installments' => 'yes' === $installments_value,
		);

		$this->repository->save( $product, $settings );
	}

}
