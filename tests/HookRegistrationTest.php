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

		self::assertCount( 1, $GLOBALS['darven_epi_test_filters'] );
		self::assertSame( 'woocommerce_get_price_html', $GLOBALS['darven_epi_test_filters'][0]['hook'] );
		self::assertSame( 2000, $GLOBALS['darven_epi_test_filters'][0]['priority'] );
		self::assertSame( 2, $GLOBALS['darven_epi_test_filters'][0]['accepted_args'] );
		self::assertSame( 'filter', $GLOBALS['darven_epi_test_filters'][0]['callback'][1] );
	}
}
