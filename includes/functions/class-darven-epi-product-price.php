<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Product_Price' ) ) {
	class Darven_Epi_Product_Price {
		/**
		 * @var Darven\ExtraPriceInfo\Services\ProductPriceResolver
		 */
		private $resolver;

		/**
		 * @var WC_Product|null
		 */
		private $product;

		/**
		 * @param WC_Product|null $product Product whose active price should be resolved.
		 */
		public function __construct( $product = null ) {
			$this->product  = $product;
			$this->resolver = new \Darven\ExtraPriceInfo\Services\ProductPriceResolver(
				new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
					new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
				)
			);
		}

		public function get_active_price(): float {
			if ( ! $this->product instanceof WC_Product ) {
				return 0.0;
			}

			return $this->resolver->getActivePrice( $this->product );
		}
	}
}
