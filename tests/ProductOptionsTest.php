<?php

use Darven\ExtraPriceInfo\Admin\ProductOptionsController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use PHPUnit\Framework\TestCase;

final class ProductOptionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_actions'] = array();
		$GLOBALS['darven_epi_test_current_user_can'] = true;
		$GLOBALS['darven_epi_test_nonce_is_valid']    = true;
		$_POST = array(
			'woocommerce_meta_nonce' => 'valid-nonce',
		);
	}

	public function test_registers_the_product_object_save_hook(): void {
		$this->getSubject()->register();

		$hooks = array_column( $GLOBALS['darven_epi_test_actions'], 'hook' );

		self::assertContains( 'woocommerce_admin_process_product_object', $hooks );
		self::assertNotContains( 'woocommerce_process_product_meta', $hooks );
	}

	public function test_projects_checked_and_unchecked_product_options(): void {
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';
		$_POST['_darven_epi_is_installment_enabled'] = 'no';

		$product = new WC_Product( '100.00' );
		$subject = $this->getSubject();

		$subject->save( $product );

		self::assertSame(
			array( 'disable_incash' => true, 'disable_installments' => false ),
			$product->get_meta( '_darven_epi_product_settings', true )
		);
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_installment_enabled', true ) );
		self::assertSame( 0, $product->get_save_count() );
	}

	public function test_product_save_hook_prepares_meta_for_the_standard_woocommerce_save(): void {
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';
		$_POST['_darven_epi_is_installment_enabled'] = 'no';

		$product = new WC_Product( '100.00' );
		$subject = $this->getSubject();

		$subject->save( $product );
		$product->save();

		self::assertSame( 1, $product->get_save_count() );
		self::assertSame(
			array( 'disable_incash' => true, 'disable_installments' => false ),
			$product->get_meta( '_darven_epi_product_settings', true )
		);
	}

	public function test_normalizes_absent_checkboxes_to_no_without_warnings(): void {
		$product = new WC_Product( '100.00' );
		$subject = $this->getSubject();

		try {
			$subject->save( $product );
		} catch ( Throwable $exception ) {
			self::fail( 'Saving absent checkboxes raised: ' . $exception->getMessage() );
		}

		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_installment_enabled' ) );
		self::assertSame(
			array( 'disable_incash' => false, 'disable_installments' => false ),
			$product->get_meta( '_darven_epi_product_settings', true )
		);
	}

	public function test_does_not_update_product_without_edit_permission(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';

		$product = new WC_Product( '100.00', 'simple', null, array(), 42 );
		$subject = $this->getSubject();

		$subject->save( $product );

		self::assertSame( '', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame( array( 'edit_post', 42 ), $GLOBALS['darven_epi_test_capability_check'] );
	}

	public function test_does_not_update_product_with_an_invalid_nonce(): void {
		$GLOBALS['darven_epi_test_nonce_is_valid'] = false;
		$_POST['_darven_epi_is_incash_enabled']    = 'yes';

		$product = new WC_Product( '100.00' );
		$subject = $this->getSubject();

		$subject->save( $product );

		self::assertSame( '', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame(
			array( 'valid-nonce', 'woocommerce_save_data' ),
			$GLOBALS['darven_epi_test_nonce_check']
		);
	}

	private function getSubject(): ProductOptionsController {
		return new ProductOptionsController(
			new ProductSettingsRepository( new LegacyProductSettingsAdapter() )
		);
	}
}
