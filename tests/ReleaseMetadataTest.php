<?php

use PHPUnit\Framework\TestCase;

final class ReleaseMetadataTest extends TestCase {
	public function test_declares_the_3_3_1_release_metadata(): void {
		$plugin_file = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		$readme      = file_get_contents( DARVEN_EPI_DIR_PATH . 'readme.txt' );

		self::assertIsString( $plugin_file );
		self::assertIsString( $readme );
		self::assertStringContainsString( 'Version: 3.3.1', $plugin_file );
		self::assertStringContainsString( "const DARVEN_EPI_VERSION = '3.3.1';", $plugin_file );
		self::assertStringContainsString( 'Tested up to: 7.0', $readme );
		self::assertStringContainsString( 'Stable tag: 3.3.1', $readme );
		self::assertStringContainsString( '= 3.3.1 =', $readme );
	}
}
