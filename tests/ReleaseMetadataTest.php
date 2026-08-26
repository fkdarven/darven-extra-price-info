<?php

use PHPUnit\Framework\TestCase;

final class ReleaseMetadataTest extends TestCase {
	public function test_declares_the_4_0_0_release_metadata(): void {
		$plugin_file = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		$readme      = file_get_contents( DARVEN_EPI_DIR_PATH . 'readme.txt' );

		self::assertIsString( $plugin_file );
		self::assertIsString( $readme );
		self::assertStringContainsString( 'Version: 4.0.0', $plugin_file );
		self::assertStringContainsString( "const DARVEN_EPI_VERSION = '4.0.0';", $plugin_file );
		self::assertStringContainsString( 'Tested up to: 7.1', $readme );
		self::assertStringContainsString( 'Stable tag: 4.0.0', $readme );
		self::assertStringContainsString( '= 4.0.0 =', $readme );
	}

	public function test_readme_screenshot_descriptions_match_published_assets(): void {
		$readme = file_get_contents( DARVEN_EPI_DIR_PATH . 'readme.txt' );

		self::assertIsString( $readme );
		self::assertSame( 1, preg_match( '/== Screenshots ==\R(?<screenshots>.*?)\R== Changelog ==/s', $readme, $matches ) );
		self::assertGreaterThan( 0, preg_match_all( '/^(?<number>[1-9][0-9]*)\.\s+.+$/m', $matches['screenshots'], $descriptions ) );

		$asset_paths   = array_filter(
			glob( DARVEN_EPI_DIR_PATH . 'wordpress-org-assets/screenshot-*.png' ),
			static function ( string $path ): bool {
				return 1 === preg_match( '/screenshot-[0-9]+\.png$/', $path );
			}
		);
		$asset_numbers = array_map(
			static function ( string $path ): int {
				preg_match( '/screenshot-(?<number>[0-9]+)\.png$/', $path, $asset_match );

				return (int) $asset_match['number'];
			},
			$asset_paths
		);

		sort( $asset_numbers );

		self::assertSame( $asset_numbers, array_map( 'intval', $descriptions['number'] ) );
	}
}
