<?php

use Darven\ExtraPriceInfo\Admin\ReactPage;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

final class ReactPageTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_actions']          = array();
		$GLOBALS['darven_epi_test_capability_check'] = null;
		$GLOBALS['darven_epi_test_current_user_can'] = true;
		$GLOBALS['darven_epi_test_nonce_check']      = null;
		$GLOBALS['darven_epi_test_nonce_is_valid']   = true;
		$GLOBALS['darven_epi_test_options']          = array(
			SettingsRepository::OPTION_NAME => array(
				'schema_version' => 2,
				'general'        => array(
					'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
					'darven_epi_max_installments'  => '9',
				),
				'display'        => array(),
				'positions'      => array(),
				'compatibility'  => array(
					'darven_epi_yith_dynamic_pricing_mode' => 'disabled',
				),
			),
		);
		$_POST = array();
	}

	public function test_registers_the_settings_page_and_classic_save_handler(): void {
		$this->getSubject()->register();

		self::assertSame(
			array( 'admin_menu', 'admin_post_darven_epi_save_settings' ),
			array_column( $GLOBALS['darven_epi_test_actions'], 'hook' )
		);
	}

	public function test_renders_the_react_mount_point_and_nonce_protected_classic_form_with_current_values(): void {
		ob_start();
		$this->getSubject()->renderPage();
		$output = ob_get_clean();

		self::assertStringContainsString( '<div id="darven-precos-parcelados-settings-root"></div>', $output );
		self::assertStringContainsString( '<noscript>', $output );
		self::assertStringContainsString( 'action="https://example.test/wp-admin/admin-post.php"', $output );
		self::assertStringContainsString( 'name="darven_epi_settings_nonce" value="test-settings-nonce"', $output );
		self::assertStringContainsString( 'name="darven_epi_settings[general][darven_epi_max_installments]" value="9"', $output );
		self::assertStringContainsString( 'name="darven_epi_settings[general][darven_epi_incash_is_enabled]" value="darven_epi_incash_is_enabled" checked', $output );
		self::assertStringContainsString( 'type="submit"', $output );
	}

	public function test_classic_handler_checks_capability_and_nonce_then_saves_a_sanitized_document(): void {
		$_POST = array(
			'darven_epi_settings_nonce' => 'valid-settings-nonce',
			'darven_epi_settings'       => array(
				'general' => array(
					'darven_epi_max_installments' => '12.9<script>ignored</script>',
					'not_a_setting'               => 'discard me',
				),
			),
		);

		$this->getSubject()->handleSave();

		self::assertSame( array( 'manage_options', null ), $GLOBALS['darven_epi_test_capability_check'] );
		self::assertSame( array( 'valid-settings-nonce', 'darven_epi_save_settings' ), $GLOBALS['darven_epi_test_nonce_check'] );
		self::assertSame( '12', $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['general']['darven_epi_max_installments'] );
		self::assertArrayNotHasKey( 'not_a_setting', $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['general'] );
		self::assertSame( 'https://example.test/wp-admin/admin.php?page=darven-epi-admin&settings-updated=1', $GLOBALS['darven_epi_test_safe_redirect'] );
	}

	public function test_classic_handler_rejects_users_without_manage_options(): void {
		$GLOBALS['darven_epi_test_current_user_can'] = false;
		$_POST['darven_epi_settings_nonce'] = 'valid-settings-nonce';

		$this->expectException( RuntimeException::class );
		$this->getSubject()->handleSave();
	}

	public function test_classic_handler_rejects_an_invalid_nonce(): void {
		$GLOBALS['darven_epi_test_nonce_is_valid'] = false;
		$_POST['darven_epi_settings_nonce'] = 'invalid-settings-nonce';

		$this->expectException( RuntimeException::class );
		$this->getSubject()->handleSave();
	}

	private function getSubject(): ReactPage {
		return new ReactPage(
			new SettingsRepository( new LegacySettingsAdapter() )
		);
	}
}
