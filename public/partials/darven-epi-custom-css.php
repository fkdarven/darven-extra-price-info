<?php

$settings_repository = new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
	new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
);

echo ( new \Darven\ExtraPriceInfo\Frontend\InlineStyles( $settings_repository ) )->render();
