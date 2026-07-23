<?php

use Darven\ExtraPriceInfo\Admin\SettingsFields\CompatibilityFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\DisplayFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\GeneralFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\PositionsFields;
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

if ( ! defined( 'DARVEN_EPI_ADMIN_PAGE' ) ) {
	define( 'DARVEN_EPI_ADMIN_PAGE', 'darven-epi-admin' );
}

if ( ! defined( 'DARVEN_EPI_LANGUAGE_DOMAIN' ) ) {
	define( 'DARVEN_EPI_LANGUAGE_DOMAIN', 'darven-epi' );
}

final class LegacySettingsSyncTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options']         = array();
		$GLOBALS['darven_epi_test_failing_options'] = array();
		$GLOBALS['darven_epi_test_settings_errors'] = array();
		$GLOBALS['darven_epi_test_settings_sanitizers'] = array();
		$GLOBALS['darven_epi_test_update_option_calls'] = array();
		$GLOBALS['darven_epi_test_update_option_depth'] = 0;
	}

	protected function tearDown(): void {
		$GLOBALS['darven_epi_test_settings_sanitizers'] = array();
	}

	public function test_general_form_save_keeps_sanitized_values_and_creates_canonical_section(): void {
		$sanitized = $this->getSubject( GeneralFields::class )->sanitize(
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
		$sanitized = $this->getSubject( PositionsFields::class )->sanitize(
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
		$sanitized = $this->getSubject( CompatibilityFields::class )->sanitize(
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
		$sanitized = $this->getSubject( DisplayFields::class )->sanitize(
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
		$sanitized = $this->getSubject( PositionsFields::class )->sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions'] );
	}

	public function test_non_array_compatibility_form_save_keeps_empty_return_and_creates_empty_canonical_section(): void {
		$sanitized = $this->getSubject( CompatibilityFields::class )->sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['compatibility'] );
	}

	public function test_non_array_display_form_save_keeps_empty_return_and_creates_empty_canonical_section(): void {
		$sanitized = $this->getSubject( DisplayFields::class )->sanitize( null );

		self::assertSame( array(), $sanitized );
		self::assertArrayHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['display'] );
	}

	public function test_registered_settings_sanitizer_reentry_finishes_and_syncs_canonical_and_legacy_options(): void {
		( new PositionsFields() )->register();
		$reentry_exception = null;

		try {
			update_option(
				'darven_epi_option_positions',
				array(
					'darven_epi_single_product_position' => 'sixth',
				)
			);
		} catch ( RuntimeException $exception ) {
			$reentry_exception = $exception;
		}

		self::assertNull( $reentry_exception );
		self::assertSame(
			'sixth',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions']['darven_epi_single_product_position']
		);
		self::assertSame(
			'sixth',
			$GLOBALS['darven_epi_test_options']['darven_epi_option_positions']['darven_epi_single_product_position']
		);

		update_option(
			'darven_epi_option_positions',
			array(
				'darven_epi_single_product_position' => 'third',
			)
		);

		self::assertSame(
			'third',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions']['darven_epi_single_product_position']
		);
		self::assertSame(
			'third',
			$GLOBALS['darven_epi_test_options']['darven_epi_option_positions']['darven_epi_single_product_position']
		);
		self::assertSame( array(), $GLOBALS['darven_epi_test_settings_errors'] );
		self::assertSame( 10, count( $GLOBALS['darven_epi_test_update_option_calls'] ) );
	}

	public function test_registered_general_settings_save_preserves_third_party_values_and_removes_an_unchecked_plugin_field(): void {
		$GLOBALS['darven_epi_test_options']['darven_epi_option_general'] = array(
			'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
			'third_party_general_key'       => 'retain',
		);
		( new GeneralFields() )->register();

		update_option(
			'darven_epi_option_general',
			array(
				'darven_epi_mode_of_view' => 'popup',
			)
		);

		$legacy_general = $GLOBALS['darven_epi_test_options']['darven_epi_option_general'];
		$canonical      = $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ];

		self::assertArrayHasKey( 'third_party_general_key', $legacy_general );
		self::assertSame( 'retain', $legacy_general['third_party_general_key'] );
		self::assertSame( 'popup', $legacy_general['darven_epi_mode_of_view'] );
		self::assertArrayNotHasKey( 'darven_epi_incash_is_enabled', $legacy_general );
		self::assertSame( 'popup', $canonical['general']['darven_epi_mode_of_view'] );
		self::assertArrayNotHasKey( 'darven_epi_incash_is_enabled', $canonical['general'] );
		self::assertArrayNotHasKey( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_settings_errors'] );
		self::assertSame(
			1,
			count( array_keys( $GLOBALS['darven_epi_test_update_option_calls'], 'darven_epi_option_general', true ) )
		);
	}

	public function test_failed_legacy_mirror_keeps_sanitized_return_and_reports_pending_sync(): void {
		$GLOBALS['darven_epi_test_failing_options'] = array( 'darven_epi_option_general' );

		$sanitized = $this->getSubject( PositionsFields::class )->sanitize(
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
				'failed_options'   => array( 'darven_epi_option_general' ),
			),
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ]
		);
		self::assertSame( 'darven_epi_legacy_sync_failed', $GLOBALS['darven_epi_test_settings_errors'][0]['code'] );
	}

	private function getSubject( string $className ) {
		return new $className();
	}
}
