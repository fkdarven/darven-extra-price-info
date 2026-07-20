<?php

use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use PHPUnit\Framework\TestCase;

final class ProductSettingsRepositoryTest extends TestCase {
	public function test_falls_back_to_legacy_meta_without_writing_a_migration(): void {
		$product = new WC_Product(
			'100.00',
			'simple',
			null,
			array(
				'_darven_epi_is_incash_enabled'      => 'yes',
				'_darven_epi_is_installment_enabled' => 'anything-else',
			)
		);

		$settings = $this->get_repository()->getSettings( $product );

		self::assertSame(
			array( 'disable_incash' => true, 'disable_installments' => false ),
			$settings
		);
		self::assertSame( '', $product->get_meta( ProductSettingsRepository::META_KEY, true ) );
		self::assertSame( 0, $product->get_save_count() );
	}

	public function test_prefers_canonical_product_settings_over_legacy_meta(): void {
		$canonical = array( 'disable_incash' => false, 'disable_installments' => true );
		$product   = new WC_Product(
			'100.00',
			'simple',
			null,
			array(
				ProductSettingsRepository::META_KEY      => $canonical,
				'_darven_epi_is_incash_enabled'          => 'yes',
				'_darven_epi_is_installment_enabled'     => 'no',
			)
		);

		self::assertSame( $canonical, $this->get_repository()->getSettings( $product ) );
		self::assertSame( 0, $product->get_save_count() );
	}

	public function test_saves_canonical_settings_and_projects_the_legacy_flags(): void {
		$product = new WC_Product( '100.00' );

		$result = $this->get_repository()->save(
			$product,
			array( 'disable_incash' => true, 'disable_installments' => false )
		);

		self::assertTrue( $result );
		self::assertSame(
			array( 'disable_incash' => true, 'disable_installments' => false ),
			$product->get_meta( '_darven_epi_product_settings', true )
		);
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_installment_enabled', true ) );
		self::assertSame( 1, $product->get_save_count() );
	}

	private function get_repository(): ProductSettingsRepository {
		return new ProductSettingsRepository( new LegacyProductSettingsAdapter() );
	}
}
