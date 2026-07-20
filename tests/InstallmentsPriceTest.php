<?php

use PHPUnit\Framework\TestCase;

final class InstallmentsPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general' => array(
				'darven_epi_installments_is_enabled'               => 'darven_epi_installments_is_enabled',
				'darven_epi_max_installments'                      => '4',
				'darven_epi_minimum_installments_value'            => '1',
				'darven_epi_installments_prefix'                   => '',
				'darven_epi_installments_suffix'                   => '',
				'darven_epi_installments_interest_fee'             => '0',
				'darven_epi_installments_interest_fee_from'        => '0',
				'darven_epi_installments_interest_fee_first_install' => '0',
				'darven_epi_mode_of_view'                          => 'default',
				'darven_epi_popup_text'                            => '',
			),
		);
	}

	public function test_legacy_shim_delegates_default_installment_markup(): void {
		$result = ( new Darven_Epi_Format_Installments_Price( new WC_Product( '100.00' ) ) )->get_discount_price();

		self::assertStringContainsString( '4x de', $result );
		self::assertStringContainsString( 'R$ 25.00', $result );
		self::assertStringContainsString( 'darven-epi-installments-price-statement', $result );
	}

	public function test_legacy_shim_keeps_the_nullable_string_installment_value_contract(): void {
		$subject = new Darven_Epi_Format_Installments_Price( new WC_Product( '100.00' ) );
		$method  = new ReflectionMethod( Darven_Epi_Format_Installments_Price::class, 'get_installments_price' );

		self::assertSame( '?string', (string) $method->getReturnType() );
		self::assertSame( '25', $subject->get_installments_price( 100.00 ) );
	}

	public function test_legacy_shim_reloads_settings_when_initiated_again(): void {
		$subject = new Darven_Epi_Format_Installments_Price( new WC_Product( '100.00' ) );
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general']['darven_epi_max_installments'] = '2';

		$subject->initiate_options();

		self::assertSame( '50', $subject->get_installments_price( 100.00 ) );
	}
}
