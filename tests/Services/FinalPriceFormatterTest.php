<?php

use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Frontend\InlineStyles;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\CashPriceFormatter;
use Darven\ExtraPriceInfo\Services\FinalPriceFormatter;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}

final class FinalPriceFormatterTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general'   => $this->getGeneralSettings(),
			'darven_epi_option_positions' => array(
				'darven_epi_single_product_position' => 'first',
			),
		);
	}

	public function test_formats_the_product_price_with_existing_markup(): void {
		$original_html = '<span class="amount">R$ 100.00</span>';

		$result = $this->getFormatter()->filter( $original_html, new WC_Product( '100.00' ) );

		self::assertStringContainsString( 'R$ 90.00', $result );
		self::assertStringContainsString( '4x de', $result );
		self::assertStringContainsString( 'darven-epi-incash-price-statement', $result );
		self::assertStringContainsString( 'darven-epi-installments-price-statement', $result );
		self::assertStringNotContainsString( ' id=', $result );
	}

	public function test_returns_original_html_for_an_invalid_product(): void {
		$original_html = '<span class="amount">R$ 100.00</span>';

		self::assertSame( $original_html, $this->getFormatter()->filter( $original_html, null ) );
	}

	public function test_honors_canonical_product_overrides(): void {
		$product = new WC_Product(
			'100.00',
			'simple',
			null,
			array(
				ProductSettingsRepository::META_KEY => array(
					'disable_incash'       => true,
					'disable_installments' => true,
				),
			)
		);
		$original_html = '<span class="amount">R$ 100.00</span>';

		self::assertSame( $original_html, $this->getFormatter()->filter( $original_html, $product ) );
	}

	public function test_does_not_treat_legacy_no_values_as_product_overrides(): void {
		$product = new WC_Product(
			'100.00',
			'simple',
			null,
			array(
				'_darven_epi_is_incash_enabled'      => 'no',
				'_darven_epi_is_installment_enabled' => 'no',
			)
		);

		$result = $this->getFormatter()->filter( '<span class="amount">R$ 100.00</span>', $product );

		self::assertStringContainsString( 'R$ 90.00', $result );
		self::assertStringContainsString( '4x de', $result );
	}

	/**
	 * @dataProvider positionProvider
	 */
	public function test_orders_the_original_cash_and_installment_markup( string $position, string $expected ): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_positions'] = array(
			'darven_epi_single_product_position' => $position,
		);

		self::assertSame( $expected, $this->getFormatter()->order( 'original', 'cash', 'installments' ) );
	}

	public function test_renders_inline_styles_from_legacy_display_settings_only(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_colorsandstyles' => array(
				'darven_epi_color_of_incash_prefix'              => '#111111',
				'darven_epi_font_size_of_incash_prefix'          => '1',
				'darven_epi_color_of_incash_price'               => '#123456',
				'darven_epi_font_size_of_incash_price'           => '1',
				'darven_epi_color_of_incash_suffix'              => '#222222',
				'darven_epi_font_size_of_incash_suffix'          => '1',
				'darven_epi_color_of_installments_prefix'        => '#333333',
				'darven_epi_font_size_of_installments_prefix'    => '1',
				'darven_epi_color_of_installments_install'       => '#444444',
				'darven_epi_font_size_of_installments_install'   => '1',
				'darven_epi_color_of_installments_price'         => '#555555',
				'darven_epi_font_size_of_installments_price'     => '1',
				'darven_epi_color_of_installments_suffix'        => '#666666',
				'darven_epi_font_size_of_installments_suffix'    => '1',
			),
		);

		$repository = new SettingsRepository( new LegacySettingsAdapter() );
		$styles     = ( new InlineStyles( $repository ) )->render();

		self::assertStringContainsString( '.darven-epi-incash-price', $styles );
		self::assertStringContainsString( 'color: #123456 !important;', $styles );
		self::assertStringContainsString( 'color: #555555;', $styles );
		self::assertStringNotContainsString( 'color: #555555 !important;', $styles );
		self::assertStringNotContainsString( '\\n', $styles );

		ob_start();
		require DARVEN_EPI_DIR_PATH . 'public/partials/darven-epi-custom-css.php';
		$partial_styles = ob_get_clean();

		self::assertStringContainsString( '.darven-epi-incash-price', $partial_styles );
		self::assertStringContainsString( 'color: #123456 !important;', $partial_styles );
	}

	public static function positionProvider(): array {
		return array(
			'first'  => array( 'first', 'originalcashinstallments' ),
			'second' => array( 'second', 'originalinstallmentscash' ),
			'third'  => array( 'third', 'cashoriginalinstallments' ),
			'fourth' => array( 'fourth', 'cashinstallmentsoriginal' ),
			'fifth'  => array( 'fifth', 'installmentsoriginalcash' ),
			'sixth'  => array( 'sixth', 'installmentscashoriginal' ),
		);
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

	private function getGeneralSettings(): array {
		return array(
			'darven_epi_incash_is_enabled'                     => 'darven_epi_incash_is_enabled',
			'darven_epi_value_of_incash_discount'              => '10',
			'darven_epi_minimum_incash_value'                  => '0',
			'darven_epi_incash_prefix'                         => 'por ',
			'darven_epi_incash_suffix'                         => ' à vista',
			'darven_epi_type_of_discount'                      => 'percent',
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
		);
	}
}
