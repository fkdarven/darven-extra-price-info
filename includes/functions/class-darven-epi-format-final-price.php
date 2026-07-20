<?php
/**
 * Legacy compatibility shim for the final product price filter.
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Format_Final_Price' ) ) {
	require_once DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-incash-price.php';
	require_once DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-installments-price.php';

	class Darven_Epi_Format_Final_Price {
		/**
		 * @var Darven\ExtraPriceInfo\Services\FinalPriceFormatter
		 */
		private $formatter;

		public function __construct() {
			$settings_repository = new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
				new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
			);
			$price_resolver = new \Darven\ExtraPriceInfo\Services\ProductPriceResolver( $settings_repository );
			$markup_builder = new \Darven\ExtraPriceInfo\Services\PriceMarkupBuilder();

			$this->formatter = new \Darven\ExtraPriceInfo\Services\FinalPriceFormatter(
				new \Darven\ExtraPriceInfo\Services\CashPriceFormatter(
					$settings_repository,
					$price_resolver,
					$markup_builder
				),
				new \Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter(
					$settings_repository,
					$price_resolver,
					$markup_builder
				),
				$settings_repository,
				new \Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository(
					new \Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter()
				)
			);
		}

		/**
		 * @param string          $price Product price HTML.
		 * @param WC_Product|null $product Product supplied by WooCommerce.
		 */
		final public function get_discount_price( $price, $product = null ): string {
			return $this->formatter->filter( (string) $price, $product );
		}

		final public function get_ordination( $price, $incash_statement, $installments_statement ): string {
			return $this->formatter->order(
				(string) $price,
				(string) $incash_statement,
				(string) $installments_statement
			);
		}
	}
}
