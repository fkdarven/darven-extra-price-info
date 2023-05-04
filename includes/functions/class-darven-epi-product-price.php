<?php

defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'Darven_Epi_Product_Price' ) ) {
	class Darven_Epi_Product_Price {

		private $is_yith_compatibility_enabled;

		public function __construct() {

			$this->initiate_options();
		}

		private function initiate_options(): void {
			$this->is_yith_compatibility_enabled = get_option( 'darven_epi_option_compatibility' )['darven_epi_is_yith_dynamic_compatibility_enabled'] ?? null;
		}

		public function get_active_price(): float {

			global $product;

			if ( $this->is_yith_compatibility_enabled ) {
				return YWDPD_Frontend::get_instance()->get_dynamic_price( $product->get_price(), $product, 1 );
			}

			if ( is_null( $product ) ) {
				return 0;
			}
			if ( is_checkout() || is_cart() ) {
				return $product->get_price();
			}

			if ( $product->is_type( 'variable' ) ) {

				$sale_price    = $product->get_variation_sale_price( 'min', true );
				$regular_price = $product->get_variation_regular_price( 'max', true );
				if ( $sale_price ) {
					return $sale_price;
				}

				return $regular_price;
			}

			$regular_price = (float) $product->get_price();
			$sale_price    = (float) $product->get_sale_price();

			if ( $sale_price ) {
				return $sale_price;
			}

			return $regular_price;
		}
	}

}