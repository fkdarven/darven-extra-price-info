<?php

use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use PHPUnit\Framework\TestCase;

final class HtmlGeneratorTest extends TestCase {
	public function test_generates_repeatable_component_classes_without_ids(): void {
		$subject = new PriceMarkupBuilder();

		$div  = $subject->div( 'darven-epi-price', 'content', 'incash' );
		$span = $subject->span( 'darven-epi-prefix', 'prefix', 'incash' );

		self::assertStringNotContainsString( ' id=', $div );
		self::assertStringNotContainsString( ' id=', $span );
		self::assertStringContainsString(
			'class="darven-epi-price incash-epi-single-product"',
			$div
		);
		self::assertStringContainsString(
			'class="darven-epi-prefix incash-epi-single-product"',
			$span
		);
	}
}
