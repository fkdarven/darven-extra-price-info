<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class LegacySettingsSync {
	/**
	 * @var bool
	 */
	private static $is_syncing = false;

	public static function save( string $section, array $sanitized_values ): array {
		if ( self::$is_syncing ) {
			return $sanitized_values;
		}

		self::$is_syncing = true;

		try {
			$adapter                 = new LegacySettingsAdapter();
			$legacy_option_name      = $adapter->getLegacyOptionName( $section );
			$projected_legacy_values = $adapter->projectSectionToLegacyOption(
				$section,
				$sanitized_values,
				get_option( $legacy_option_name, array() )
			);
			$repository              = new SettingsRepository( $adapter );

			if ( ! $repository->saveSection( $section, $sanitized_values, $legacy_option_name ) ) {
				add_settings_error(
					'darven_epi_option_group',
					'darven_epi_legacy_sync_failed',
					__( 'Settings were saved, but compatibility synchronization needs another save attempt.', 'darven-epi' )
				);
			}

			return $projected_legacy_values;
		} finally {
			self::$is_syncing = false;
		}
	}
}
