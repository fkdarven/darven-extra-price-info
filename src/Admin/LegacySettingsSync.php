<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class LegacySettingsSync {
	public static function save( string $section, array $sanitized_values ): array {
		$repository = new SettingsRepository( new LegacySettingsAdapter() );

		if ( ! $repository->saveSection( $section, $sanitized_values ) ) {
			add_settings_error(
				'darven_epi_option_group',
				'darven_epi_legacy_sync_failed',
				__( 'Settings were saved, but compatibility synchronization needs another save attempt.', 'darven-epi' )
			);
		}

		return $sanitized_values;
	}
}
