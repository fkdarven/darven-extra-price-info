<?php

namespace Darven\ExtraPriceInfo\Setup;

final class TextDomainLoader {
	public function load(): void {
		load_plugin_textdomain(
			'darven-epi',
			false,
			dirname( plugin_basename( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' ) ) . '/i18n/languages/'
		);
	}
}
