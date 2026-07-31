<?php

use Darven\ExtraPriceInfo\Repositories\SettingsSanitizer;
use PHPUnit\Framework\TestCase;

final class GeneralSettingsSanitizationTest extends TestCase {
	public function test_sanitizes_general_options_according_to_field_types(): void {
		$subject = new SettingsSanitizer();

		$result = $subject->sanitizeSection(
			'general', array(
				'darven_epi_incash_is_enabled'                         => 'yes<script>alert(1)</script>',
				'darven_epi_installments_is_enabled'                   => 'darven_epi_installments_is_enabled',
				'darven_epi_installments_interest_fee_is_table_enabled' => '1',
				'darven_epi_type_of_discount'                          => 'coupon',
				'darven_epi_mode_of_view'                              => 'popup',
				'darven_epi_value_of_incash_discount'                  => '10,5<script>alert(1)</script>',
				'darven_epi_minimum_incash_value'                      => '-99',
				'darven_epi_max_installments'                          => '-3<script>9</script>',
				'darven_epi_installments_interest_fee_from'            => '3.9',
				'darven_epi_installments_interest_fee_table'           => '8,25|bad|9.5|<script>10</script>|-2',
				'darven_epi_unknown_option'                            => 'keep me',
			)
		);

		self::assertArrayNotHasKey( 'darven_epi_incash_is_enabled', $result );
		self::assertSame( 'darven_epi_installments_is_enabled', $result['darven_epi_installments_is_enabled'] );
		self::assertArrayNotHasKey( 'darven_epi_installments_interest_fee_is_table_enabled', $result );
		self::assertSame( 'percent', $result['darven_epi_type_of_discount'] );
		self::assertSame( 'popup', $result['darven_epi_mode_of_view'] );
		self::assertSame( '10.5', $result['darven_epi_value_of_incash_discount'] );
		self::assertSame( '0', $result['darven_epi_minimum_incash_value'] );
		self::assertSame( '1', $result['darven_epi_max_installments'] );
		self::assertSame( '3', $result['darven_epi_installments_interest_fee_from'] );
		self::assertSame( '8.25|9.5|10', $result['darven_epi_installments_interest_fee_table'] );
		self::assertArrayNotHasKey( 'darven_epi_unknown_option', $result );
	}

	public function test_exposes_the_general_field_rules_through_the_shared_sanitizer(): void {
		$result = ( new SettingsSanitizer() )->sanitizeSection(
			'general', array( 'darven_epi_max_installments' => '12.9' )
		);

		self::assertSame( '12', $result['darven_epi_max_installments'] );
	}

	public function test_allows_limited_markup_only_for_general_text_fields(): void {
		$subject = new SettingsSanitizer();

		$result = $subject->sanitizeSection(
			'general', array(
				'darven_epi_incash_prefix'        => 'Pay <b>now</b><script>alert(1)</script>',
				'darven_epi_installments_suffix'  => '<em>sem juros</em><iframe src="https://example.com"></iframe>',
				'darven_epi_popup_text'           => '<a href="https://example.com">ver parcelas</a><img src=x>',
				'darven_epi_value_of_incash_discount' => '<b>10</b>',
			)
		);

		self::assertStringContainsString( '<b>now</b>', $result['darven_epi_incash_prefix'] );
		self::assertStringNotContainsString( '<script', $result['darven_epi_incash_prefix'] );
		self::assertStringContainsString( '<em>sem juros</em>', $result['darven_epi_installments_suffix'] );
		self::assertStringNotContainsString( '<iframe', $result['darven_epi_installments_suffix'] );
		self::assertStringContainsString( '<a href="https://example.com">ver parcelas</a>', $result['darven_epi_popup_text'] );
		self::assertStringNotContainsString( '<img', $result['darven_epi_popup_text'] );
		self::assertSame( '10', $result['darven_epi_value_of_incash_discount'] );
	}
}
