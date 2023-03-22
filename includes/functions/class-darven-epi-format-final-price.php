<?php


if ( ! class_exists( 'Darven_Epi_Format_Final_Price' ) ) {
	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-incash-price.php';
	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-installments-price.php';
	class Darven_Epi_Format_Final_Price {
		public function __construct() {


			//add_filter( 'woocommerce_get_price_html', array( $this, 'get_discount_price' ) );
		}

		public function get_discount_price( $price ): string {

			if ( is_admin() || ( ! is_woocommerce() && ! is_product() && ! is_home() && ! is_product_category() ) ) {
				return $price;
			}
			$product             = get_post();
			$disable_incash      = get_post_meta( $product->ID, '_darven_epi_is_incash_enabled', true );
			$disable_installment = get_post_meta( $product->ID, '_darven_epi_is_installment_enabled', true );


			$incash                 = new Darven_Epi_Format_Incash_Price();
			$installments           = new Darven_Epi_Format_Installments_Price();
			$incash_statement       = '';
			$installments_statement = '';

			if ( ! $disable_incash && count( (array) $incash ) > 0 ) {
				$incash_statement = $incash->get_discount_price();
			}
			if ( ! $disable_installment && count( (array) $installments ) > 0 ) {
				$installments_statement = $installments->get_discount_price();
			}


			return ( $price . $incash_statement . $installments_statement );
		}
	}
}
