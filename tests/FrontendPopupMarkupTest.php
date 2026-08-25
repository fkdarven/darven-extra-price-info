<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

final class FrontendPopupMarkupTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_unique_id'] = 0;
	}

	/**
	 * @dataProvider popupModeProvider
	 */
	public function test_popup_modes_render_an_accessible_hidden_dialog( string $mode ): void {
		$result = $this->format( $mode, '<span class="highlight">View</span> <a href="/plans">plans</a> <script>alert(1)</script> installments' );

		self::assertMatchesRegularExpression(
			'/<button type="button" class="darven-epi-installments-toggle" aria-expanded="false" aria-controls="([^"]+)" aria-label="View plans alert\(1\) installments"><span class="highlight">View<\/span> plans alert\(1\) installments<\/button>/',
			$result
		);
		self::assertMatchesRegularExpression(
			'/<div id="darven-epi-installments-[0-9a-f-]+-dialog" class="messagepop pop darven-epi-installments-popup" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="darven-epi-installments-[0-9a-f-]+-title">/', $result
		);
		self::assertStringContainsString( 'class="darven-epi-installments-backdrop"', $result );
		self::assertStringContainsString( 'class="screen-reader-text darven-epi-installments-title"', $result );
		self::assertStringContainsString( 'class="darven-epi-installments-close"', $result );
		self::assertStringContainsString( 'aria-label="Close installment options"', $result );
		self::assertStringContainsString( 'installments_table darven-epi-installments-table', $result );
		self::assertStringNotContainsString( '<a ', $result );
		self::assertStringNotContainsString( '<script>', $result );
		self::assertStringNotContainsString( 'onclick=', $result );
		self::assertStringNotContainsString( 'onkeydown=', $result );
	}

	/**
	 * @dataProvider popupModeProvider
	 */
	public function test_popup_modes_fall_back_to_an_accessible_trigger_name( string $mode ): void {
		$result = $this->format( $mode, '' );

		self::assertStringContainsString( 'aria-label="View installment options"', $result );
		self::assertStringContainsString( '>View installment options</button>', $result );
	}

	public function test_popup_ids_use_wordpress_uuids_with_an_unavailable_function_fallback(): void {
		$source = file_get_contents( DARVEN_EPI_DIR_PATH . 'src/Services/InstallmentPriceFormatter.php' );

		self::assertIsString( $source );
		self::assertStringContainsString( "function_exists( 'wp_generate_uuid4' )", $source );
		self::assertStringContainsString( 'wp_generate_uuid4()', $source );
		self::assertStringContainsString( "uniqid( '', true )", $source );
		self::assertStringNotContainsString( "function_exists( 'wp_unique_id' )", $source );
	}

	public function test_each_formatted_popup_uses_distinct_linked_ids(): void {
		$formatter = $this->getFormatter( 'popup', 'View installments' );
		$first     = $formatter->format( new WC_Product( '100.00' ) );
		$second    = $formatter->format( new WC_Product( '100.00' ) );

		preg_match( '/aria-controls="([^"]+)"/', $first, $first_controls );
		preg_match( '/aria-controls="([^"]+)"/', $second, $second_controls );

		self::assertNotEmpty( $first_controls[1] ?? '' );
		self::assertNotEmpty( $second_controls[1] ?? '' );
		self::assertNotSame( $first_controls[1], $second_controls[1] );
		self::assertStringContainsString( 'id="' . $first_controls[1] . '"', $first );
		self::assertStringContainsString( 'id="' . $second_controls[1] . '"', $second );
	}

	public static function popupModeProvider(): array {
		return array(
			'maximum installments popup' => array( 'popup' ),
			'no-interest popup'          => array( 'nofee' ),
		);
	}

	private function format( string $mode, string $popup_text ): string {
		return $this->getFormatter( $mode, $popup_text )->format( new WC_Product( '100.00' ) );
	}

	private function getFormatter( string $mode, string $popup_text ): InstallmentPriceFormatter {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general' => array(
				'darven_epi_installments_is_enabled'                 => 'darven_epi_installments_is_enabled',
				'darven_epi_max_installments'                        => '4',
				'darven_epi_minimum_installments_value'              => '1',
				'darven_epi_installments_prefix'                     => '',
				'darven_epi_installments_suffix'                     => '',
				'darven_epi_installments_interest_fee'               => '0',
				'darven_epi_installments_interest_fee_from'          => '3',
				'darven_epi_installments_interest_fee_first_install' => '0',
				'darven_epi_mode_of_view'                            => $mode,
				'darven_epi_popup_text'                              => $popup_text,
			),
		);

		$repository = new SettingsRepository( new LegacySettingsAdapter() );

		return new InstallmentPriceFormatter(
			$repository,
			new ProductPriceResolver( $repository ),
			new PriceMarkupBuilder()
		);
	}
}
