<?php

use PHPUnit\Framework\TestCase;

final class PluginMetadataTest extends TestCase {
	public function test_declares_runtime_and_plugin_requirements(): void {
		$plugin_headers = file_get_contents(
			DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php',
			false,
			null,
			0,
			8192
		);

		self::assertIsString( $plugin_headers );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Requires PHP:\s*7\.4\s*$/mi', $plugin_headers );
		self::assertMatchesRegularExpression( '/^[ \t*#@]*Requires Plugins:\s*woocommerce\s*$/mi', $plugin_headers );
	}
}
