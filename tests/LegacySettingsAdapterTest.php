<?php

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use PHPUnit\Framework\TestCase;

final class LegacySettingsAdapterTest extends TestCase {
	public function test_normalizes_plugin_owned_legacy_values_into_canonical_sections(): void {
		$adapter  = new LegacySettingsAdapter();
		$settings = $adapter->fromLegacyOptions( $this->get_legacy_options() );

		self::assertSame( 1, $settings['schema_version'] );
		self::assertSame(
			array(
				'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
				'darven_epi_max_installments'  => '6',
			),
			$settings['general']
		);
		self::assertSame(
			array(
				'darven_epi_single_product_position' => 'third',
			),
			$settings['positions']
		);
		self::assertSame(
			array(
				'darven_epi_color_of_incash_price' => '#123456',
			),
			$settings['display']
		);
		self::assertSame(
			array(
				'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
			),
			$settings['compatibility']
		);

		self::assertArrayNotHasKey( 'third_party_general_key', $settings['general'] );
		self::assertArrayNotHasKey( 'third_party_position_key', $settings['positions'] );
		self::assertArrayNotHasKey( 'third_party_display_key', $settings['display'] );
		self::assertArrayNotHasKey( 'third_party_compatibility_key', $settings['compatibility'] );
	}

	public function test_projects_canonical_values_without_overwriting_third_party_legacy_values(): void {
		$adapter = new LegacySettingsAdapter();
		$result  = $adapter->projectToLegacyOptions(
			array(
				'schema_version' => 1,
				'general'        => array(
					'darven_epi_max_installments' => '12',
				),
				'positions'      => array(),
				'display'        => array(),
				'compatibility'  => array(),
			),
			$this->get_legacy_options()
		);

		self::assertSame( '12', $result['darven_epi_option_general']['darven_epi_max_installments'] );
		self::assertSame( 'retain', $result['darven_epi_option_general']['third_party_general_key'] );
		self::assertSame( 'retain', $result['darven_epi_option_positions']['third_party_position_key'] );
		self::assertSame( 'retain', $result['darven_epi_option_colorsandstyles']['third_party_display_key'] );
		self::assertSame( 'retain', $result['darven_epi_option_compatibility']['third_party_compatibility_key'] );
	}

	public function test_uses_empty_sections_for_missing_or_non_array_legacy_options(): void {
		$adapter  = new LegacySettingsAdapter();
		$settings = $adapter->fromLegacyOptions(
			array(
				'darven_epi_option_general'         => 'not-an-array',
				'darven_epi_option_positions'       => null,
				'darven_epi_option_colorsandstyles' => array(),
			)
		);

		self::assertSame(
			array(
				'schema_version' => 1,
				'general'        => array(),
				'positions'      => array(),
				'display'        => array(),
				'compatibility'  => array(),
			),
			$settings
		);
	}

	public function test_projects_all_option_arrays_when_existing_options_are_missing_or_non_arrays(): void {
		$adapter = new LegacySettingsAdapter();
		$result  = $adapter->projectToLegacyOptions(
			array(
				'general' => array(
					'darven_epi_max_installments' => '9',
				),
			),
			array(
				'darven_epi_option_general' => 'not-an-array',
			)
		);

		self::assertSame(
			array(
				'darven_epi_option_general' => array(
					'darven_epi_max_installments' => '9',
				),
				'darven_epi_option_positions' => array(),
				'darven_epi_option_colorsandstyles' => array(),
				'darven_epi_option_compatibility' => array(),
			),
			$result
		);
	}

	private function get_legacy_options(): array {
		return array(
			'darven_epi_option_general' => array(
				'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
				'darven_epi_max_installments'  => '6',
				'third_party_general_key'       => 'retain',
			),
			'darven_epi_option_positions' => array(
				'darven_epi_single_product_position' => 'third',
				'third_party_position_key'            => 'retain',
			),
			'darven_epi_option_colorsandstyles' => array(
				'darven_epi_color_of_incash_price' => '#123456',
				'third_party_display_key'          => 'retain',
			),
			'darven_epi_option_compatibility' => array(
				'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
				'third_party_compatibility_key'                    => 'retain',
			),
		);
	}
}
