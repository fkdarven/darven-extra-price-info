<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Format_Incash_Price' ) ) {
	class Darven_Epi_Format_Incash_Price {
		/**
		 * @var Darven\ExtraPriceInfo\Services\CashPriceFormatter
		 */
		private $formatter;

		/**
		 * @var WC_Product|null
		 */
		private $product;

		/**
		 * @param WC_Product|null $product Product whose price should be formatted.
		 */
		public function __construct( $product = null ) {
			$this->product = $product;

			$repository      = new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
				new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
			);
			$this->formatter = new \Darven\ExtraPriceInfo\Services\CashPriceFormatter(
				$repository,
				new \Darven\ExtraPriceInfo\Services\ProductPriceResolver( $repository ),
				new \Darven\ExtraPriceInfo\Services\PriceMarkupBuilder()
			);
		}

		public function initiate_options(): void {
		}

		public function get_discount_price(): string {
			if ( ! $this->product instanceof WC_Product ) {
				return '';
			}

			return $this->formatter->format( $this->product );
		}

		public function get_incash_price( $price ): string {
			return $this->formatter->formatPrice( $price );
		}
	}
}
