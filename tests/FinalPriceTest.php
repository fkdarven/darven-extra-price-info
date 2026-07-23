<?php

use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\CashPriceFormatter;
use Darven\ExtraPriceInfo\Services\FinalPriceFormatter;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
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

	public function test_formats_the_final_product_price(): void {
		$original_html = '<span class="amount">R$ 100.00</span>';

		$result = $this->getFormatter()->filter(
			$original_html,
			new WC_Product( '100.00' )
		);

		self::assertStringStartsWith( $original_html, $result );
		self::assertStringContainsString( 'R$ 90.00', $result );
		self::assertStringContainsString( 'darven-epi-incash-price-statement', $result );
	}

	private function getFormatter(): FinalPriceFormatter {
		$settings_repository = new SettingsRepository( new LegacySettingsAdapter() );
		$price_resolver      = new ProductPriceResolver( $settings_repository );
		$markup_builder      = new PriceMarkupBuilder();

		return new FinalPriceFormatter(
			new CashPriceFormatter( $settings_repository, $price_resolver, $markup_builder ),
			new InstallmentPriceFormatter( $settings_repository, $price_resolver, $markup_builder ),
			$settings_repository,
			new ProductSettingsRepository( new LegacyProductSettingsAdapter() )
		);
	}
}
