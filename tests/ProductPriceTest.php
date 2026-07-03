<?php

use PHPUnit\Framework\TestCase;

final class ProductPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();
	}

	public function test_returns_the_active_price_from_the_given_simple_product(): void {
		$product = new WC_Product( '149.90' );
		$subject = new Darven_Epi_Product_Price( $product );

		self::assertSame( 149.90, $subject->get_active_price() );
	}

	public function test_uses_the_minimum_active_price_for_a_variable_product(): void {
		$product = new WC_Product( '0', 'variable', '89.50' );
		$subject = new Darven_Epi_Product_Price( $product );

		self::assertSame( 89.50, $subject->get_active_price() );
	}

	public function test_returns_zero_when_no_valid_product_is_available(): void {
		$subject = new Darven_Epi_Product_Price();

		self::assertSame( 0.0, $subject->get_active_price() );
	}

	public function test_falls_back_to_the_woocommerce_price_when_yith_is_unavailable(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		$product = new WC_Product( '75.25' );
		$subject = new Darven_Epi_Product_Price( $product );

		self::assertSame( 75.25, $subject->get_active_price() );
	}
}
