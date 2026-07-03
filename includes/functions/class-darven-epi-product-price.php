<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Product_Price' ) ) {
	/*
	 * Responsible for retrieving the product active price.
	 */

	class Darven_Epi_Product_Price {

		private $is_yith_compatibility_enabled;
		private $product;

		/**
		 * Construct method.
		 *
		 * @param WC_Product|null $product Product whose active price should be resolved.
		 */
		public function __construct( $product = null ) {

			$this->product = $product;
			$this->initiate_options();
		}

		/**
		 * Initiate options.
		 * @return void
		 */
		private function initiate_options(): void {
			$this->is_yith_compatibility_enabled = get_option( 'darven_epi_option_compatibility' )['darven_epi_is_yith_dynamic_compatibility_enabled'] ?? null;
		}

		/**
		 * Get active price. Either promotional or full.
		 * @return float
		 */
		public function get_active_price(): float {

			$product = $this->product;

			if ( ! $product instanceof WC_Product ) {
				return 0.0;
			}

			if ( $this->is_yith_compatibility_enabled && class_exists( 'YWDPD_Frontend' ) ) {
				$dynamic_price = YWDPD_Frontend::get_instance()->get_dynamic_price( $product->get_price(), $product, 1 );

				if ( is_numeric( $dynamic_price ) ) {
					return (float) $dynamic_price;
				}
			}

			if ( $product->is_type( 'variable' ) ) {
				return (float) $product->get_variation_price( 'min', true );
			}

			return (float) $product->get_price();
		}
	}

}
