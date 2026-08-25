<?php

use Darven\ExtraPriceInfo\Admin\ProductOptionsController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use PHPUnit\Framework\TestCase;

final class ProductOptionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_actions'] = array();
		$GLOBALS['darven_epi_test_filters'] = array();
		$GLOBALS['darven_epi_test_current_user_can'] = true;
		$GLOBALS['darven_epi_test_nonce_is_valid']    = true;
		$_POST = array(
			'woocommerce_meta_nonce' => 'valid-nonce',
		);
	}

	public function test_registers_the_darven_tab_panel_and_product_object_save_hook(): void {
		$this->getSubject()->register();

		$hooks = array_column( $GLOBALS['darven_epi_test_actions'], 'hook' );
		$filters = array_column( $GLOBALS['darven_epi_test_filters'], 'hook' );

		self::assertContains( 'woocommerce_admin_process_product_object', $hooks );
		self::assertContains( 'woocommerce_product_data_panels', $hooks );
		self::assertContains( 'woocommerce_product_data_tabs', $filters );
		self::assertNotContains( 'woocommerce_product_options_general_product_data', $hooks );
		self::assertNotContains( 'woocommerce_process_product_meta', $hooks );
	}

	public function test_adds_a_darven_product_data_tab_targeting_the_react_panel(): void {
		$subject = $this->getSubject();
		$tabs = $subject->addTab(
			array(
				'general' => array( 'label' => 'General' ),
			)
		);

		self::assertSame( 'General', $tabs['general']['label'] );
		self::assertSame( 'Darven', $tabs['darven-precos-parcelados']['label'] );
		self::assertSame(
			'darven-precos-parcelados-product-options-panel', $tabs['darven-precos-parcelados']['target']
		);
	}

	public function test_renders_the_react_mount_point_and_visible_classic_controls_outside_the_hidden_panel(): void {
		$GLOBALS['post'] = (object) array( 'ID' => 42 );
		$GLOBALS['darven_epi_test_products'][42] = new WC_Product(
			'100.00', 'simple', null, array(
				'_darven_epi_product_settings' => array(
					'schema_version'       => 1,
					'disable_incash'       => true,
					'disable_installments' => false,
				),
			), 42
		);
		$subject = $this->getSubject();

		ob_start();
		$subject->renderPanel();
		$output = ob_get_clean();

		self::assertStringContainsString(
			'<div id="darven-precos-parcelados-product-options-root" data-product-id="42"></div></div><noscript>', $output
		);
		self::assertStringContainsString( 'name="_darven_epi_is_incash_enabled" value="yes" checked', $output );
		self::assertStringContainsString( 'name="_darven_epi_is_installment_enabled" value="yes"', $output );
	}

	public function test_projects_checked_and_unchecked_product_options(): void {
		$_POST['_darven_epi_is_incash_enabled']      = 'yes';
		$_POST['_darven_epi_is_installment_enabled'] = 'no';

		$product = new WC_Product( '100.00' );
		$subject = $this->getSubject();

		$subject->save( $product );

		self::assertSame(
			array( 'schema_version' => 1, 'disable_incash' => true, 'disable_installments' => false ), $product->get_meta( '_darven_epi_product_settings', true )
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
			array( 'schema_version' => 1, 'disable_incash' => true, 'disable_installments' => false ), $product->get_meta( '_darven_epi_product_settings', true )
		);
	}

	public function test_does_not_reset_existing_product_settings_when_no_fallback_fields_are_submitted(): void {
		$product = new WC_Product(
			'100.00', 'simple', null, array(
				'_darven_epi_product_settings' => array(
					'schema_version'       => 1,
					'disable_incash'       => true,
					'disable_installments' => true,
				),
				'_darven_epi_is_incash_enabled' => 'yes',
				'_darven_epi_is_installment_enabled' => 'yes',
			)
		);
		$subject = $this->getSubject();

		try {
			$subject->save( $product );
		} catch ( Throwable $exception ) {
			self::fail( 'Saving absent checkboxes raised: ' . $exception->getMessage() );
		}

		self::assertSame(
			array( 'schema_version' => 1, 'disable_incash' => true, 'disable_installments' => true ), $product->get_meta( '_darven_epi_product_settings', true )
		);
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
		self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_installment_enabled', true ) );
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
