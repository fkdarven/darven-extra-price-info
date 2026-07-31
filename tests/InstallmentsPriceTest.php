<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
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

	public function test_formats_default_installment_markup(): void {
		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertStringContainsString( '4x de', $result );
		self::assertStringContainsString( 'R$ 25.00', $result );
		self::assertStringContainsString( 'darven-epi-installments-price-statement', $result );
	}

	public function test_default_mode_does_not_render_popup_markup(): void {
		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertStringNotContainsString( 'darven-epi-installments-toggle', $result );
		self::assertStringNotContainsString( 'darven-epi-installments-popup', $result );
		self::assertStringNotContainsString( 'role="dialog"', $result );
	}

	public function test_returns_installment_values_as_floats(): void {
		$subject = $this->getFormatter();
		$method  = new ReflectionMethod( InstallmentPriceFormatter::class, 'getInstallmentPrice' );

		self::assertSame( 'float', (string) $method->getReturnType() );
		self::assertSame( 25.0, $subject->getInstallmentPrice( 100.00 ) );
	}

	public function test_reads_current_settings_when_a_new_formatter_is_created(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general']['darven_epi_max_installments'] = '2';

		$subject = $this->getFormatter();

		self::assertSame( 50.0, $subject->getInstallmentPrice( 100.00 ) );
	}

	private function getFormatter(): InstallmentPriceFormatter {
		$repository = new SettingsRepository( new LegacySettingsAdapter() );

		return new InstallmentPriceFormatter(
			$repository,
			new ProductPriceResolver( $repository ),
			new PriceMarkupBuilder()
		);
	}
}
