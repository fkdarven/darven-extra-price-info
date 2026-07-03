<?php

use PHPUnit\Framework\TestCase;

final class InstallmentsPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general' => $this->get_default_options(),
		);
	}

	public function test_calculates_installments_from_the_given_product(): void {
		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Format_Installments_Price( $product );

		$result = $subject->get_discount_price();

		self::assertStringContainsString( '4x de', $result );
		self::assertStringContainsString( 'R$ 25.00', $result );
	}

	public function test_custom_interest_rates_are_aligned_without_mutating_the_starting_installment(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array_merge(
			$this->get_default_options(),
			array(
				'darven_epi_installments_interest_fee_is_table_enabled' => 'darven_epi_installments_interest_fee_is_table_enabled',
				'darven_epi_installments_interest_fee_from'             => '3',
				'darven_epi_installments_interest_fee_table'            => '5|7',
			)
		);

		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Format_Installments_Price( $product );

		$subject->get_installments_price( 100.00 );
		$first_result  = $subject->get_price_table( 100.00 );
		$second_result = $subject->get_price_table( 100.00 );

		self::assertStringContainsString( '<td>2x de</td><td>R$ 50.00</td>', $first_result[0] );
		self::assertStringContainsString( '<td>3x de</td><td>R$ 35.00</td>', $first_result[0] );
		self::assertStringContainsString( '<td>4x de</td><td>R$ 26.75</td>', $first_result[0] );
		self::assertSame( $first_result, $second_result );
	}

	private function get_default_options(): array {
		return array(
			'darven_epi_installments_is_enabled'                  => 'darven_epi_installments_is_enabled',
			'darven_epi_max_installments'                         => '4',
			'darven_epi_minimum_installments_value'               => '1',
			'darven_epi_installments_prefix'                      => '',
			'darven_epi_installments_suffix'                      => '',
			'darven_epi_installments_interest_fee'                => '0',
			'darven_epi_installments_interest_fee_from'           => '0',
			'darven_epi_installments_interest_fee_first_install' => '0',
			'darven_epi_mode_of_view'                             => 'default',
			'darven_epi_popup_text'                               => '',
		);
	}
}
