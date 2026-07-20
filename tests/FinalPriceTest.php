<?php

use PHPUnit\Framework\TestCase;

final class FinalPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general'   => array(
				'darven_epi_incash_is_enabled'                    => 'darven_epi_incash_is_enabled',
				'darven_epi_value_of_incash_discount'             => '10',
				'darven_epi_minimum_incash_value'                 => '0',
				'darven_epi_incash_prefix'                        => 'por ',
				'darven_epi_incash_suffix'                        => ' à vista',
				'darven_epi_type_of_discount'                     => 'percent',
			),
			'darven_epi_option_positions' => array(
				'darven_epi_single_product_position' => 'first',
			),
		);
	}

	public function test_legacy_shim_delegates_final_price_formatting(): void {
		$original_html = '<span class="amount">R$ 100.00</span>';

		$result = ( new Darven_Epi_Format_Final_Price() )->get_discount_price(
			$original_html,
			new WC_Product( '100.00' )
		);

		self::assertStringStartsWith( $original_html, $result );
		self::assertStringContainsString( 'R$ 90.00', $result );
		self::assertStringContainsString( 'darven-epi-incash-price-statement', $result );
	}
}
