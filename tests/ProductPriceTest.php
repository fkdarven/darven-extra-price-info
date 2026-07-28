<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class ProductPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();

		if ( class_exists( 'YWDPD_Frontend' ) && property_exists( 'YWDPD_Frontend', 'dynamic_price' ) ) {
			YWDPD_Frontend::$dynamic_price = null;
		}
	}

	public function test_returns_the_active_price_from_the_given_simple_product(): void {
		$product = new WC_Product( '149.90' );
		$subject = $this->getResolver();

		self::assertSame( 149.90, $subject->getActivePrice( $product ) );
	}

	public function test_uses_the_minimum_active_price_for_a_variable_product(): void {
		$product = new WC_Product( '0', 'variable', '89.50' );
		$subject = $this->getResolver();

		self::assertSame( 89.50, $subject->getActivePrice( $product ) );
	}

	public function test_requires_a_woocommerce_product(): void {
		$this->expectException( TypeError::class );

		$this->getResolver()->getActivePrice( null );
	}

	public function test_falls_back_to_the_woocommerce_price_when_yith_is_unavailable(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		$product = new WC_Product( '75.25' );
		$subject = $this->getResolver();

		self::assertSame( 75.25, $subject->getActivePrice( $product ) );
	}

	private function getResolver(): ProductPriceResolver {
		return new ProductPriceResolver( new SettingsRepository( new LegacySettingsAdapter() ) );
	}
}
