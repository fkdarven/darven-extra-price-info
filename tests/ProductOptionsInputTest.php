<?php

use Darven\ExtraPriceInfo\Admin\ProductOptionsController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use PHPUnit\Framework\TestCase;

final class ProductOptionsInputTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = true;
		$GLOBALS['darven_epi_test_nonce_is_valid']    = true;
		$_POST = array(
			'woocommerce_meta_nonce'             => 'valid-nonce',
			'_darven_epi_is_incash_enabled'      => 'yes<em></em>',
			'_darven_epi_is_installment_enabled' => 'no',
		);
	}

	public function test_sanitizes_checkbox_values_before_projecting_product_options(): void {
		$product = new WC_Product( '100.00' );

		$this->getSubject()->save( $product );

		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
	}

	private function getSubject(): ProductOptionsController {
		return new ProductOptionsController(
			new ProductSettingsRepository( new LegacyProductSettingsAdapter() )
		);
	}
}
