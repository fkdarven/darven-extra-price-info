<?php
/*
 * Legacy compatibility shim for installment prices.
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Format_Installments_Price' ) ) {
	class Darven_Epi_Format_Installments_Price {
		/**
		 * @var Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter
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

			$settings_repository = new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
				new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
			);
			$this->formatter = new \Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter(
				$settings_repository,
				new \Darven\ExtraPriceInfo\Services\ProductPriceResolver( $settings_repository ),
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

		public function get_installments_price( $price ) {
			return $this->formatter->getInstallmentPrice( (float) $price );
		}

		public function get_price_table( $price ): array {
			return $this->formatter->getPriceTable( (float) $price );
		}

		public function get_tax_calculation( $price, $mode, $installment ): float {
			return $this->formatter->getTaxCalculation( (float) $price, (string) $mode, (int) $installment );
		}
	}
}
