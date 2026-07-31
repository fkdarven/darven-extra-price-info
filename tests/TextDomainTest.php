<?php

use Darven\ExtraPriceInfo\Admin\Assets;
use Darven\ExtraPriceInfo\Setup\TextDomainLoader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/AdminAssetsDoubles.php';

final class TextDomainTest extends TestCase {
	private const DOMAIN = 'darven-multiplos-precos-informativos';

	protected function setUp(): void {
		$_GET = array();
		$GLOBALS['darven_epi_test_script_translations'] = array();
		$GLOBALS['darven_epi_test_loaded_textdomains'] = array();
	}

	public function test_loads_the_official_domain_from_the_runtime_languages_directory(): void {
		$plugin_headers = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php', false, null, 0, 8192 );

		self::assertSame( self::DOMAIN, DARVEN_EPI_LANGUAGE_DOMAIN );
		self::assertStringContainsString( 'Text Domain: ' . self::DOMAIN, $plugin_headers );
		( new TextDomainLoader() )->load();

		self::assertSame(
			array(
				'domain' => self::DOMAIN,
				'path'   => 'languages/',
			),
			$GLOBALS['darven_epi_test_loaded_textdomains'][0]
		);
	}

	public function test_registers_script_translations_for_both_admin_bundles(): void {
		$_GET = array( 'page' => 'darven-epi-admin' );
		( new Assets() )->enqueue();

		$_GET = array( 'post' => '42' );
		$GLOBALS['darven_epi_test_screen'] = (object) array( 'post_type' => 'product', 'base' => 'post' );
		( new Assets() )->enqueue();

		self::assertSame(
			array(
				array(
					'handle' => 'darven-precos-parcelados-settings',
					'domain' => self::DOMAIN,
					'path'   => DARVEN_EPI_DIR_PATH . 'languages',
				),
				array(
					'handle' => 'darven-precos-parcelados-product-options',
					'domain' => self::DOMAIN,
					'path'   => DARVEN_EPI_DIR_PATH . 'languages',
				),
			),
			$GLOBALS['darven_epi_test_script_translations']
		);
	}

	public function test_ships_complete_portuguese_translation_artifacts_without_old_active_domain_calls(): void {
		foreach ( array( '.pot', '-pt_BR.po', '-pt_BR.mo' ) as $suffix ) {
			self::assertFileExists( DARVEN_EPI_DIR_PATH . 'languages/' . self::DOMAIN . $suffix );
		}

		self::assertNotEmpty( glob( DARVEN_EPI_DIR_PATH . 'languages/' . self::DOMAIN . '-pt_BR-*.json' ) );

		$php_files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( DARVEN_EPI_DIR_PATH . 'src' ) );
		foreach ( $php_files as $php_file ) {
			if ( $php_file->isFile() && 'php' === $php_file->getExtension() ) {
				self::assertStringNotContainsString( "'darven-epi'", file_get_contents( $php_file->getPathname() ) );
			}
		}
	}
}
