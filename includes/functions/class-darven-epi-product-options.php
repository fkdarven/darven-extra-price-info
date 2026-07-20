<?php
/**
 * Legacy product-options compatibility shim.
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

use Darven\ExtraPriceInfo\Admin\ProductOptionsController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;

if ( ! class_exists( 'Darven_Epi_Product_Options' ) ) {
	class Darven_Epi_Product_Options {
		/**
		 * @var ProductOptionsController
		 */
		private $controller;

		public function __construct() {
			$this->controller = new ProductOptionsController(
				new ProductSettingsRepository( new LegacyProductSettingsAdapter() )
			);
			$this->controller->register();
		}

		public function woocommerce_product_custom_fields(): void {
			$this->controller->renderFields();
		}

		/**
		 * @param WC_Product|null $product Product being saved by WooCommerce.
		 */
		public function save_extra_prices( $product ): void {
			if ( ! $product instanceof WC_Product ) {
				return;
			}

			$this->controller->save( $product );
		}
	}
}

$darven = new Darven_Epi_Product_Options();
