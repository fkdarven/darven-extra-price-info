<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

final class SettingsRepositoryTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options']         = array();
		$GLOBALS['darven_epi_test_failing_options'] = array();
		$GLOBALS['darven_epi_test_option_reads']    = array();
	}

	public function test_reads_normalized_legacy_options_without_writing_a_migration(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();

		$settings = $this->getRepository()->getSettings();

		self::assertSame( 1, $settings['schema_version'] );
		self::assertSame( '6', $settings['general']['darven_epi_max_installments'] );
		self::assertSame( 'third', $settings['positions']['darven_epi_single_product_position'] );
		self::assertArrayNotHasKey( 'third_party_general_key', $settings['general'] );
		self::assertArrayNotHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
	}

	public function test_prefers_a_valid_canonical_document(): void {
		$canonical = array(
			'schema_version' => 1,
			'general'        => array(
				'darven_epi_max_installments' => '12',
			),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array(),
		);
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = $canonical;

		self::assertSame( $canonical, $this->getRepository()->getSettings() );
	}

	public function test_falls_back_to_legacy_options_when_canonical_document_is_invalid(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = array(
			'schema_version' => 1,
			'general'        => array(),
		);

		$settings = $this->getRepository()->getSettings();

		self::assertSame( '6', $settings['general']['darven_epi_max_installments'] );
		self::assertSame( 'third', $settings['positions']['darven_epi_single_product_position'] );
	}

	public function test_defaults_to_automatic_without_any_persisted_darven_settings(): void {
		self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
		self::assertSame( array(), $GLOBALS['darven_epi_test_options'] );
	}

	public function test_keeps_an_existing_unchecked_installation_disabled(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();

		self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_maps_the_existing_checked_legacy_value_to_automatic(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_explicit_canonical_mode_overrides_legacy_inference(): void {
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = array(
			'schema_version' => 1,
			'general'        => array(),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array( 'darven_epi_yith_dynamic_pricing_mode' => 'disabled' ),
		);

		self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_saves_a_section_to_canonical_and_legacy_options(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ] = array(
			'pending_sections' => array( 'positions' ),
			'failed_options'   => array( 'darven_epi_option_positions' ),
		);

		$result = $this->getRepository()->saveSection(
			'general',
			array(
				'darven_epi_max_installments' => '12',
			)
		);

		self::assertTrue( $result );
		self::assertSame(
			'12',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['general']['darven_epi_max_installments']
		);
		self::assertSame(
			'12',
			$GLOBALS['darven_epi_test_options']['darven_epi_option_general']['darven_epi_max_installments']
		);
		self::assertArrayNotHasKey( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_options'] );
	}

	public function test_confirms_idempotent_writes_and_clears_pending_sync_state(): void {
		$canonical = array(
			'schema_version' => 1,
			'general'        => array(
				'darven_epi_max_installments' => '6',
			),
			'positions'      => array(
				'darven_epi_single_product_position' => 'third',
			),
			'display'        => array(),
			'compatibility'  => array(),
		);
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = $canonical;
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ] = array(
			'pending_sections' => array( 'positions' ),
			'failed_options'   => array( 'darven_epi_option_positions' ),
		);

		$result = $this->getRepository()->saveSection( 'general', $canonical['general'] );

		self::assertTrue( $result );
		self::assertSame( $canonical, $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] );
		self::assertArrayNotHasKey( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_options'] );
	}

	public function test_returns_false_when_the_pending_sync_state_cannot_be_deleted(): void {
		$sync_state = array(
			'pending_sections' => array( 'positions' ),
			'failed_options'   => array( 'darven_epi_option_positions' ),
		);
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ] = $sync_state;
		$GLOBALS['darven_epi_test_failing_options'] = array( SettingsRepository::SYNC_STATE_OPTION );

		$result = $this->getRepository()->saveSection(
			'general',
			array(
				'darven_epi_max_installments' => '12',
			)
		);

		self::assertFalse( $result );
		self::assertSame( $sync_state, $GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ] );
	}

	public function test_returns_false_when_a_pending_sync_state_cannot_be_recorded(): void {
		$GLOBALS['darven_epi_test_options']         = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_failing_options'] = array(
			'darven_epi_option_positions',
			SettingsRepository::SYNC_STATE_OPTION,
		);

		$result = $this->getRepository()->saveSection(
			'positions',
			array(
				'darven_epi_single_product_position' => 'sixth',
			)
		);

		self::assertFalse( $result );
		self::assertSame(
			'sixth',
			$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ]['positions']['darven_epi_single_product_position']
		);
		// No new marker can be observed when the sync-state option itself fails to write.
		self::assertArrayNotHasKey( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_options'] );
		self::assertContains( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_option_reads'] );
	}

	public function test_preserves_unknown_legacy_keys_when_projecting_a_save(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();

		$result = $this->getRepository()->saveSection(
			'general',
			array(
				'darven_epi_max_installments' => '12',
			)
		);

		self::assertTrue( $result );
		self::assertSame(
			'retain',
			$GLOBALS['darven_epi_test_options']['darven_epi_option_general']['third_party_general_key']
		);
	}

	public function test_records_pending_sync_when_a_legacy_mirror_cannot_be_verified(): void {
		$GLOBALS['darven_epi_test_options']         = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_failing_options'] = array( 'darven_epi_option_positions' );

		$result = $this->getRepository()->saveSection(
			'positions',
			array(
				'darven_epi_single_product_position' => 'sixth',
			)
		);

		self::assertFalse( $result );
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
	}

	public function test_get_section_throws_for_an_unknown_section(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->getRepository()->getSection( 'unknown' );
	}

	private function getRepository(): SettingsRepository {
		return new SettingsRepository( new LegacySettingsAdapter() );
	}

	private function getLegacyOptions(): array {
		return array(
			'darven_epi_option_general' => array(
				'darven_epi_max_installments' => '6',
				'third_party_general_key'      => 'retain',
			),
			'darven_epi_option_positions' => array(
				'darven_epi_single_product_position' => 'third',
			),
			'darven_epi_option_colorsandstyles' => array(),
			'darven_epi_option_compatibility' => array(),
		);
	}
}
