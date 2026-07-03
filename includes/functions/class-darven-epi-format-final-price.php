<?php
/**
 * Class Darven_Epi_Format_Final_Price
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Format_Final_Price' ) ) {

	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-incash-price.php';
	require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-installments-price.php';

	/**
	 * Class responsible for handling everything related to price formatting.
	 */
	class Darven_Epi_Format_Final_Price {

		/**
		 * Method responsible for getting the product price with discount. Ignored if in the admin, checkout or cart page.
		 *
		 * @param float $price product price.
		 *
		 * @return string
		 */
		final public function get_discount_price( $price ): string {

			if ( is_admin() || is_checkout() || is_cart() ) {
				return $price;
			}

			$product = get_post();

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

			return $this->get_ordination( $price, $incash_statement, $installments_statement );
		}


		/**
		 * Responsible for getting the ordination for which prices appears in each position.
		 *
		 * @param float $price produt price.
		 * @param string $incash_statement incash statement.
		 * @param string $installments_statement installments statement.
		 *
		 * @return string
		 */
		final public function get_ordination( $price, $incash_statement, $installments_statement ): string {

			if ( is_product() ) {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_single_product_position'] ?? null;
			} elseif ( is_product_category() ) {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_catalog_product_position'] ?? null;
			} else {
				$order = get_option( 'darven_epi_option_positions' )['darven_epi_others_product_position'] ?? null;
			}

			switch ( $order ) {
				case 'second':
					return ( '<div>' . $price . $installments_statement . $incash_statement . '</div>' );
				case 'third':
					return ( '<div>' . $incash_statement . $price . $installments_statement . '</div>' );
				case 'fourth':
					return ( '<div>' . $incash_statement . $installments_statement . $price . '</div>' );
				case 'fifth':
					return ( '<div>' . $installments_statement . $price . $incash_statement . '</div>' );
				case 'sixth':
					return ( '<div>' . $installments_statement . $incash_statement . $price . '</div>' );
				default:
					return ( '<div>' . $price . $incash_statement . $installments_statement . '</div>' );

			}

		}
	}
}
