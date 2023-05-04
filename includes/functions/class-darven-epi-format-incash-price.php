<?php
/*
 *
 * Class responsible for handling incash prices. Special functions for grouped and variable prices
 * In case of a product being bundled/variable, the price considered is always the lowest
 */
//namespace Darven\Epi\Includes\Functions\Incash;

defined( 'ABSPATH' ) || exit();
require 'class-darven-epi-product-price.php';

if ( ! class_exists( 'Darven_Epi_Format_Incash_Price' ) ) {
	class Darven_Epi_Format_Incash_Price {

		private string $value_of_discount;
		private string $incash_prefix;
		private string $incash_suffix;
		private string $minimum_price;
		private string $type_of_discount;

		public function __construct($overwrite_price = null) {


			if ( ! isset( get_option( 'darven_epi_option_general' )['darven_epi_incash_is_enabled'] ) || ! get_option( 'darven_epi_option_general' )['darven_epi_incash_is_enabled'] ) {

				return;

			}

			$this->initiate_options();

		}

		public function initiate_options(): void {

			$this->value_of_discount = get_option( 'darven_epi_option_general' )['darven_epi_value_of_incash_discount'];
			$this->minimum_price     = get_option( 'darven_epi_option_general' )['darven_epi_minimum_incash_value'] ?? 0;
			$this->incash_prefix     = get_option( 'darven_epi_option_general' )['darven_epi_incash_prefix'];
			$this->incash_suffix     = get_option( 'darven_epi_option_general' )['darven_epi_incash_suffix'];
			$this->type_of_discount  = get_option( 'darven_epi_option_general' )['darven_epi_type_of_discount'];
		}

		public function get_discount_price(): string {
			$darven_product_price = new Darven_Epi_Product_Price();
			$clean_price  = $darven_product_price->get_active_price();
			$incash_price = $this->get_incash_price( $clean_price );


			if ( $clean_price <= $this->minimum_price ) {
				return '';
			}

			return '<div id="incash-price-statement"><span id="incash-prefix">' . $this->incash_prefix . '</span><span id="incash-price"> ' . $incash_price . '</span><span id="incash-suffix"> ' . $this->incash_suffix . '</span></div>';
		}

		public function get_incash_price( $price ): string {

			if ( $price <= $this->minimum_price ) {
				return strip_tags( wc_price( $price ) );
			}
			$price = (float) $price;

			if ( $this->type_of_discount === 'fixed' ) {
				if ( $price - $this->value_of_discount <= 0 ) {
					return $price;
				}

				return $price - $this->value_of_discount;
			}
			$value_of_discount = ( (int) $this->value_of_discount ) / 100;


			$final_price = $price - ( $price * $value_of_discount );
			$final_price = round( $final_price, 2 );

			return strip_tags( wc_price( $final_price ) );
		}


	}
}

$Darven_Epi_Format_Incash_Price = new Darven_Epi_Format_Incash_Price();