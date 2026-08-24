<?php
/**
 * Builds deterministic PHP and JavaScript translation catalogues.
 *
 * Usage: php scripts/build-translations.php
 *
 * @package darven-extra-price-info
 */

// This standalone CLI tool intentionally uses filesystem and process APIs outside WordPress.
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions

const DARVEN_EPI_TRANSLATION_DOMAIN        = 'darven-multiplos-precos-informativos';
const DARVEN_EPI_TRANSLATION_LOCALE        = 'pt_BR';
const DARVEN_EPI_TRANSLATION_POT_DATE      = '2026-07-31T00:00:00+00:00';
const DARVEN_EPI_TRANSLATION_REVISION_DATE = '2026-07-31 00:00+0000';
const DARVEN_EPI_TRANSLATION_BUG_REPORT_URL = 'https://wordpress.org/support/plugin/darven-extra-price-info';

/**
 * Stops the build with a concise diagnostic.
 *
 * @param string $message Error message.
 * @return void
 */
function darven_epi_translation_fail( string $message ): void {
	fwrite( STDERR, 'Translation build failed: ' . $message . PHP_EOL );
	exit( 1 );
}

/**
 * Returns the command prefix used to invoke WP-CLI.
 *
 * WP_CLI_BINARY may point to a PHAR or executable. Laragon is discovered relative
 * to PHP_BINARY so this script never embeds a checkout or worktree path.
 *
 * @return array<int, string>
 */
function darven_epi_translation_wp_cli_command(): array {
	$wp_cli = getenv( 'WP_CLI_BINARY' );

	if ( false !== $wp_cli && '' !== $wp_cli ) {
		return 'phar' === strtolower( pathinfo( $wp_cli, PATHINFO_EXTENSION ) )
			? array( PHP_BINARY, $wp_cli )
			: array( $wp_cli );
	}

	$laragon_phar = dirname( dirname( dirname( PHP_BINARY ) ) ) . DIRECTORY_SEPARATOR . 'wp-cli' . DIRECTORY_SEPARATOR . 'wp-cli.phar';
	if ( is_file( $laragon_phar ) ) {
		return array( PHP_BINARY, $laragon_phar );
	}

	return array( 'wp' );
}

/**
 * Executes a WP-CLI i18n command.
 *
 * @param array<int, string> $arguments Command arguments.
 * @return void
 */
function darven_epi_translation_run_wp_cli( array $arguments ): void {
	$parts   = array_merge( darven_epi_translation_wp_cli_command(), $arguments, array( '--allow-root', '--quiet' ) );
	$command = implode( ' ', array_map( 'escapeshellarg', $parts ) ) . ' 2>&1';
	$output  = array();
	$status  = 0;

	exec( $command, $output, $status );

	if ( 0 !== $status ) {
		darven_epi_translation_fail( implode( PHP_EOL, $output ) );
	}
}

/**
 * Reads a scalar gettext field from an entry block.
 *
 * @param string $entry PO entry block.
 * @param string $field Field name.
 * @return string|null
 */
function darven_epi_translation_read_po_field( string $entry, string $field ): ?string {
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

/**
 * Rejects untranslated active entries.
 *
 * @param string $po_path PO file path.
 * @return void
 */
function darven_epi_translation_assert_complete_po( string $po_path ): void {
	$contents = file_get_contents( $po_path );
	if ( false === $contents ) {
		darven_epi_translation_fail( 'Unable to read ' . $po_path );
	}

	$blank_msgids = array();
	foreach ( preg_split( '/\R{2,}/', trim( $contents ) ) as $entry ) {
		if ( 0 === strpos( ltrim( $entry ), '#~' ) || false !== strpos( $entry, '#, fuzzy' ) ) {
			continue;
		}

		$msgid = darven_epi_translation_read_po_field( $entry, 'msgid' );
		if ( null === $msgid || '' === $msgid ) {
			continue;
		}

		if ( '' === darven_epi_translation_read_po_field( $entry, 'msgstr' ) ) {
			$blank_msgids[] = $msgid;
		}
	}

	if ( ! empty( $blank_msgids ) ) {
		darven_epi_translation_fail( 'Blank pt_BR translations: ' . implode( ' | ', $blank_msgids ) );
	}
}

/**
 * Normalizes volatile gettext headers.
 *
 * @param string      $path     Catalogue path.
 * @param string|null $language Catalogue language, or null for a POT template.
 * @return void
 */
function darven_epi_translation_normalize_headers( string $path, ?string $language = null ): void {
	$contents = file_get_contents( $path );
	if ( false === $contents ) {
		darven_epi_translation_fail( 'Unable to read ' . $path );
	}

	$contents = str_replace( "\r\n", "\n", $contents );
	$contents = preg_replace( '/"Report-Msgid-Bugs-To:[^"]*"/', '"Report-Msgid-Bugs-To: ' . DARVEN_EPI_TRANSLATION_BUG_REPORT_URL . '\\n"', $contents );
	$contents = preg_replace( '/"POT-Creation-Date:[^"]*"/', '"POT-Creation-Date: ' . DARVEN_EPI_TRANSLATION_POT_DATE . '\\n"', $contents );
	$contents = preg_replace( '/"PO-Revision-Date:[^"]*"/', '"PO-Revision-Date: ' . DARVEN_EPI_TRANSLATION_REVISION_DATE . '\\n"', $contents );
	$contents = preg_replace( '/"X-Generator:[^"]*"/', '"X-Generator: build-translations.php\\n"', $contents );
	if ( null !== $language ) {
		$contents = preg_replace( '/"Language:[^"]*"/', '"Language: ' . $language . '\\n"', $contents );
	}

	if ( false === file_put_contents( $path, $contents ) ) {
		darven_epi_translation_fail( 'Unable to write ' . $path );
	}
}

/**
 * Removes a temporary directory created by this process.
 *
 * @param string $path Temporary directory.
 * @return void
 */
function darven_epi_translation_remove_directory( string $path ): void {
	if ( ! is_dir( $path ) ) {
		return;
	}

	foreach ( new DirectoryIterator( $path ) as $entry ) {
		if ( $entry->isDot() ) {
			continue;
		}

		$entry->isDir()
			? darven_epi_translation_remove_directory( $entry->getPathname() )
			: unlink( $entry->getPathname() );
	}

	rmdir( $path );
}

/**
 * Creates the two handle-based catalogues WordPress resolves before path hashes.
 *
 * @param string $generated_directory Directory containing WP-CLI JSON output.
 * @param string $languages_directory Final language directory.
 * @return void
 */
function darven_epi_translation_aggregate_json( string $generated_directory, string $languages_directory ): void {
	$bundles         = array(
		'darven-precos-parcelados-settings'        => array(
			'source'   => 'build/settings/index.js',
			'prefixes' => array( 'admin/src/settings/', 'admin/src/shared/' ),
			'messages' => array(),
		),
		'darven-precos-parcelados-product-options' => array(
			'source'   => 'build/product-options/index.js',
			'prefixes' => array( 'admin/src/product-options/', 'admin/src/shared/' ),
			'messages' => array(),
		),
	);
	$generated_files = glob( $generated_directory . DIRECTORY_SEPARATOR . DARVEN_EPI_TRANSLATION_DOMAIN . '-' . DARVEN_EPI_TRANSLATION_LOCALE . '-*.json' );

	if ( false === $generated_files || empty( $generated_files ) ) {
		darven_epi_translation_fail( 'WP-CLI produced no JavaScript catalogues.' );
	}

	sort( $generated_files, SORT_STRING );
	foreach ( $generated_files as $generated_file ) {
		$catalogue = json_decode( (string) file_get_contents( $generated_file ), true );
		if ( ! is_array( $catalogue ) || ! isset( $catalogue['source'], $catalogue['locale_data'] ) ) {
			darven_epi_translation_fail( 'Invalid generated JSON: ' . basename( $generated_file ) );
		}

		$source      = str_replace( '\\', '/', $catalogue['source'] );
		$locale_data = reset( $catalogue['locale_data'] );
		if ( ! is_array( $locale_data ) ) {
			darven_epi_translation_fail( 'Missing locale data in ' . basename( $generated_file ) );
		}

		foreach ( $bundles as $handle => &$bundle ) {
			$belongs_to_bundle = false;
			foreach ( $bundle['prefixes'] as $prefix ) {
				if ( 0 === strpos( $source, $prefix ) ) {
					$belongs_to_bundle = true;
					break;
				}
			}

			if ( ! $belongs_to_bundle ) {
				continue;
			}

			foreach ( $locale_data as $msgid => $translation ) {
				if ( '' === $msgid ) {
					continue;
				}

				if ( ! is_array( $translation ) || ! isset( $translation[0] ) || '' === $translation[0] ) {
					darven_epi_translation_fail( $handle . ' has a blank translation for: ' . $msgid );
				}

				$bundle['messages'][ $msgid ] = $translation;
			}
		}
		unset( $bundle );
	}

	$staged_catalogues = array();
	foreach ( $bundles as $handle => $bundle ) {
		if ( empty( $bundle['messages'] ) ) {
			darven_epi_translation_fail( 'No messages were generated for ' . $handle );
		}

		ksort( $bundle['messages'], SORT_STRING );
		$messages    = array(
			'' => array(
				'domain'       => DARVEN_EPI_TRANSLATION_DOMAIN,
				'lang'         => DARVEN_EPI_TRANSLATION_LOCALE,
				'plural-forms' => 'nplurals=2; plural=(n > 1);',
			),
		) + $bundle['messages'];
		$catalogue   = array(
			'translation-revision-date' => DARVEN_EPI_TRANSLATION_REVISION_DATE,
			'generator'                 => 'build-translations.php',
			'source'                    => $bundle['source'],
			'domain'                    => DARVEN_EPI_TRANSLATION_DOMAIN,
			'locale_data'               => array(
				'messages'                    => $messages,
				DARVEN_EPI_TRANSLATION_DOMAIN => $messages,
			),
		);
		$filename    = DARVEN_EPI_TRANSLATION_DOMAIN . '-' . DARVEN_EPI_TRANSLATION_LOCALE . '-' . $handle . '.json';
		$staged_path = $generated_directory . DIRECTORY_SEPARATOR . $filename;
		$json        = json_encode( $catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json || false === file_put_contents( $staged_path, $json . "\n" ) ) {
			darven_epi_translation_fail( 'Unable to write ' . $filename );
		}

		$staged_catalogues[ $filename ] = $staged_path;
	}

	$old_catalogues = glob( $languages_directory . DIRECTORY_SEPARATOR . DARVEN_EPI_TRANSLATION_DOMAIN . '-' . DARVEN_EPI_TRANSLATION_LOCALE . '-*.json' );
	if ( false !== $old_catalogues ) {
		foreach ( $old_catalogues as $old_catalogue ) {
			if ( ! unlink( $old_catalogue ) ) {
				darven_epi_translation_fail( 'Unable to remove ' . basename( $old_catalogue ) );
			}
		}
	}

	foreach ( $staged_catalogues as $filename => $staged_path ) {
		if ( ! copy( $staged_path, $languages_directory . DIRECTORY_SEPARATOR . $filename ) ) {
			darven_epi_translation_fail( 'Unable to publish ' . $filename );
		}
	}
}

try {
	if ( PHP_VERSION_ID < 80000 ) {
		throw new RuntimeException( 'PHP 8.0 or later is required.' );
	}

	$repository_root     = dirname( __DIR__ );
	$languages_directory = $repository_root . DIRECTORY_SEPARATOR . 'languages';
	$pot_path            = $languages_directory . DIRECTORY_SEPARATOR . DARVEN_EPI_TRANSLATION_DOMAIN . '.pot';
	$po_path             = $languages_directory . DIRECTORY_SEPARATOR . DARVEN_EPI_TRANSLATION_DOMAIN . '-' . DARVEN_EPI_TRANSLATION_LOCALE . '.po';
	$mo_path             = $languages_directory . DIRECTORY_SEPARATOR . DARVEN_EPI_TRANSLATION_DOMAIN . '-' . DARVEN_EPI_TRANSLATION_LOCALE . '.mo';
	$temporary_directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darven-epi-translations-' . bin2hex( random_bytes( 8 ) );

	if ( ! is_file( $po_path ) ) {
		throw new RuntimeException( 'The pt_BR PO catalogue is required.' );
	}

	if ( ! is_dir( $temporary_directory ) && ! mkdir( $temporary_directory, 0700 ) && ! is_dir( $temporary_directory ) ) {
		throw new RuntimeException( 'Unable to create the temporary JSON directory.' );
	}

	try {
		$headers = json_encode( array( 'POT-Creation-Date' => DARVEN_EPI_TRANSLATION_POT_DATE ) );
		darven_epi_translation_run_wp_cli(
			array(
				'i18n',
				'make-pot',
				$repository_root,
				$pot_path,
				'--slug=darven-extra-price-info',
				'--domain=' . DARVEN_EPI_TRANSLATION_DOMAIN,
				'--package-name=Darven Installment Prices',
				'--file-comment=Copyright (C) 2026 Leticia Moreira. Distributed under the GPL-2.0-or-later license.',
				'--headers=' . $headers,
				'--exclude=node_modules,build,vendor,i18n,languages,tests,docs,wordpress-org-assets,dist,.superpowers',
			)
		);
		darven_epi_translation_normalize_headers( $pot_path );

		darven_epi_translation_run_wp_cli( array( 'i18n', 'update-po', $pot_path, $po_path ) );
		darven_epi_translation_normalize_headers( $po_path, DARVEN_EPI_TRANSLATION_LOCALE );
		darven_epi_translation_assert_complete_po( $po_path );

		darven_epi_translation_run_wp_cli( array( 'i18n', 'make-mo', $po_path, $mo_path ) );
		darven_epi_translation_run_wp_cli(
			array(
				'i18n',
				'make-json',
				$po_path,
				$temporary_directory,
				'--domain=' . DARVEN_EPI_TRANSLATION_DOMAIN,
				'--no-purge',
				'--pretty-print',
			)
		);
		darven_epi_translation_aggregate_json( $temporary_directory, $languages_directory );
	} finally {
		darven_epi_translation_remove_directory( $temporary_directory );
	}

	echo 'Built deterministic pt_BR translations for PHP and both admin handles.' . PHP_EOL;
} catch ( Throwable $exception ) {
	darven_epi_translation_fail( $exception->getMessage() );
}
