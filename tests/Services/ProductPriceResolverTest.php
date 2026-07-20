<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class DarvenEpiYithFrontendTestDouble {
	public static $dynamic_price = null;

	public static function get_instance() {
		return new self();
	}

	public function get_dynamic_price( $price, $product, $quantity ) {
		return self::$dynamic_price;
	}
}

final class ProductPriceResolverTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();

		if ( class_exists( 'YWDPD_Frontend' ) && property_exists( 'YWDPD_Frontend', 'dynamic_price' ) ) {
			YWDPD_Frontend::$dynamic_price = null;
		}
	}

	public function test_returns_the_active_price_from_a_simple_product(): void {
		$resolver = $this->getResolver();

		self::assertSame( 149.90, $resolver->getActivePrice( new WC_Product( '149.90' ) ) );
	}

	public function test_uses_the_minimum_display_price_for_a_variable_product(): void {
		$resolver = $this->getResolver();
		$product  = new WC_Product( '0', 'variable', '89.50' );

		self::assertSame( 89.50, $resolver->getActivePrice( $product ) );
	}

	public function test_rejects_an_invalid_product(): void {
		$this->expectException( TypeError::class );

		$this->getResolver()->getActivePrice( null );
	}

	public function test_falls_back_to_the_woocommerce_price_when_yith_is_unavailable(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		self::assertSame( 75.25, $this->getResolver()->getActivePrice( new WC_Product( '75.25' ) ) );
	}

	public function test_prefers_a_valid_yith_dynamic_price_when_compatibility_is_enabled(): void {
		if ( ! class_exists( 'YWDPD_Frontend' ) ) {
			class_alias( DarvenEpiYithFrontendTestDouble::class, 'YWDPD_Frontend' );
		}

		YWDPD_Frontend::$dynamic_price = '65.50';
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		self::assertSame( 65.50, $this->getResolver()->getActivePrice( new WC_Product( '100.00', 'variable', '89.50' ) ) );
	}

	private function getResolver(): ProductPriceResolver {
		return new ProductPriceResolver( new SettingsRepository( new LegacySettingsAdapter() ) );
	}
}
