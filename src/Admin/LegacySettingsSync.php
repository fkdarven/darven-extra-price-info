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
			$repository = new SettingsRepository( new LegacySettingsAdapter() );

			if ( ! $repository->saveSection( $section, $sanitized_values ) ) {
				add_settings_error(
					'darven_epi_option_group',
					'darven_epi_legacy_sync_failed',
					__( 'Settings were saved, but compatibility synchronization needs another save attempt.', 'darven-epi' )
				);
			}

			return $sanitized_values;
		} finally {
			self::$is_syncing = false;
		}
	}
}
