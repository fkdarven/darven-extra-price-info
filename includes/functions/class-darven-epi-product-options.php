<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Product_Options' ) ) {
	class Darven_Epi_Product_Options {
		public function __construct() {


			// The code for displaying WooCommerce Product Custom Fields
			add_action( 'woocommerce_product_options_general_product_data', array(
				$this,
				'woocommerce_product_custom_fields'
			) );
// Following code Saves  WooCommerce Product Custom Fields
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_extra_prices' ) );
		}


		public function woocommerce_product_custom_fields() {
			global $woocommerce, $post;
			echo '<div class="product_custom_field">';
			// Custom Product Text Field
			woocommerce_wp_checkbox( array(
				'id'          => '_darven_epi_is_incash_enabled',
				'placeholder' => '',
				'label'       => __( 'Disable in cash price for this product', 'woocommerce' ),
				'type'        => 'boolean'
			) );
			//Custom Product Number Field
			woocommerce_wp_checkbox( array(
				'id'          => '_darven_epi_is_installment_enabled',
				'placeholder' => '',
				'label'       => __( 'Disable installments price for this product', 'woocommerce' ),
				'type'        => 'boolean',

			) );

			echo '</div>';


		}

		function save_extra_prices( $id ) {

			global $woocommerce, $post;
			update_post_meta( $id, '_darven_epi_is_incash_enabled', filter_input( 1, $_POST['_darven_epi_is_incash_enabled'] ) );
			update_post_meta( $id, '_darven_epi_is_installment_enabled', $_POST['_darven_epi_is_installment_enabled'] );


		}
	}
}

$darven = new Darven_Epi_Product_Options();