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
		$canonical = get_option( self::OPTION_NAME, array() );

		if ( $this->isReadableDocument( $canonical ) ) {
			return $this->normalizeDocument( $canonical );
		}

		return $this->normalizeDocument( $this->adapter->fromLegacyOptions( $this->getLegacyOptions() ) );
	}

	public function getSection( string $section ): array {
		$this->assertKnownSection( $section );

		$settings = $this->getNormalizedSettings();

		return $settings[ $section ];
	}

	public function getYithDynamicPricingMode(): string {
		$canonical                     = get_option( self::OPTION_NAME, null );
		$has_persisted_darven_settings = null !== $canonical;
		$legacy_options                 = array();

		foreach ( self::LEGACY_OPTION_NAMES as $option_name ) {
			$legacy_option = get_option( $option_name, null );

			if ( null !== $legacy_option ) {
				$has_persisted_darven_settings = true;
			}

			$legacy_options[ $option_name ] = null === $legacy_option ? array() : $legacy_option;
		}

		$settings = $this->isReadableDocument( $canonical )
			? $this->normalizeDocument( $canonical )
			: $this->normalizeDocument( $this->adapter->fromLegacyOptions( $legacy_options ) );

		$compatibility = isset( $settings['compatibility'] ) && is_array( $settings['compatibility'] )
			? $settings['compatibility']
			: array();

		$legacy_compatibility = $legacy_options['darven_epi_option_compatibility'];

		if ( ! isset( $compatibility[ YithDynamicPricingMode::FIELD ] )
			&& is_array( $legacy_compatibility )
			&& isset( $legacy_compatibility[ YithDynamicPricingMode::LEGACY_FIELD ] ) ) {
			$compatibility[ YithDynamicPricingMode::LEGACY_FIELD ] = $legacy_compatibility[ YithDynamicPricingMode::LEGACY_FIELD ];
		}

		return YithDynamicPricingMode::resolve( $compatibility, $has_persisted_darven_settings );
	}

	public function saveSection( string $section, array $values, string $deferred_legacy_option = '' ): bool {
		$this->assertKnownSection( $section );

		$settings             = $this->getNormalizedSettings();
		$settings[ $section ] = $values;

		return $this->saveDocumentInternal( $settings, array( $section ), $deferred_legacy_option );
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

	private function saveDocumentInternal( array $document, array $submitted_sections, string $deferred_legacy_option ): bool {
		$settings = $this->getNormalizedSettings();

		foreach ( self::SECTION_NAMES as $section ) {
			if ( isset( $document[ $section ] ) && is_array( $document[ $section ] ) ) {
				$settings[ $section ] = array_merge( $settings[ $section ], $document[ $section ] );
			}
		}

		$settings = $this->normalizeDocument( $settings );

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

	private function normalizeDocument( array $document ): array {
		$normalized = array( 'schema_version' => 2 );

		foreach ( self::SECTION_NAMES as $section ) {
			$values = isset( $document[ $section ] ) && is_array( $document[ $section ] ) ? $document[ $section ] : array();
			if ( 'compatibility' === $section && ! isset( $values[ YithDynamicPricingMode::FIELD ] ) ) {
				$normalized[ $section ] = isset( $values[ YithDynamicPricingMode::LEGACY_FIELD ] )
					&& YithDynamicPricingMode::LEGACY_FIELD === $values[ YithDynamicPricingMode::LEGACY_FIELD ]
					? array( YithDynamicPricingMode::LEGACY_FIELD => YithDynamicPricingMode::LEGACY_FIELD )
					: array();
				continue;
			}

			$normalized[ $section ] = $this->sanitizer->sanitizeSection( $section, $values );
		}

		return $normalized;
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
