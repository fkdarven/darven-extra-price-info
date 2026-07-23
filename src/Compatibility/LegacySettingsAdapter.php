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
			$canonical_section = isset( $settings[ $section ] ) && is_array( $settings[ $section ] )
				? $settings[ $section ]
				: array();

			$projected_options[ $option_name ] = $this->projectSectionToLegacyOption(
				$section,
				$canonical_section,
				$existing_options[ $option_name ] ?? array()
			);
		}

		return $projected_options;
	}

	public function getLegacyOptionName( string $section ): string {
		if ( ! isset( self::OPTION_BY_SECTION[ $section ] ) ) {
			throw new \InvalidArgumentException( 'Unknown settings section.' );
		}

		return self::OPTION_BY_SECTION[ $section ];
	}

	public function fromLegacyOption( string $section, $legacy_option ): array {
		$this->getLegacyOptionName( $section );

		return $this->getPluginOwnedValues( is_array( $legacy_option ) ? $legacy_option : array() );
	}

	public function projectSectionToLegacyOption( string $section, array $canonical_section, $existing_option ): array {
		$legacy_option = is_array( $existing_option ) ? $existing_option : array();
		$canonical_values = $this->getPluginOwnedValues( $canonical_section );
		$projected_option = array();

		$this->getLegacyOptionName( $section );

		foreach ( $legacy_option as $key => $value ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'darven_epi_' ) ) {
				if ( array_key_exists( $key, $canonical_values ) ) {
					$projected_option[ $key ] = $canonical_values[ $key ];
					unset( $canonical_values[ $key ] );
				}

				continue;
			}

			$projected_option[ $key ] = $value;
		}

		foreach ( $canonical_values as $key => $value ) {
			$projected_option[ $key ] = $value;
		}

		return $projected_option;
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
