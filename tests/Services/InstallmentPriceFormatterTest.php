<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class InstallmentPriceFormatterTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options']      = array(
			'darven_epi_option_general' => $this->getDefaultGeneralSettings(),
		);
		$GLOBALS['darven_epi_test_option_reads'] = array();
		$GLOBALS['darven_epi_test_translations']  = array();
	}

	public function test_formats_default_installments_with_the_existing_markup(): void {
		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertStringContainsString( '4x of', $result );
		self::assertStringContainsString( 'R$ 25.00', $result );
		self::assertStringContainsString( 'darven-epi-installments-price-statement', $result );
	}

	public function test_reads_normalized_general_settings_once(): void {
		$this->getFormatter();

		self::assertSame(
			1,
			count(
				array_keys(
					array_filter(
						$GLOBALS['darven_epi_test_option_reads'],
						static function ( $option_name ): bool {
							return 'darven_epi_option_general' === $option_name;
						}
					)
				)
			)
		);
	}

	public function test_custom_interest_table_is_repeatable_without_mutating_the_starting_price(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array_merge(
			$this->getDefaultGeneralSettings(),
			array(
				'darven_epi_installments_interest_fee_is_table_enabled' => 'darven_epi_installments_interest_fee_is_table_enabled',
				'darven_epi_installments_interest_fee_from'             => '3',
				'darven_epi_installments_interest_fee_table'            => '5|7',
			)
		);

		$formatter     = $this->getFormatter();
		$first_result  = $formatter->getPriceTable( 100.00 );
		$second_result = $formatter->getPriceTable( 100.00 );

		self::assertStringContainsString( '<td>2x de</td><td>R$ 50.00</td>', $first_result[0] );
		self::assertStringContainsString( '<td>3x de</td><td>R$ 35.00</td>', $first_result[0] );
		self::assertStringContainsString( '<td>4x de</td><td>R$ 26.75</td>', $first_result[0] );
		self::assertSame( $first_result, $second_result );
	}

	public function test_nofee_popup_preserves_legacy_component_classes(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array_merge(
			$this->getDefaultGeneralSettings(),
			array(
				'darven_epi_installments_interest_fee_from' => '5',
				'darven_epi_mode_of_view'                   => 'nofee',
				'darven_epi_popup_text'                     => 'View installments',
			)
		);

		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertStringContainsString( 'installments-price-statement darven-epi-installments-price-statement', $result );
		self::assertStringContainsString( 'messagepop pop darven-epi-installments-popup', $result );
		self::assertStringContainsString( 'installments_table darven-epi-installments-table', $result );
		self::assertStringContainsString( 'darven-epi-installments-table', $result );
		self::assertStringContainsString( 'darven-epi-installments-popup', $result );
		self::assertStringContainsString( 'class="darven-epi-installments-toggle"', $result );
	}

	public function test_installment_connector_is_translatable_with_the_count_placeholder(): void {
		$GLOBALS['darven_epi_test_translations']['%1$sx of'] = '%1$s installments';

		$result = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		self::assertStringContainsString( '>4 installments</span>', $result );
	}

	public function test_independent_popup_renders_use_distinct_cross_request_ids(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array_merge(
			$this->getDefaultGeneralSettings(), array(
				'darven_epi_mode_of_view' => 'popup',
				'darven_epi_popup_text'   => 'View installments',
			)
		);

		$GLOBALS['darven_epi_test_unique_id'] = 0;
		$first = $this->getFormatter()->format( new WC_Product( '100.00' ) );
		$GLOBALS['darven_epi_test_unique_id'] = 0;
		$second = $this->getFormatter()->format( new WC_Product( '100.00' ) );

		preg_match( '/aria-controls="([^"]+)"/', $first, $first_controls );
		preg_match( '/aria-controls="([^"]+)"/', $second, $second_controls );

		self::assertNotEmpty( $first_controls[1] ?? '' );
		self::assertNotEmpty( $second_controls[1] ?? '' );
		self::assertNotSame( $first_controls[1], $second_controls[1] );
		self::assertStringContainsString( 'id="' . $first_controls[1] . '"', $first );
		self::assertStringContainsString( 'id="' . $second_controls[1] . '"', $second );
	}

	public function test_installments_table_has_balanced_rows(): void {
		$price_table = $this->getFormatter()->getPriceTable( 100.00 );

		self::assertSame( 4, substr_count( $price_table[0], '<tr>' ) );
		self::assertSame( 4, substr_count( $price_table[0], '</tr>' ) );
	}

	private function getFormatter(): InstallmentPriceFormatter {
		$repository = new SettingsRepository( new LegacySettingsAdapter() );

		return new InstallmentPriceFormatter(
			$repository,
			new ProductPriceResolver( $repository ),
			new PriceMarkupBuilder()
		);
	}

	private function getDefaultGeneralSettings(): array {
		return array(
			'darven_epi_installments_is_enabled'                  => 'darven_epi_installments_is_enabled',
			'darven_epi_max_installments'                         => '4',
			'darven_epi_minimum_installments_value'               => '1',
			'darven_epi_installments_prefix'                      => '',
			'darven_epi_installments_suffix'                      => '',
			'darven_epi_installments_interest_fee'                => '0',
			'darven_epi_installments_interest_fee_from'           => '0',
			'darven_epi_installments_interest_fee_first_install'  => '0',
			'darven_epi_mode_of_view'                             => 'default',
			'darven_epi_popup_text'                               => '',
		);
	}
}
