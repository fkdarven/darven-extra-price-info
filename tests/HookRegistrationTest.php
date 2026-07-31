<?php

use Darven\ExtraPriceInfo\Setup\Plugin;
use PHPUnit\Framework\TestCase;

final class HookRegistrationTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_actions'] = array();
		$GLOBALS['darven_epi_test_filters'] = array();
	}

	public function test_price_html_filter_uses_the_required_priority_and_product_argument(): void {
		Plugin::boot();

		$price_filters = array_values(
			array_filter(
				$GLOBALS['darven_epi_test_filters'], static function ( array $registration ): bool {
					return 'woocommerce_get_price_html' === $registration['hook'];
				}
			)
		);

		self::assertCount( 1, $price_filters );
		self::assertSame( 2000, $price_filters[0]['priority'] );
		self::assertSame( 2, $price_filters[0]['accepted_args'] );
		self::assertSame( 'filter', $price_filters[0]['callback'][1] );
	}
}
