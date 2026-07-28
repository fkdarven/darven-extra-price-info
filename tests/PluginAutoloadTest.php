<?php

use PHPUnit\Framework\TestCase;

final class PluginAutoloadTest extends TestCase {
	public function test_declares_the_psr4_namespace_and_loads_the_runtime_autoloader(): void {
		$composer = json_decode( file_get_contents( DARVEN_EPI_DIR_PATH . 'composer.json' ), true );
		$plugin   = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );

		self::assertSame(
			'src/',
			$composer['autoload']['psr-4']['Darven\\ExtraPriceInfo\\']
		);
		self::assertStringContainsString( 'vendor/autoload.php', $plugin );
	}
}
