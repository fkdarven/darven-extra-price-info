<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\CashPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class CashPriceFormatterTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array();
	}

	public function test_returns_empty_when_cash_configuration_is_missing(): void {
		self::assertSame( '', $this->getFormatter()->format( new WC_Product( '100.00' ) ) );
	}

	public function test_returns_empty_when_cash_is_not_enabled_by_the_exact_legacy_checkbox_value(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array(
			'darven_epi_incash_is_enabled' => 'yes',
		);

		self::assertSame( '', $this->getFormatter()->format( new WC_Product( '100.00' ) ) );
	}

	public function test_formats_percent_cash_price_with_the_existing_markup_classes(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array(
			'darven_epi_incash_is_enabled'       => 'darven_epi_incash_is_enabled',
			'darven_epi_value_of_incash_discount' => '10',
			'darven_epi_minimum_incash_value'     => '0',
			'darven_epi_incash_prefix'             => 'por ',
			'darven_epi_incash_suffix'             => ' à vista',
			'darven_epi_type_of_discount'          => 'percent',
		);

		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertSame(
			'<div class="incash-price-statement darven-epi-incash-price-statement incash-epi-single-product"><span class="incash-prefix darven-epi-incash-prefix incash-epi-single-product"> por  </span><span class="darven-epi-incash-price incash-epi-single-product"> R$ 90.00 </span><span class="incash-suffix darven-epi-incash-suffix incash-epi-single-product">  à vista </span></div>',
			$result
		);
		self::assertStringNotContainsString( ' id=', $result );
	}

	public function test_preserves_fixed_cash_discount_calculation(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array(
			'darven_epi_incash_is_enabled'       => 'darven_epi_incash_is_enabled',
			'darven_epi_value_of_incash_discount' => '10',
			'darven_epi_minimum_incash_value'     => '0',
			'darven_epi_incash_prefix'             => '',
			'darven_epi_incash_suffix'             => '',
			'darven_epi_type_of_discount'          => 'fixed',
		);

		self::assertStringContainsString( '> 90 </span>', $this->getFormatter()->format( new WC_Product( '100.00' ) ) );
	}

	private function getFormatter(): CashPriceFormatter {
		$repository = new SettingsRepository( new LegacySettingsAdapter() );

		return new CashPriceFormatter(
			$repository,
			new ProductPriceResolver( $repository ),
			new PriceMarkupBuilder()
		);
	}
}
