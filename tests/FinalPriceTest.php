<?php

use PHPUnit\Framework\TestCase;

final class FinalPriceTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options'] = array(
			'darven_epi_option_general'   => array(
				'darven_epi_incash_is_enabled'       => 'darven_epi_incash_is_enabled',
				'darven_epi_value_of_incash_discount' => '10',
				'darven_epi_minimum_incash_value'     => '0',
				'darven_epi_incash_prefix'             => 'por ',
				'darven_epi_incash_suffix'             => ' à vista',
				'darven_epi_type_of_discount'          => 'percent',
			),
			'darven_epi_option_positions' => array(
				'darven_epi_single_product_position' => 'first',
			),
		);
	}

	public function test_calculates_the_extra_price_from_the_product_given_by_woocommerce(): void {
		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Format_Final_Price();

		$result = $subject->get_discount_price( '<span class="amount">R$ 100.00</span>', $product );

		self::assertStringContainsString( 'R$ 90.00', $result );
		self::assertStringContainsString( 'R$ 100.00', $result );
	}

	public function test_does_not_render_for_an_invalid_product(): void {
		$subject       = new Darven_Epi_Format_Final_Price();
		$original_html = '<span class="amount">R$ 100.00</span>';

		self::assertSame( $original_html, $subject->get_discount_price( $original_html, null ) );
	}

	public function test_does_not_treat_no_checkbox_metadata_as_disabled(): void {
		$product = new WC_Product(
			'100.00',
			'simple',
			null,
			array(
				'_darven_epi_is_incash_enabled'      => 'no',
				'_darven_epi_is_installment_enabled' => 'no',
			)
		);
		$subject = new Darven_Epi_Format_Final_Price();

		$result = $subject->get_discount_price( '<span class="amount">R$ 100.00</span>', $product );

		self::assertStringContainsString( 'R$ 90.00', $result );
	}

	public function test_incash_markup_uses_repeatable_prefixed_classes(): void {
		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Format_Final_Price();

		$result = $subject->get_discount_price( '<span class="amount">R$ 100.00</span>', $product );

		self::assertStringNotContainsString( ' id=', $result );
		self::assertStringContainsString( 'darven-epi-incash-price-statement', $result );
		self::assertStringContainsString( 'darven-epi-incash-prefix', $result );
		self::assertStringContainsString( 'darven-epi-incash-price', $result );
		self::assertStringContainsString( 'darven-epi-incash-suffix', $result );
	}

	public function test_ordination_does_not_wrap_the_original_price_in_a_generic_div(): void {
		$product       = new WC_Product( '100.00' );
		$subject       = new Darven_Epi_Format_Final_Price();
		$original_html = '<span class="amount">R$ 100.00</span>';

		$result = $subject->get_discount_price( $original_html, $product );

		self::assertStringStartsWith( $original_html, $result );
		self::assertStringNotContainsString( '<div>' . $original_html, $result );
	}
}
