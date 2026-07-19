<?php

namespace Darven\ExtraPriceInfo\Repositories;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use InvalidArgumentException;

final class SettingsRepository {
	public const OPTION_NAME = 'darven_epi_settings';
	public const SYNC_STATE_OPTION = 'darven_epi_settings_sync_state';

	private const SECTION_NAMES = array(
		'general',
		'positions',
		'display',
		'compatibility',
	);

	private const LEGACY_OPTION_NAMES = array(
		'darven_epi_option_general',
		'darven_epi_option_positions',
		'darven_epi_option_colorsandstyles',
		'darven_epi_option_compatibility',
	);

	/**
	 * @var LegacySettingsAdapter
	 */
	private $adapter;

	public function __construct( LegacySettingsAdapter $adapter ) {
		$this->adapter = $adapter;
	}

	public function getSettings(): array {
		$canonical = get_option( self::OPTION_NAME, array() );

		if ( $this->isCanonicalDocument( $canonical ) ) {
			return $canonical;
		}

		return $this->adapter->fromLegacyOptions( $this->getLegacyOptions() );
	}

	public function getSection( string $section ): array {
		$this->assertKnownSection( $section );

		$settings = $this->getSettings();

		return $settings[ $section ];
	}

	public function saveSection( string $section, array $values ): bool {
		$this->assertKnownSection( $section );

		$settings                   = $this->getSettings();
		$settings['schema_version'] = 1;
		$settings[ $section ]       = $values;

		if ( ! $this->persistAndVerify( self::OPTION_NAME, $settings ) ) {
			return false;
		}

		return $this->persistLegacyOptions(
			$this->adapter->projectToLegacyOptions( $settings, $this->getLegacyOptions() ),
			$section
		);
	}

	private function isCanonicalDocument( $settings ): bool {
		if ( ! is_array( $settings ) || ! isset( $settings['schema_version'] ) || 1 !== $settings['schema_version'] ) {
			return false;
		}

		foreach ( self::SECTION_NAMES as $section ) {
			if ( ! array_key_exists( $section, $settings ) || ! is_array( $settings[ $section ] ) ) {
				return false;
			}
		}

		return true;
	}

	private function getLegacyOptions(): array {
		$legacy_options = array();

		foreach ( self::LEGACY_OPTION_NAMES as $option_name ) {
			$legacy_options[ $option_name ] = get_option( $option_name, array() );
		}

		return $legacy_options;
	}

	private function persistAndVerify( string $option_name, array $value ): bool {
		update_option( $option_name, $value );

		return get_option( $option_name, null ) === $value;
	}

	private function persistLegacyOptions( array $legacy_options, string $section ): bool {
		$failed_options = array();

		foreach ( $legacy_options as $option_name => $option_value ) {
			if ( ! $this->persistAndVerify( $option_name, $option_value ) ) {
				$failed_options[] = $option_name;
			}
		}

		if ( ! empty( $failed_options ) ) {
			update_option(
				self::SYNC_STATE_OPTION,
				array(
					'pending_sections' => array( $section ),
					'failed_options'   => $failed_options,
				)
			);

			return false;
		}

		delete_option( self::SYNC_STATE_OPTION );

		return true;
	}

	private function assertKnownSection( string $section ): void {
		if ( ! in_array( $section, self::SECTION_NAMES, true ) ) {
			throw new InvalidArgumentException( 'Unknown settings section.' );
		}
	}
}
