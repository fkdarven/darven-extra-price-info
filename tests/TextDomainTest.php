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
		$expected_path  = dirname( plugin_basename( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' ) ) . '/languages/';

		self::assertSame( self::DOMAIN, DARVEN_EPI_LANGUAGE_DOMAIN );
		self::assertStringContainsString( 'Text Domain: ' . self::DOMAIN, $plugin_headers );
		( new TextDomainLoader() )->load();

		self::assertSame(
			array(
				'domain' => self::DOMAIN,
				'path'   => $expected_path,
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

		self::assertDirectoryDoesNotExist( DARVEN_EPI_DIR_PATH . 'i18n' );
		self::assertSame(
			array(),
			glob( DARVEN_EPI_DIR_PATH . 'languages/' . self::DOMAIN . '-pt_BR-????????????????????????????????.json' ),
			'Source-path-only JSON catalogues are not resolved by the stable admin handles.'
		);

		$php_files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( DARVEN_EPI_DIR_PATH . 'src' ) );
		foreach ( $php_files as $php_file ) {
			if ( $php_file->isFile() && 'php' === $php_file->getExtension() && false === strpos( $php_file->getPathname(), DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR ) ) {
				self::assertStringNotContainsString( "'darven-epi'", file_get_contents( $php_file->getPathname() ) );
			}
		}
	}

	public function test_pt_br_catalog_has_no_blank_functional_translations(): void {
		$po = file_get_contents( DARVEN_EPI_DIR_PATH . 'languages/' . self::DOMAIN . '-pt_BR.po' );

		self::assertStringNotContainsString( '`r`nmsgstr', $po );

		foreach ( preg_split( '/\R{2,}/', trim( $po ) ) as $entry ) {
			if ( 0 === strpos( ltrim( $entry ), '#~' ) || false !== strpos( $entry, '#, fuzzy' ) ) {
				continue;
			}

			$msgid = $this->readPoField( $entry, 'msgid' );
			if ( null === $msgid || '' === $msgid ) {
				continue;
			}

			self::assertNotSame( '', $this->readPoField( $entry, 'msgstr' ), 'Blank pt_BR translation for: ' . $msgid );
		}
	}

	public function test_both_admin_handles_resolve_complete_aggregated_catalogues(): void {
		$catalogues = array(
			'darven-precos-parcelados-settings'        => array(
				'source'       => 'build/settings/index.js',
				'translations' => array(
					'General' => 'Geral',
					'Automatic mode uses a valid YITH price when available and safely falls back to WooCommerce pricing.' => 'O modo automático usa um preço válido do YITH quando disponível e retorna com segurança aos preços do WooCommerce.',
					'Cash suffix font size' => 'Tamanho da fonte do sufixo do preço à vista',
					'Original price, cash price, installments price' => 'Preço original, preço à vista, preço parcelado',
				),
			),
			'darven-precos-parcelados-product-options' => array(
				'source'       => 'build/product-options/index.js',
				'translations' => array(
					'Installment prices' => 'Preços parcelados',
					'The selected change has not been saved yet.' => 'A alteração selecionada ainda não foi salva.',
					'Saving…' => 'Salvando…',
				),
			),
		);

		foreach ( $catalogues as $handle => $expected ) {
			$path = DARVEN_EPI_DIR_PATH . 'languages/' . self::DOMAIN . '-pt_BR-' . $handle . '.json';
			self::assertFileExists( $path );

			$json = json_decode( file_get_contents( $path ), true );
			self::assertIsArray( $json, 'Invalid JSON catalogue: ' . $path );
			self::assertSame( $expected['source'], $json['source'] );
			self::assertSame( self::DOMAIN, $json['domain'] );
			self::assertSame( 'pt_BR', $json['locale_data'][ self::DOMAIN ]['']['lang'] );

			$messages = $json['locale_data'][ self::DOMAIN ];
			foreach ( $expected['translations'] as $msgid => $msgstr ) {
				self::assertSame( $msgstr, $messages[ $msgid ][0], $handle . ' is missing: ' . $msgid );
			}

			foreach ( $messages as $msgid => $translation ) {
				if ( '' === $msgid ) {
					continue;
				}

				self::assertNotSame( '', $translation[0], $handle . ' has a blank translation for: ' . $msgid );
			}
		}
	}

	private function readPoField( string $entry, string $field ): ?string {
		if ( 1 !== preg_match( '/^' . preg_quote( $field, '/' ) . ' "(.*)"((?:\R".*")*)/m', $entry, $matches ) ) {
			return null;
		}

		$value = stripcslashes( $matches[1] );
		if ( '' !== $matches[2] ) {
			preg_match_all( '/^"(.*)"$/m', trim( $matches[2] ), $continuations );
			foreach ( $continuations[1] as $continuation ) {
				$value .= stripcslashes( $continuation );
			}
		}

		return $value;
	}
}
