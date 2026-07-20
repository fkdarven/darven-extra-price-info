<?php

use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ): void {
		$GLOBALS['darven_epi_test_settings_errors'][] = array(
			'setting' => $setting,
			'code'    => $code,
			'message' => $message,
			'type'    => $type,
		);
	}
}

require_once DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-general-settings-fields.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-positions-settings-fields.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-compatibility-settings-fields.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-colorsandstyles-settings-fields.php';

final class LegacySettingsSyncTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options']         = array();
		$GLOBALS['darven_epi_test_failing_options'] = array();
		$GLOBALS['darven_epi_test_settings_errors'] = array();
	}

	public function test_general_form_save_keeps_sanitized_values_and_creates_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_General_Settings_Fields::class )->darven_epi_sanitize(
			array(
				'darven_epi_mode_of_view' => 'popup',
			)
		);

		self::assertSame( 'popup', $sanitized['darven_epi_mode_of_view'] );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame(
			'popup',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['general']['darven_epi_mode_of_view']
		);
	}

	public function test_positions_form_save_keeps_sanitized_values_and_creates_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Positions_Fields::class )->darven_epi_sanitize(
			array(
				'darven_epi_single_product_position' => 'sixth',
			)
		);

		self::assertSame( 'sixth', $sanitized['darven_epi_single_product_position'] );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame(
			'sixth',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions']['darven_epi_single_product_position']
		);
	}

	public function test_compatibility_form_save_keeps_sanitized_values_and_creates_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Compatibility_Settings_Fields::class )->darven_epi_sanitize(
			array(
				'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
			)
		);

		self::assertSame(
			'darven_epi_is_yith_dynamic_compatibility_enabled',
			$sanitized['darven_epi_is_yith_dynamic_compatibility_enabled']
		);
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame(
			'darven_epi_is_yith_dynamic_compatibility_enabled',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['compatibility']['darven_epi_is_yith_dynamic_compatibility_enabled']
		);
	}

	public function test_display_form_save_keeps_sanitized_values_and_creates_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Colorsandstyles_Settings_Fields::class )->darven_epi_sanitize(
			array(
				'darven_epi_color_of_incash_price' => '#ABCDEF',
			)
		);

		self::assertSame( '#abcdef', $sanitized['darven_epi_color_of_incash_price'] );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame(
			'#abcdef',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['display']['darven_epi_color_of_incash_price']
		);
	}

	public function test_non_array_positions_form_save_keeps_empty_return_and_creates_empty_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Positions_Fields::class )->darven_epi_sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions'] );
	}

	public function test_non_array_compatibility_form_save_keeps_empty_return_and_creates_empty_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Compatibility_Settings_Fields::class )->darven_epi_sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['compatibility'] );
	}

	public function test_non_array_display_form_save_keeps_empty_return_and_creates_empty_canonical_section(): void {
		$sanitized = $this->getSubject( Darven_Epi_Colorsandstyles_Settings_Fields::class )->darven_epi_sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['display'] );
	}

	public function test_failed_legacy_mirror_keeps_sanitized_return_and_reports_pending_sync(): void {
		$GLOBALS['darven_epi_test_failing_options'] = array( 'darven_epi_option_positions' );

		$sanitized = $this->getSubject( Darven_Epi_Positions_Fields::class )->darven_epi_sanitize(
			array(
				'darven_epi_single_product_position' => 'sixth',
			)
		);

		self::assertSame( 'sixth', $sanitized['darven_epi_single_product_position'] );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame(
			'sixth',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions']['darven_epi_single_product_position']
		);
		self::assertSame(
			array(
				'pending_sections' => array( 'positions' ),
				'failed_options'   => array( 'darven_epi_option_positions' ),
			),
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ]
		);
		self::assertSame( 'darven_epi_legacy_sync_failed', $GLOBALS['darven_epi_test_settings_errors'][0]['code'] );
	}

	private function getSubject( string $className ) {
		$reflection = new ReflectionClass( $className );

		return $reflection->newInstanceWithoutConstructor();
	}
}
