<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class DarvenEpiYithFrontendTestDouble {
	public static $dynamic_price = null;
	public static $call_count = 0;
	public static $should_throw = false;

	public static function get_instance() {
		return new self();
	}

	public function get_dynamic_price( $price, $product, $quantity ) {
		self::$call_count++;

		if ( self::$should_throw ) {
			throw new RuntimeException( 'YITH dynamic pricing failed.' );
		}

		return self::$dynamic_price;
	}
}

final class ProductPriceResolverTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();

		if ( class_exists( 'YWDPD_Frontend' ) && property_exists( 'YWDPD_Frontend', 'dynamic_price' ) ) {
			YWDPD_Frontend::$dynamic_price = null;
			YWDPD_Frontend::$call_count    = 0;
			YWDPD_Frontend::$should_throw  = false;
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
		self::assertSame( 75.25, $this->getResolver()->getActivePrice( new WC_Product( '75.25' ) ) );
	}

	public function test_uses_yith_automatically_without_saved_settings_on_a_clean_install(): void {
		$this->enableYithDoubleWithPrice( '65.50' );

		self::assertSame( 65.50, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
	}

	public function test_never_calls_yith_when_the_explicit_mode_is_disabled(): void {
		$this->enableYithDoubleWithPrice( '65.50' );
		$this->setCanonicalCompatibilityMode( 'disabled' );

		self::assertSame( 100.00, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
		self::assertSame( 0, YWDPD_Frontend::$call_count );
	}

	public function test_falls_back_when_yith_throws(): void {
		$this->enableYithDoubleWithPrice( '65.50' );
		YWDPD_Frontend::$should_throw = true;

		self::assertSame( 100.00, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
	}

	public function test_falls_back_when_yith_returns_an_invalid_dynamic_price(): void {
		$this->enableYithDoubleWithPrice( 'not-a-price' );

		self::assertSame( 100.00, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
	}

	public function test_accepts_zero_as_a_valid_yith_dynamic_price(): void {
		$this->enableYithDoubleWithPrice( '0' );

		self::assertSame( 0.0, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
	}

	private function getResolver(): ProductPriceResolver {
		return new ProductPriceResolver( new SettingsRepository( new LegacySettingsAdapter() ) );
	}

	private function enableYithDoubleWithPrice( $price ): void {
		if ( ! class_exists( 'YWDPD_Frontend' ) ) {
			class_alias( DarvenEpiYithFrontendTestDouble::class, 'YWDPD_Frontend' );
		}

		YWDPD_Frontend::$dynamic_price = $price;
	}

	private function setCanonicalCompatibilityMode( string $mode ): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_settings'] = array(
			'schema_version' => 1,
			'general'        => array(),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array(
				'darven_epi_yith_dynamic_pricing_mode' => $mode,
			),
		);
	}
}
