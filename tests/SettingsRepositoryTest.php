<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\YithDynamicPricingMode;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use PHPUnit\Framework\TestCase;

final class SettingsRepositoryTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['darven_epi_test_options']         = array();
		$GLOBALS['darven_epi_test_failing_options'] = array();
		$GLOBALS['darven_epi_test_option_reads']    = array();
		$GLOBALS['darven_epi_test_update_option_calls'] = array();
	}

	public function test_reads_normalized_legacy_options_without_writing_a_migration(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();

		$settings = $this->getRepository()->getSettings();

		self::assertSame( 2, $settings['schema_version'] );
		self::assertSame( '6', $settings['general']['darven_epi_max_installments'] );
		self::assertSame( 'third', $settings['positions']['darven_epi_single_product_position'] );
		self::assertArrayNotHasKey( 'third_party_general_key', $settings['general'] );
		self::assertArrayNotHasKey( SettingsRepository::OPTION_NAME, $GLOBALS['darven_epi_test_options'] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_update_option_calls'] );
	}

	public function test_promotes_a_v1_document_to_a_normalized_v2_view_without_writing(): void {
		$document = array(
			'schema_version' => 1,
			'general'        => array( 'darven_epi_max_installments' => '12.9' ),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array(),
		);
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = $document;

		$settings = $this->getRepository()->getNormalizedSettings();

		self::assertSame( 2, $settings['schema_version'] );
		self::assertSame( '12', $settings['general']['darven_epi_max_installments'] );
		self::assertSame( $document, $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] );
		self::assertSame( array(), $GLOBALS['darven_epi_test_update_option_calls'] );
	}

	public function test_normalizes_a_valid_canonical_document_with_an_explicit_effective_mode(): void {
		$canonical = array(
			'schema_version' => 2,
			'general'        => array(
				'darven_epi_max_installments' => '12',
			),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array(),
		);
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = $canonical;

		$expected = $canonical;
		$expected['compatibility'] = array(
			YithDynamicPricingMode::FIELD => YithDynamicPricingMode::DISABLED,
		);

		self::assertSame( $expected, $this->getRepository()->getSettings() );
	}

	public function test_save_document_merges_partial_sections_and_sanitizes_known_values(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();

		$result = $this->getRepository()->saveDocument(
			array(
				'general' => array(
					'darven_epi_max_installments' => '12.9',
					'darven_epi_type_of_discount' => 'unknown',
				),
				'display' => 'malformed',
			)
		);

		self::assertTrue( $result );
		$stored = $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ];
		self::assertSame( 2, $stored['schema_version'] );
		self::assertSame( '12', $stored['general']['darven_epi_max_installments'] );
		self::assertSame( 'percent', $stored['general']['darven_epi_type_of_discount'] );
		self::assertSame( 'third', $stored['positions']['darven_epi_single_product_position'] );
	}

	public function test_save_document_projects_all_legacy_options_and_preserves_third_party_values(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::SYNC_STATE_OPTION ] = array(
			'pending_sections' => array( 'positions' ),
			'failed_options'   => array( 'darven_epi_option_positions' ),
		);

		$result = $this->getRepository()->saveDocument(
			array( 'general' => array( 'darven_epi_max_installments' => '12' ) )
		);

		self::assertTrue( $result );
		foreach ( array(
			'darven_epi_option_general',
			'darven_epi_option_positions',
			'darven_epi_option_colorsandstyles',
			'darven_epi_option_compatibility',
		) as $option_name ) {
			self::assertArrayHasKey( $option_name, $GLOBALS['darven_epi_test_options'] );
		}
		self::assertSame( 'retain', $GLOBALS['darven_epi_test_options']['darven_epi_option_general']['third_party_general_key'] );
		self::assertArrayNotHasKey( SettingsRepository::SYNC_STATE_OPTION, $GLOBALS['darven_epi_test_options'] );
	}

	public function test_falls_back_to_legacy_options_when_canonical_document_is_invalid(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = array(
			'schema_version' => 2,
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

	public function test_unchanged_react_save_materializes_automatic_mode_for_a_clean_installation(): void {
		$before_save = $GLOBALS['darven_epi_test_options'];
		$document    = $this->getRepository()->getNormalizedSettings();

		self::assertSame( 'auto', $document['compatibility'][ YithDynamicPricingMode::FIELD ] );
		self::assertSame( $before_save, $GLOBALS['darven_epi_test_options'] );
		self::assertTrue( $this->getRepository()->saveDocument( $document ) );
		self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_unchanged_react_save_preserves_automatic_mode_from_a_checked_legacy_option(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			YithDynamicPricingMode::LEGACY_FIELD => YithDynamicPricingMode::LEGACY_FIELD,
		);
		$before_save = $GLOBALS['darven_epi_test_options'];
		$document    = $this->getRepository()->getNormalizedSettings();

		self::assertSame( 'auto', $document['compatibility'][ YithDynamicPricingMode::FIELD ] );
		self::assertSame( $before_save, $GLOBALS['darven_epi_test_options'] );
		self::assertTrue( $this->getRepository()->saveDocument( $document ) );
		self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_unchanged_react_save_preserves_disabled_mode_from_an_unchecked_legacy_option(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$before_save = $GLOBALS['darven_epi_test_options'];
		$document    = $this->getRepository()->getNormalizedSettings();

		self::assertSame( 'disabled', $document['compatibility'][ YithDynamicPricingMode::FIELD ] );
		self::assertSame( $before_save, $GLOBALS['darven_epi_test_options'] );
		self::assertTrue( $this->getRepository()->saveDocument( $document ) );
		self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
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

	public function test_reads_the_checked_legacy_value_when_a_valid_canonical_document_has_no_mode(): void {
		$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = array(
			'schema_version' => 2,
			'general'        => array(),
			'positions'      => array(),
			'display'        => array(),
			'compatibility'  => array(),
		);
		$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
			'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
		);

		self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
	}

	public function test_keeps_an_invalid_persisted_canonical_document_disabled_without_writing(): void {
		$invalid_canonical = array(
			'schema_version' => 2,
			'general'        => array(),
		);
		$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = $invalid_canonical;
		$before = $GLOBALS['darven_epi_test_options'];

		self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
		self::assertSame( $before, $GLOBALS['darven_epi_test_options'] );
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
			'schema_version' => 2,
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
		$expected = $canonical;
		$expected['compatibility'] = array(
			YithDynamicPricingMode::FIELD => YithDynamicPricingMode::DISABLED,
		);
		self::assertSame( $expected, $GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] );
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
