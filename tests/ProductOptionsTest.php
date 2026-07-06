<?php

use PHPUnit\Framework\TestCase;

require_once DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-product-options.php';

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
		new Darven_Epi_Product_Options();

		$hooks = array_column( $GLOBALS['darven_epi_test_actions'], 'hook' );

		self::assertContains( 'woocommerce_admin_process_product_object', $hooks );
		self::assertNotContains( 'woocommerce_process_product_meta', $hooks );
	}

	public function test_updates_the_given_product_when_both_checkboxes_are_checked(): void {
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';
		$_POST['_darven_epi_is_installment_enabled'] = 'yes';

		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Product_Options();

		$subject->save_extra_prices( $product );

		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_installment_enabled' ) );
	}

	public function test_normalizes_absent_checkboxes_to_no_without_warnings(): void {
		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Product_Options();

		try {
			$subject->save_extra_prices( $product );
		} catch ( Throwable $exception ) {
			self::fail( 'Saving absent checkboxes raised: ' . $exception->getMessage() );
		}

		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame( 'no', $product->get_meta( '_darven_epi_is_installment_enabled' ) );
	}

	public function test_does_not_update_product_without_edit_permission(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';

		$product = new WC_Product( '100.00', 'simple', null, array(), 42 );
		$subject = new Darven_Epi_Product_Options();

		$subject->save_extra_prices( $product );

		self::assertSame( '', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame( array( 'edit_post', 42 ), $GLOBALS['darven_epi_test_capability_check'] );
	}

	public function test_does_not_update_product_with_an_invalid_nonce(): void {
		$GLOBALS['darven_epi_test_nonce_is_valid'] = false;
		$_POST['_darven_epi_is_incash_enabled']    = 'yes';

		$product = new WC_Product( '100.00' );
		$subject = new Darven_Epi_Product_Options();

		$subject->save_extra_prices( $product );

		self::assertSame( '', $product->get_meta( '_darven_epi_is_incash_enabled' ) );
		self::assertSame(
			array( 'valid-nonce', 'woocommerce_save_data' ),
			$GLOBALS['darven_epi_test_nonce_check']
		);
	}
}
