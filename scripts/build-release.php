<?php
/**
 * Builds the distributable WordPress plugin ZIP.
 *
 * Usage: php scripts/build-release.php [--output=C:\path\to\archive.zip]
 */

// This standalone CLI tool intentionally uses PHP filesystem and process APIs outside WordPress.
// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.Missing, WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions

const DARVEN_EPI_RELEASE_SLUG = 'darven-extra-price-info';

/**
 * @param string $message Error message.
 * @return void
 */
function darven_epi_release_fail( $message ): void {
	fwrite( STDERR, 'Release build failed: ' . $message . PHP_EOL );
	exit( 1 );
}

/**
 * @param string $path Filesystem path.
 * @return string Absolute filesystem path.
 */
function darven_epi_release_absolute_path( $path ): string {
	if ( preg_match( '#^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)#', $path ) ) {
		return $path;
	}

	$working_directory = getcwd();

	if ( false === $working_directory ) {
		throw new RuntimeException( 'Unable to determine the current working directory.' );
	}

	return $working_directory . DIRECTORY_SEPARATOR . $path;
}

/**
 * @param string $path Filesystem path.
 * @return string Lexically normalized filesystem path.
 */
function darven_epi_release_normalize_path( $path ): string {
	$path   = str_replace( '\\', '/', $path );
	$prefix = '';

	if ( preg_match( '#^([A-Za-z]:)(/|$)#', $path, $matches ) ) {
		$prefix = strtoupper( $matches[1] ) . '/';
		$path   = substr( $path, strlen( $matches[0] ) );
	} elseif ( 0 === strpos( $path, '//' ) ) {
		$prefix = '//';
		$path   = substr( $path, 2 );
	} elseif ( '/' === substr( $path, 0, 1 ) ) {
		$prefix = '/';
		$path   = substr( $path, 1 );
	}

	$parts = array();

	foreach ( explode( '/', $path ) as $part ) {
		if ( '' === $part || '.' === $part ) {
			continue;
		}

		if ( '..' === $part ) {
			array_pop( $parts );
			continue;
		}

		$parts[] = $part;
	}

	return rtrim( $prefix . implode( '/', $parts ), '/' );
}

/**
 * @param string $path Candidate path.
 * @param string $root Required containing root.
 * @return bool Whether the candidate is equal to or inside the root.
 */
function darven_epi_release_is_within_path( $path, $root ): bool {
	$normalized_path = darven_epi_release_normalize_path( $path );
	$normalized_root = darven_epi_release_normalize_path( $root );

	if ( '\\' === DIRECTORY_SEPARATOR ) {
		$normalized_path = strtolower( $normalized_path );
		$normalized_root = strtolower( $normalized_root );
	}

	return $normalized_path === $normalized_root || 0 === strpos( $normalized_path, $normalized_root . '/' );
}

/**
 * @param string $destination_path Destination filesystem path.
 * @param string $staging_root Staging directory real path.
 * @return void
 */
function darven_epi_release_assert_staging_destination( $destination_path, $staging_root ): void {
	if ( ! darven_epi_release_is_within_path( $destination_path, $staging_root ) ) {
		throw new RuntimeException( 'Staging destination escapes its root: ' . $destination_path );
	}

	$existing_path = dirname( $destination_path );

	while ( ! file_exists( $existing_path ) && ! is_link( $existing_path ) ) {
		$parent_path = dirname( $existing_path );

		if ( $parent_path === $existing_path ) {
			throw new RuntimeException( 'Unable to resolve staging destination: ' . $destination_path );
		}

		$existing_path = $parent_path;
	}

	$existing_real_path = realpath( $existing_path );

	if ( false === $existing_real_path || ! darven_epi_release_is_within_path( $existing_real_path, $staging_root ) ) {
		throw new RuntimeException( 'Staging destination resolves outside its root: ' . $destination_path );
	}
}

/**
 * @param string $archive_path ZIP output path.
 * @param string $repository_root Repository root.
 * @return void
 */
function darven_epi_release_validate_archive_destination( $archive_path, $repository_root ): void {
	if ( 'zip' !== strtolower( pathinfo( $archive_path, PATHINFO_EXTENSION ) ) ) {
		throw new RuntimeException( 'Release output must use the .zip extension.' );
	}

	if ( is_dir( $archive_path ) || is_link( $archive_path ) ) {
		throw new RuntimeException( 'Release output must be a regular ZIP file path.' );
	}

	$distribution_root = $repository_root . DIRECTORY_SEPARATOR . 'dist';
	$is_distribution_output = darven_epi_release_is_within_path( $archive_path, $distribution_root );

	if ( darven_epi_release_is_within_path( $archive_path, $repository_root ) && ! $is_distribution_output ) {
		throw new RuntimeException( 'Release output inside the repository root is allowed only under dist/.' );
	}

	$existing_parent = dirname( $archive_path );

	while ( ! file_exists( $existing_parent ) && ! is_link( $existing_parent ) ) {
		$parent_path = dirname( $existing_parent );

		if ( $parent_path === $existing_parent ) {
			throw new RuntimeException( 'Unable to resolve the release output parent directory.' );
		}

		$existing_parent = $parent_path;
	}

	$parent_real_path = realpath( $existing_parent );

	if ( false === $parent_real_path ) {
		throw new RuntimeException( 'Unable to resolve the release output parent directory.' );
	}

	if ( darven_epi_release_is_within_path( $parent_real_path, $repository_root ) ) {
		$distribution_real_path = realpath( $distribution_root );

		if ( ! $is_distribution_output ) {
			throw new RuntimeException( 'Release output parent resolves inside the repository root.' );
		}

		if ( false === $distribution_real_path ) {
			if ( darven_epi_release_normalize_path( $parent_real_path ) !== darven_epi_release_normalize_path( $repository_root ) ) {
				throw new RuntimeException( 'Release output parent resolves inside the repository root outside dist/.' );
			}

			return;
		}

		if ( darven_epi_release_normalize_path( $distribution_real_path ) !== darven_epi_release_normalize_path( $distribution_root ) || ! darven_epi_release_is_within_path( $parent_real_path, $distribution_real_path ) ) {
			throw new RuntimeException( 'Release output parent resolves inside the repository root outside dist/.' );
		}
	}
}

/**
 * @param string $plugin_file Main plugin file.
 * @return string Release version.
 */
function darven_epi_release_version( $plugin_file ): string {
	$headers = file_get_contents( $plugin_file, false, null, 0, 8192 );

	if ( false === $headers || ! preg_match( '/^[ \t*#@]*Version:\s*([0-9]+(?:\.[0-9]+){2})\s*$/mi', $headers, $matches ) ) {
		throw new RuntimeException( 'The plugin header must contain a semantic Version value such as 3.3.0.' );
	}

	return $matches[1];
}

/**
 * @return string Composer command prefix.
 */
function darven_epi_release_composer_command(): string {
	$composer = getenv( 'COMPOSER_BINARY' );

	if ( false !== $composer && '' !== $composer ) {
		return escapeshellarg( $composer );
	}

	if ( '\\' === DIRECTORY_SEPARATOR && is_file( 'C:\\laragon\\bin\\composer\\composer.phar' ) ) {
		return escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( 'C:\\laragon\\bin\\composer\\composer.phar' );
	}

	return 'composer';
}

/**
 * @param string $relative_path Path relative to repository root.
 * @return bool Whether the path is ignored by Git.
 */
function darven_epi_release_is_git_ignored( $relative_path ): bool {
	$command = 'git check-ignore --quiet --no-index -- ' . escapeshellarg( str_replace( DIRECTORY_SEPARATOR, '/', $relative_path ) ) . ' 2>&1';
	$output  = array();
	$status  = 1;

	exec( $command, $output, $status );

	if ( 0 === $status ) {
		return true;
	}

	if ( 1 === $status && empty( $output ) ) {
		return false;
	}

	throw new RuntimeException( 'Git ignore check failed for ' . $relative_path . ': ' . implode( PHP_EOL, $output ) );
}

/**
 * @param string $relative_path Path relative to repository root.
 * @return bool Whether the path is excluded from a distribution.
 */
function darven_epi_release_is_excluded( $relative_path ): bool {
	$parts = explode( '/', str_replace( DIRECTORY_SEPARATOR, '/', $relative_path ) );
	$files = array(
		'phpcs.xml.dist',
		'phpunit.xml.dist',
	);
	$directories = array(
		'.git',
		'.github',
		'.idea',
		'.vscode',
		'.superpowers',
		'.worktrees',
		'dist',
		'docs',
		'tests',
		'vendor',
	);

	foreach ( $parts as $part ) {
		if ( in_array( $part, $directories, true ) || 0 === strpos( $part, '.phpunit' ) ) {
			return true;
		}
	}

	return in_array( end( $parts ), $files, true ) || darven_epi_release_is_git_ignored( $relative_path );
}

/**
 * @param string $source_path Source filesystem path.
 * @param string $destination_path Destination filesystem path.
 * @param string $repository_root Repository root.
 * @param string $staging_root Staging directory real path.
 * @return void
 */
function darven_epi_release_copy_path( $source_path, $destination_path, $repository_root, $staging_root ): void {
	$relative_path = ltrim( substr( $source_path, strlen( $repository_root ) ), DIRECTORY_SEPARATOR );
	$source_real_path = realpath( $source_path );
	$repository_real_path = realpath( $repository_root );

	if ( is_link( $source_path ) || false === $source_real_path || false === $repository_real_path || ! darven_epi_release_is_within_path( $source_real_path, $repository_real_path ) ) {
		throw new RuntimeException( 'Runtime path is a symbolic link or resolves outside the repository root: ' . $relative_path );
	}

	if ( darven_epi_release_is_excluded( $relative_path ) ) {
		return;
	}

	darven_epi_release_assert_staging_destination( $destination_path, $staging_root );

	if ( is_file( $source_path ) ) {
		$destination_directory = dirname( $destination_path );

		if ( ! is_dir( $destination_directory ) && ! mkdir( $destination_directory, 0777, true ) && ! is_dir( $destination_directory ) ) {
			throw new RuntimeException( 'Unable to create staging directory: ' . $destination_directory );
		}

		darven_epi_release_assert_staging_destination( $destination_path, $staging_root );

		if ( ! copy( $source_path, $destination_path ) ) {
			throw new RuntimeException( 'Unable to stage file: ' . $relative_path );
		}

		return;
	}

	if ( ! is_dir( $source_path ) ) {
		throw new RuntimeException( 'Runtime path does not exist: ' . $relative_path );
	}

	if ( ! is_dir( $destination_path ) && ! mkdir( $destination_path, 0777, true ) && ! is_dir( $destination_path ) ) {
		throw new RuntimeException( 'Unable to create staging directory: ' . $destination_path );
	}

	darven_epi_release_assert_staging_destination( $destination_path, $staging_root );

	$directory = new DirectoryIterator( $source_path );

	foreach ( $directory as $entry ) {
		if ( $entry->isDot() ) {
			continue;
		}

		darven_epi_release_copy_path( $entry->getPathname(), $destination_path . DIRECTORY_SEPARATOR . $entry->getFilename(), $repository_root, $staging_root );
	}
}

/**
 * @param string $path Temporary staging directory.
 * @param string $staging_root Staging directory real path.
 * @return void
 */
function darven_epi_release_remove_directory( $path, $staging_root ): void {
	if ( ! darven_epi_release_is_within_path( $path, $staging_root ) ) {
		throw new RuntimeException( 'Temporary cleanup path escapes staging root: ' . $path );
	}

	if ( is_link( $path ) ) {
		if ( ! unlink( $path ) ) {
			throw new RuntimeException( 'Unable to remove temporary symbolic link: ' . $path );
		}

		return;
	}

	if ( ! is_dir( $path ) ) {
		return;
	}

	$real_path = realpath( $path );

	if ( false === $real_path || ! darven_epi_release_is_within_path( $real_path, $staging_root ) ) {
		throw new RuntimeException( 'Temporary cleanup path resolves outside staging root: ' . $path );
	}

	$directory = new DirectoryIterator( $path );

	foreach ( $directory as $entry ) {
		if ( $entry->isDot() ) {
			continue;
		}

		$entry_path = $entry->getPathname();

		if ( $entry->isLink() ) {
			if ( ! unlink( $entry_path ) ) {
				throw new RuntimeException( 'Unable to remove temporary symbolic link: ' . $entry_path );
			}
		} elseif ( $entry->isDir() ) {
			darven_epi_release_remove_directory( $entry_path, $staging_root );
		} elseif ( ! unlink( $entry_path ) ) {
			throw new RuntimeException( 'Unable to remove temporary file: ' . $entry_path );
		}
	}

	if ( ! rmdir( $path ) ) {
		throw new RuntimeException( 'Unable to remove temporary directory: ' . $path );
	}
}

/**
 * @param string $staging_root Staged plugin directory.
 * @param string $archive_path ZIP output path.
 * @return void
 */
function darven_epi_release_create_zip( $staging_root, $archive_path ): void {
	$archive = new ZipArchive();
	$result  = $archive->open( $archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

	if ( true !== $result ) {
		throw new RuntimeException( 'Unable to create ZIP archive: ' . $archive_path );
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $staging_root, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}

		$relative_path = substr( $file->getPathname(), strlen( $staging_root ) + 1 );
		$archive_name  = DARVEN_EPI_RELEASE_SLUG . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $relative_path );

		if ( ! $archive->addFile( $file->getPathname(), $archive_name ) ) {
			throw new RuntimeException( 'Unable to add archive entry: ' . $archive_name );
		}
	}

	if ( ! $archive->close() ) {
		throw new RuntimeException( 'Unable to finalize ZIP archive: ' . $archive_path );
	}
}

/**
 * @param string $archive_path ZIP output path.
 * @return void
 */
function darven_epi_release_validate_zip( $archive_path ): void {
	$archive = new ZipArchive();
	$result  = $archive->open( $archive_path );

	if ( true !== $result ) {
		throw new RuntimeException( 'Unable to reopen ZIP archive: ' . $archive_path );
	}

	$required = array(
		DARVEN_EPI_RELEASE_SLUG . '/darven-extra-price-info.php',
		DARVEN_EPI_RELEASE_SLUG . '/vendor/autoload.php',
	);
	$forbidden = array(
		'.git/',
		'.github/',
		'.idea/',
		'.vscode/',
		'.superpowers/',
		'.worktrees/',
		'dist/',
		'docs/',
		'tests/',
		'vendor/bin/',
		'composer.json',
		'composer.lock',
		'.phpunit',
	);

	foreach ( $required as $name ) {
		if ( false === $archive->locateName( $name ) ) {
			$archive->close();
			throw new RuntimeException( 'Archive is missing required entry: ' . $name );
		}
	}

	for ( $index = 0; $index < $archive->numFiles; $index++ ) {
		$name = $archive->getNameIndex( $index );

		if ( 0 !== strpos( $name, DARVEN_EPI_RELEASE_SLUG . '/' ) ) {
			$archive->close();
			throw new RuntimeException( 'Archive entry has an invalid root: ' . $name );
		}

		foreach ( $forbidden as $segment ) {
			if ( false !== strpos( $name, $segment ) ) {
				$archive->close();
				throw new RuntimeException( 'Archive contains forbidden entry: ' . $name );
			}
		}
	}

	$archive->close();
}

try {
	if ( PHP_VERSION_ID < 70400 ) {
		throw new RuntimeException( 'PHP 7.4 or later is required to build a release.' );
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		throw new RuntimeException( 'The PHP ZipArchive extension is required to build a release.' );
	}

	$repository_root = dirname( __DIR__ );
	$plugin_file     = $repository_root . DIRECTORY_SEPARATOR . 'darven-extra-price-info.php';
	$version         = darven_epi_release_version( $plugin_file );
	$options         = getopt( '', array( 'output:' ) );
	$output_path     = isset( $options['output'] ) ? $options['output'] : 'dist' . DIRECTORY_SEPARATOR . DARVEN_EPI_RELEASE_SLUG . '-' . $version . '.zip';
	$archive_path    = darven_epi_release_absolute_path( $output_path );
	$staging_path    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . DARVEN_EPI_RELEASE_SLUG . '-release-' . uniqid( '', true );
	$staging_root    = $staging_path . DIRECTORY_SEPARATOR . DARVEN_EPI_RELEASE_SLUG;
	$repository_real_path = realpath( $repository_root );

	if ( false === $repository_real_path ) {
		throw new RuntimeException( 'Unable to resolve the repository root.' );
	}

	darven_epi_release_validate_archive_destination( $archive_path, $repository_real_path );

	if ( ! chdir( $repository_root ) ) {
		throw new RuntimeException( 'Unable to access the repository root.' );
	}

	if ( ! is_dir( dirname( $archive_path ) ) && ! mkdir( dirname( $archive_path ), 0777, true ) && ! is_dir( dirname( $archive_path ) ) ) {
		throw new RuntimeException( 'Unable to create output directory: ' . dirname( $archive_path ) );
	}

	if ( ! mkdir( $staging_path, 0700 ) ) {
		throw new RuntimeException( 'Unable to create an exclusive temporary staging directory.' );
	}

	if ( ! mkdir( $staging_root, 0700 ) ) {
		throw new RuntimeException( 'Unable to create temporary staging directory.' );
	}

	$staging_real_path = realpath( $staging_path );

	if ( false === $staging_real_path ) {
		throw new RuntimeException( 'Unable to resolve the temporary staging directory.' );
	}

	try {
		$runtime_paths = array(
			'admin',
			'i18n',
			'includes',
			'languages',
			'public',
			'src',
			'templates',
			'darven-epi-manual.pdf',
			'readme.txt',
			'composer.json',
			'composer.lock',
		);

		foreach ( $runtime_paths as $runtime_path ) {
			darven_epi_release_copy_path(
				$repository_root . DIRECTORY_SEPARATOR . $runtime_path,
				$staging_root . DIRECTORY_SEPARATOR . $runtime_path,
				$repository_root,
				$staging_real_path
			);
		}

		$root_files = new DirectoryIterator( $repository_root );

		foreach ( $root_files as $root_file ) {
			if ( $root_file->isFile() && 'php' === strtolower( $root_file->getExtension() ) ) {
				darven_epi_release_copy_path( $root_file->getPathname(), $staging_root . DIRECTORY_SEPARATOR . $root_file->getFilename(), $repository_root, $staging_real_path );
			}
		}

		$working_directory = getcwd();

		if ( false === $working_directory || ! chdir( $staging_root ) ) {
			throw new RuntimeException( 'Unable to execute Composer in the staging directory.' );
		}

		try {
			$command = darven_epi_release_composer_command() . ' install --no-dev --prefer-dist --optimize-autoloader 2>&1';
			$output   = array();
			$composer_status = 0;

			exec( $command, $output, $composer_status );
		} finally {
			chdir( $working_directory );
		}

		if ( 0 !== $composer_status ) {
			throw new RuntimeException( 'Composer install failed: ' . implode( PHP_EOL, $output ) );
		}

		if ( ! is_file( $staging_root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' ) ) {
			throw new RuntimeException( 'Composer did not generate vendor/autoload.php.' );
		}

		foreach ( array( 'composer.json', 'composer.lock' ) as $composer_file ) {
			if ( ! unlink( $staging_root . DIRECTORY_SEPARATOR . $composer_file ) ) {
				throw new RuntimeException( 'Unable to remove staged ' . $composer_file . '.' );
			}
		}

		darven_epi_release_create_zip( $staging_root, $archive_path );
		darven_epi_release_validate_zip( $archive_path );
	} finally {
		darven_epi_release_remove_directory( $staging_path, $staging_real_path );
	}

	echo $archive_path . PHP_EOL;
} catch ( Throwable $exception ) {
	darven_epi_release_fail( $exception->getMessage() );
}
