<?php
/**
 * Product-specific price display controls.
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Product_Options' ) ) {
	/**
	 * Registers and persists the per-product price display options.
	 */
	class Darven_Epi_Product_Options {
		/**
		 * Register the WooCommerce product hooks.
		 */
		public function __construct() {
			add_action(
				'woocommerce_product_options_general_product_data',
				array( $this, 'woocommerce_product_custom_fields' )
			);
			add_action(
				'woocommerce_admin_process_product_object',
				array( $this, 'save_extra_prices' )
			);
		}

		/**
		 * Render the product-level display controls.
		 */
		public function woocommerce_product_custom_fields(): void {
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

		/**
		 * Persist product-level display controls.
		 *
		 * @param WC_Product|null $product Product being saved by WooCommerce.
		 */
		public function save_extra_prices( $product ): void {
			if ( ! $product instanceof WC_Product ) {
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

			$incash_value      = isset( $_POST['_darven_epi_is_incash_enabled'] )
				? sanitize_text_field( wp_unslash( $_POST['_darven_epi_is_incash_enabled'] ) )
				: null;
			$installment_value = isset( $_POST['_darven_epi_is_installment_enabled'] )
				? sanitize_text_field( wp_unslash( $_POST['_darven_epi_is_installment_enabled'] ) )
				: null;

			$product->update_meta_data(
				'_darven_epi_is_incash_enabled',
				$this->normalize_checkbox_value( $incash_value )
			);
			$product->update_meta_data(
				'_darven_epi_is_installment_enabled',
				$this->normalize_checkbox_value( $installment_value )
			);
		}

		/**
		 * Normalize a WooCommerce checkbox value.
		 *
		 * @param mixed $value Submitted checkbox value.
		 */
		private function normalize_checkbox_value( $value ): string {
			$value = sanitize_text_field( (string) $value );

			return 'yes' === $value ? 'yes' : 'no';
		}
	}
}

$darven = new Darven_Epi_Product_Options();
