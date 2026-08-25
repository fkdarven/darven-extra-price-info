<?php

namespace Darven\ExtraPriceInfo\Repositories;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\YithDynamicPricingMode;
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

	/**
	 * @var SettingsSanitizer
	 */
	private $sanitizer;

	public function __construct( LegacySettingsAdapter $adapter, ?SettingsSanitizer $sanitizer = null ) {
		$this->adapter   = $adapter;
		$this->sanitizer = $sanitizer ?? new SettingsSanitizer();
	}

	public function getSettings(): array {
		return $this->getNormalizedSettings();
	}

	/**
	 * Returns a sanitized v2 document without migrating legacy or v1 storage.
	 */
	public function getNormalizedSettings(): array {
		$canonical      = get_option( self::OPTION_NAME, null );
		$legacy_options = $this->getLegacyOptions();
		$has_persisted_darven_settings = null !== $canonical || $this->hasPersistedLegacySettings( $legacy_options );

		if ( $this->isReadableDocument( $canonical ) ) {
			return $this->normalizeDocument( $canonical, $has_persisted_darven_settings, $legacy_options );
		}

		return $this->normalizeDocument( $this->adapter->fromLegacyOptions( $legacy_options ), $has_persisted_darven_settings, $legacy_options );
	}

	public function getSection( string $section ): array {
		$this->assertKnownSection( $section );

		$settings = $this->getNormalizedSettings();

		return $settings[ $section ];
	}

	public function getYithDynamicPricingMode(): string {
		$settings = $this->getNormalizedSettings();

		return $settings['compatibility'][ YithDynamicPricingMode::FIELD ];
	}

	public function saveSection( string $section, array $values, string $deferred_legacy_option = '' ): bool {
		$this->assertKnownSection( $section );

		$settings             = $this->getNormalizedSettings();
		$settings[ $section ] = $values;

		return $this->saveDocumentInternal( $settings, array( $section ), $deferred_legacy_option, array( $section ) );
	}

	/**
	 * Merges submitted sections with the normalized current state, stores v2,
	 * and mirrors every legacy option.
	 */
	public function saveDocument( array $document ): bool {
		$sections = array();
		foreach ( self::SECTION_NAMES as $section ) {
			if ( isset( $document[ $section ] ) && is_array( $document[ $section ] ) ) {
				$sections[] = $section;
			}
		}

		return $this->saveDocumentInternal( $document, $sections, '' );
	}

	private function saveDocumentInternal( array $document, array $submitted_sections, string $deferred_legacy_option, array $replaced_sections = array() ): bool {
		$settings = $this->getNormalizedSettings();

		foreach ( self::SECTION_NAMES as $section ) {
			if ( isset( $document[ $section ] ) && is_array( $document[ $section ] ) ) {
				$settings[ $section ] = in_array( $section, $replaced_sections, true )
					? $document[ $section ]
					: array_merge( $settings[ $section ], $document[ $section ] );
			}
		}

		$settings = $this->normalizeDocument( $settings, true );

		if ( ! $this->persistAndVerify( self::OPTION_NAME, $settings ) ) {
			return false;
		}

		return $this->persistLegacyOptions(
			$this->adapter->projectToLegacyOptions( $settings, $this->getLegacyOptions() ), empty( $submitted_sections ) ? self::SECTION_NAMES : $submitted_sections, $deferred_legacy_option
		);
	}

	public function recordPendingSync( string $section, string $failed_option ): bool {
		$this->assertKnownSection( $section );

		return $this->persistAndVerify(
			self::SYNC_STATE_OPTION,
			array(
				'pending_sections' => array( $section ),
				'failed_options'   => array( $failed_option ),
			)
		);
	}

	private function isReadableDocument( $settings ): bool {
		if ( ! is_array( $settings ) || ! isset( $settings['schema_version'] ) || ! in_array( $settings['schema_version'], array( 1, 2 ), true ) ) {
			return false;
	}

	foreach ( self::SECTION_NAMES as $section ) {
		if ( ! array_key_exists( $section, $settings ) || ! is_array( $settings[ $section ] ) ) {
			return false;
		}
	}

		return true;
	}

	private function normalizeDocument( array $document, bool $has_persisted_darven_settings, array $legacy_options = array() ): array {
		$normalized = array( 'schema_version' => 2 );

		foreach ( self::SECTION_NAMES as $section ) {
			$values = isset( $document[ $section ] ) && is_array( $document[ $section ] ) ? $document[ $section ] : array();
			if ( 'compatibility' === $section ) {
				if ( ! isset( $values[ YithDynamicPricingMode::FIELD ] )
					&& ! isset( $values[ YithDynamicPricingMode::LEGACY_FIELD ] )
					&& isset( $legacy_options['darven_epi_option_compatibility'] )
					&& is_array( $legacy_options['darven_epi_option_compatibility'] )
					&& isset( $legacy_options['darven_epi_option_compatibility'][ YithDynamicPricingMode::LEGACY_FIELD ] ) ) {
					$values[ YithDynamicPricingMode::LEGACY_FIELD ] = $legacy_options['darven_epi_option_compatibility'][ YithDynamicPricingMode::LEGACY_FIELD ];
				}

				$mode = YithDynamicPricingMode::resolve( $values, $has_persisted_darven_settings );
				$normalized[ $section ] = array(
					YithDynamicPricingMode::FIELD => $mode,
				);
				continue;
			}

			$normalized[ $section ] = $this->sanitizer->sanitizeSection( $section, $values );
		}

		return $normalized;
	}

	private function getLegacyOptions(): array {
		$legacy_options = array();

		foreach ( self::LEGACY_OPTION_NAMES as $option_name ) {
			$legacy_options[ $option_name ] = get_option( $option_name, null );
		}

		return $legacy_options;
	}

	private function hasPersistedLegacySettings( array $legacy_options ): bool {
		foreach ( $legacy_options as $legacy_option ) {
			if ( null !== $legacy_option ) {
				return true;
			}
		}

		return false;
	}

	private function persistAndVerify( string $option_name, array $value ): bool {
		update_option( $option_name, $value );

		return get_option( $option_name, null ) === $value;
	}

	private function persistLegacyOptions( array $legacy_options, array $sections, string $deferred_legacy_option ): bool {
		$failed_options = array();

		foreach ( $legacy_options as $option_name => $option_value ) {
			if ( $option_name === $deferred_legacy_option ) {
				continue;
			}

			if ( ! $this->persistAndVerify( $option_name, $option_value ) ) {
				$failed_options[] = $option_name;
			}
		}

		if ( ! empty( $failed_options ) ) {
			if ( ! $this->persistAndVerify(
				self::SYNC_STATE_OPTION, array(
					'pending_sections' => $sections,
					'failed_options'   => $failed_options,
				)
			) ) {
				return false;
			}

			return false;
		}

		delete_option( self::SYNC_STATE_OPTION );

		return null === get_option( self::SYNC_STATE_OPTION, null );
	}

	private function assertKnownSection( string $section ): void {
		if ( ! in_array( $section, self::SECTION_NAMES, true ) ) {
			throw new InvalidArgumentException( 'Unknown settings section.' );
		}
	}
}
