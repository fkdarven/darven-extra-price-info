<?php

namespace Darven\ExtraPriceInfo\Compatibility;

final class LegacySettingsAdapter {
	private const OPTION_BY_SECTION = array(
		'general'       => 'darven_epi_option_general',
		'positions'     => 'darven_epi_option_positions',
		'display'       => 'darven_epi_option_colorsandstyles',
		'compatibility' => 'darven_epi_option_compatibility',
	);

	public function fromLegacyOptions( array $legacy_options ): array {
		$settings = array(
			'schema_version' => 1,
		);

		foreach ( self::OPTION_BY_SECTION as $section => $option_name ) {
			$legacy_option = isset( $legacy_options[ $option_name ] ) && is_array( $legacy_options[ $option_name ] )
				? $legacy_options[ $option_name ]
				: array();

			$settings[ $section ] = $this->getPluginOwnedValues( $legacy_option );
		}

		return $settings;
	}

	public function projectToLegacyOptions( array $settings, array $existing_options ): array {
		$projected_options = array();

		foreach ( self::OPTION_BY_SECTION as $section => $option_name ) {
			$legacy_option = isset( $existing_options[ $option_name ] ) && is_array( $existing_options[ $option_name ] )
				? $existing_options[ $option_name ]
				: array();
			$canonical_section = isset( $settings[ $section ] ) && is_array( $settings[ $section ] )
				? $settings[ $section ]
				: array();

			foreach ( $canonical_section as $key => $value ) {
				$legacy_option[ $key ] = $value;
			}

			$projected_options[ $option_name ] = $legacy_option;
		}

		return $projected_options;
	}

	private function getPluginOwnedValues( array $legacy_option ): array {
		$plugin_owned_values = array();

		foreach ( $legacy_option as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'darven_epi_' ) ) {
				$plugin_owned_values[ $key ] = $value;
			}
		}

		return $plugin_owned_values;
	}
}
