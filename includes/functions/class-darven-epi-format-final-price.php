<?php


defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'Darven_Epi_Format_Final_Price' ) ) {

	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-incash-price.php';
	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-installments-price.php';


	class Darven_Epi_Format_Final_Price {
		public function __construct() {


			//add_filter( 'woocommerce_get_price_html', array( $this, 'get_discount_price' ), 2000 );
		}

		public function get_discount_price( $price ): string {


			if ( is_admin() || is_cart() || is_checkout() ) {
				return $price;
			}

			$product = get_post();
			//if($product->ID){

			$disable_incash      = get_post_meta( $product->ID, '_darven_epi_is_incash_enabled', true );
			$disable_installment = get_post_meta( $product->ID, '_darven_epi_is_installment_enabled', true );

			//}


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


			//return ( $installments_statement .'<br>'. $price . $incash_statement );

			return $this->get_ordination( $price, $incash_statement, $installments_statement );
		}

		public function get_ordination( $price, $incash_statement, $installments_statement ): string {

			if ( is_product() ) {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_single_product_position'] ?? null;
			} elseif ( is_product_category() ) {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_catalog_product_position'] ?? null;
			} else {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_others_product_position'] ?? null;
			}


			switch ( $order ) {
				case 'second':
					return ( $price . $installments_statement . $incash_statement );
				case 'third':
					return ( $incash_statement . $price . $installments_statement );
				case 'fourth':
					return ( $incash_statement . $installments_statement . $price );
				case 'fifth':
					return ( $installments_statement . $price . $incash_statement );
				case 'sixth':
					return ( $installments_statement . $incash_statement . $price );
				default:
					return ( $price . $incash_statement . $installments_statement );

			}
		}
	}
}
