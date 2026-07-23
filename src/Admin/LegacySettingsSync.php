<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class LegacySettingsSync {
	/**
	 * @var bool
	 */
	private static $is_syncing = false;

	/**
	 * @var array<string,array{section:string}>
	 */
	private static $pending_syncs = array();

	/**
	 * @var array<string,bool>
	 */
	private static $registered_legacy_options = array();

	/**
	 * @var bool
	 */
	private static $is_shutdown_registered = false;

	public static function save( string $section, array $sanitized_values ): array {
		if ( self::$is_syncing ) {
			return $sanitized_values;
		}

		$adapter              = new LegacySettingsAdapter();
		$legacy_option_name   = $adapter->getLegacyOptionName( $section );
		$current_legacy_value = get_option( $legacy_option_name, null );
		$projected_legacy_values = $adapter->projectSectionToLegacyOption(
			$section,
			$sanitized_values,
			$current_legacy_value
		);

		if ( null !== $current_legacy_value && $current_legacy_value === $projected_legacy_values ) {
			self::synchronizePersistedLegacyOption( $section, $sanitized_values, $legacy_option_name );

			return $projected_legacy_values;
		}

		self::queuePendingSync( $legacy_option_name, $section );

		return $projected_legacy_values;
	}

	public static function syncAfterLegacyOptionUpdate( $old_value, $new_value, $option_name ): void {
		self::completePendingSync( (string) $option_name, $new_value );
	}

	public static function syncAfterLegacyOptionAdd( $option_name, $value ): void {
		self::completePendingSync( (string) $option_name, $value );
	}

	public static function recordFailedLegacySaves(): void {
		foreach ( array_keys( self::$pending_syncs ) as $legacy_option_name ) {
			$pending_sync = self::$pending_syncs[ $legacy_option_name ];

			self::removePendingSync( $legacy_option_name );

			$repository = new SettingsRepository( new LegacySettingsAdapter() );
			$repository->recordPendingSync( $pending_sync['section'], $legacy_option_name );
		}
	}

	private static function queuePendingSync( string $legacy_option_name, string $section ): void {
		self::$pending_syncs[ $legacy_option_name ] = array(
			'section' => $section,
		);

		if ( ! isset( self::$registered_legacy_options[ $legacy_option_name ] ) ) {
			add_action(
				'update_option_' . $legacy_option_name,
				array( __CLASS__, 'syncAfterLegacyOptionUpdate' ),
				10,
				3
			);
			add_action(
				'add_option_' . $legacy_option_name,
				array( __CLASS__, 'syncAfterLegacyOptionAdd' ),
				10,
				2
			);
			self::$registered_legacy_options[ $legacy_option_name ] = true;
		}

		if ( ! self::$is_shutdown_registered ) {
			add_action( 'shutdown', array( __CLASS__, 'recordFailedLegacySaves' ) );
			self::$is_shutdown_registered = true;
		}
	}

	private static function completePendingSync( string $legacy_option_name, $legacy_value ): void {
		if ( ! isset( self::$pending_syncs[ $legacy_option_name ] ) ) {
			return;
		}

		$pending_sync = self::$pending_syncs[ $legacy_option_name ];

		self::removePendingSync( $legacy_option_name );

		$adapter = new LegacySettingsAdapter();
		self::synchronizePersistedLegacyOption(
			$pending_sync['section'],
			$adapter->fromLegacyOption( $pending_sync['section'], $legacy_value ),
			$legacy_option_name
		);
	}

	private static function synchronizePersistedLegacyOption( string $section, array $values, string $legacy_option_name ): void {
		if ( self::$is_syncing ) {
			return;
		}

		self::$is_syncing = true;

		try {
			$repository = new SettingsRepository( new LegacySettingsAdapter() );

			if ( ! $repository->saveSection( $section, $values, $legacy_option_name ) ) {
				add_settings_error(
					'darven_epi_option_group',
					'darven_epi_legacy_sync_failed',
					__( 'Settings were saved, but compatibility synchronization needs another save attempt.', 'darven-epi' )
				);
			}
		} finally {
			self::$is_syncing = false;
		}
	}

	private static function removePendingSync( string $legacy_option_name ): void {
		unset( self::$pending_syncs[ $legacy_option_name ] );
		unset( self::$registered_legacy_options[ $legacy_option_name ] );
		remove_action(
			'update_option_' . $legacy_option_name,
			array( __CLASS__, 'syncAfterLegacyOptionUpdate' )
		);
		remove_action(
			'add_option_' . $legacy_option_name,
			array( __CLASS__, 'syncAfterLegacyOptionAdd' )
		);

		if ( empty( self::$pending_syncs ) && self::$is_shutdown_registered ) {
			remove_action( 'shutdown', array( __CLASS__, 'recordFailedLegacySaves' ) );
			self::$is_shutdown_registered = false;
		}
	}
}
