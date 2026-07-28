<?php

use PHPUnit\Framework\TestCase;

final class ReleasePackageTest extends TestCase {
	private $archive_path;

	protected function setUp(): void {
		$this->archive_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darven-extra-price-info-release-' . uniqid( '', true ) . '.zip';
	}

	protected function tearDown(): void {
		if ( is_file( $this->archive_path ) ) {
			unlink( $this->archive_path );
		}
	}

	public function test_builds_a_runtime_only_distribution_archive(): void {
		$result = $this->run_builder( '--output=' . escapeshellarg( $this->archive_path ) );

		self::assertSame( 0, $result['status'], $result['output'] );
		self::assertFileExists( $this->archive_path );

		$archive = new ZipArchive();
		self::assertTrue( $archive->open( $this->archive_path ) );
		self::assertNotFalse( $archive->locateName( 'darven-extra-price-info/darven-extra-price-info.php' ) );
		self::assertNotFalse( $archive->locateName( 'darven-extra-price-info/vendor/autoload.php' ) );
		self::assertFalse( $archive->locateName( 'darven-extra-price-info/tests/bootstrap.php' ) );
		self::assertFalse( $archive->locateName( 'darven-extra-price-info/.superpowers/release-3.3.0-plan.md' ) );

		$forbidden_segments = array(
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

		for ( $index = 0; $index < $archive->numFiles; $index++ ) {
			$name = $archive->getNameIndex( $index );

			self::assertStringStartsWith( 'darven-extra-price-info/', $name );

			foreach ( $forbidden_segments as $segment ) {
				self::assertStringNotContainsString( $segment, $name );
			}
		}

		$archive->close();
	}

	public function test_uses_a_laragon_php_with_openssl_for_the_local_composer_phar_when_php74_has_none(): void {
		if ( PHP_VERSION_ID >= 80000 || '\\' !== DIRECTORY_SEPARATOR ) {
			self::markTestSkipped( 'This regression applies only to the Windows PHP 7.4 validation runtime.' );
		}

		self::assertFalse( extension_loaded( 'openssl' ), 'The PHP 7.4 matrix must run without OpenSSL for this regression.' );

		$original_path     = getenv( 'PATH' );
		$original_composer = getenv( 'COMPOSER_BINARY' );
		$git_path          = $this->find_windows_executable( 'git' );
		$system_root       = getenv( 'SystemRoot' );

		self::assertNotFalse( $system_root );
		self::assertNotFalse( $original_path );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- The child builder must discover the local Composer PHAR without a PATH Composer command.
		putenv( 'COMPOSER_BINARY' );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Git remains available for the builder while Composer is intentionally absent from PATH.
		putenv( 'PATH=' . dirname( $git_path ) . PATH_SEPARATOR . $system_root . DIRECTORY_SEPARATOR . 'System32' );

		try {
			$composer_lookup = array();
			$composer_status = 0;

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Prove that the real builder cannot satisfy this regression through a PATH Composer command.
			exec( 'where composer 2>&1', $composer_lookup, $composer_status );
			self::assertNotSame( 0, $composer_status, implode( "\n", $composer_lookup ) );

			$result = $this->run_builder( '--output=' . escapeshellarg( $this->archive_path ) );
		} finally {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Restore the process environment after the child builder exits.
			putenv( 'PATH=' . $original_path );

			if ( false === $original_composer ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Restore an unset Composer override.
				putenv( 'COMPOSER_BINARY' );
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Restore the explicit Composer override.
				putenv( 'COMPOSER_BINARY=' . $original_composer );
			}
		}

		self::assertSame( 0, $result['status'], $result['output'] );
		self::assertFileExists( $this->archive_path );
	}

	public function test_fails_when_git_ignore_check_cannot_run(): void {
		$original_path = getenv( 'PATH' );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- The child builder must observe a missing Git command.
		putenv( 'PATH=' );

		try {
			$result = $this->run_builder( '--output=' . escapeshellarg( $this->archive_path ) );
		} finally {
			if ( false === $original_path ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Restore the process environment after the child process exits.
				putenv( 'PATH' );
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Restore the process environment after the child process exits.
				putenv( 'PATH=' . $original_path );
			}
		}

		self::assertNotSame( 0, $result['status'] );
		self::assertStringContainsString( 'Git ignore check failed', $result['output'] );
		self::assertFileDoesNotExist( $this->archive_path );
	}

	public function test_rejects_runtime_symlinks_that_point_outside_the_repository(): void {
		$outside_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darven-extra-price-info-outside-' . uniqid( '', true );
		$link_path    = DARVEN_EPI_DIR_PATH . 'public' . DIRECTORY_SEPARATOR . 'release-package-outside-link';
		$is_junction  = false;

		mkdir( $outside_path );
		file_put_contents( $outside_path . DIRECTORY_SEPARATOR . 'outside.php', '<?php echo "outside";' );

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Windows may require elevation to create a symbolic link; the junction fallback is intentional.
		if ( ! function_exists( 'symlink' ) || ! @symlink( $outside_path, $link_path ) ) {
			if ( '\\' === DIRECTORY_SEPARATOR ) {
				$junction_command = 'cmd /c mklink /J ' . escapeshellarg( $link_path ) . ' ' . escapeshellarg( $outside_path ) . ' 2>&1';
				$junction_output  = array();
				$junction_status  = 0;

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Windows junctions cover hosts where symlinks require elevation.
				exec( $junction_command, $junction_output, $junction_status );
				$is_junction = 0 === $junction_status;
			}

			if ( ! $is_junction ) {
				unlink( $outside_path . DIRECTORY_SEPARATOR . 'outside.php' );
				rmdir( $outside_path );
				self::markTestSkipped( 'Creating symlinks or Windows junctions is not supported by this test environment.' );
			}
		}

		try {
			$result = $this->run_builder( '--output=' . escapeshellarg( $this->archive_path ) );
		} finally {
			if ( $is_junction && is_dir( $link_path ) ) {
				rmdir( $link_path );
			} elseif ( is_link( $link_path ) || is_file( $link_path ) ) {
				unlink( $link_path );
			}

			unlink( $outside_path . DIRECTORY_SEPARATOR . 'outside.php' );
			rmdir( $outside_path );
		}

		self::assertNotSame( 0, $result['status'] );
		self::assertStringContainsString( 'symbolic link', $result['output'] );
		self::assertFileDoesNotExist( $this->archive_path );
	}

	public function test_rejects_output_paths_inside_the_repository_outside_dist(): void {
		$source_output = DARVEN_EPI_DIR_PATH . 'release-package-output-safety.zip';

		try {
			$result = $this->run_builder( '--output=' . escapeshellarg( $source_output ) );
		} finally {
			if ( is_file( $source_output ) ) {
				unlink( $source_output );
			}
		}

		self::assertNotSame( 0, $result['status'] );
		self::assertStringContainsString( 'inside the repository root', $result['output'] );
		self::assertFileDoesNotExist( $source_output );
	}

	public function test_rejects_external_output_parents_that_resolve_inside_the_repository(): void {
		$junction_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'darven-extra-price-info-output-link-' . uniqid( '', true );
		$source_output = DARVEN_EPI_DIR_PATH . 'release-package-parent-junction.zip';
		$is_junction   = false;

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Windows may require elevation to create a symbolic link; the junction fallback is intentional.
		if ( ! function_exists( 'symlink' ) || ! @symlink( DARVEN_EPI_DIR_PATH, $junction_path ) ) {
			if ( '\\' === DIRECTORY_SEPARATOR ) {
				$junction_command = 'cmd /c mklink /J ' . escapeshellarg( $junction_path ) . ' ' . escapeshellarg( DARVEN_EPI_DIR_PATH ) . ' 2>&1';
				$junction_output  = array();
				$junction_status  = 0;

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Windows junctions cover hosts where symlinks require elevation.
				exec( $junction_command, $junction_output, $junction_status );
				$is_junction = 0 === $junction_status;
			}

			if ( ! $is_junction ) {
				self::markTestSkipped( 'Creating symlinks or Windows junctions is not supported by this test environment.' );
			}
		}

		try {
			$result      = $this->run_builder( '--output=' . escapeshellarg( $junction_path . DIRECTORY_SEPARATOR . basename( $source_output ) ) );
			$was_written = is_file( $source_output );
		} finally {
			if ( is_file( $source_output ) ) {
				unlink( $source_output );
			}

			if ( $is_junction && is_dir( $junction_path ) ) {
				rmdir( $junction_path );
			} elseif ( is_link( $junction_path ) || is_file( $junction_path ) ) {
				unlink( $junction_path );
			}
		}

		self::assertNotSame( 0, $result['status'] );
		self::assertStringContainsString( 'resolves inside the repository root', $result['output'] );
		self::assertFalse( $was_written );
	}

	private function run_builder( $arguments ): array {
		$builder = DARVEN_EPI_DIR_PATH . 'scripts' . DIRECTORY_SEPARATOR . 'build-release.php';
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $builder ) . ' ' . $arguments . ' 2>&1';
		$output  = array();
		$status  = 0;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- The integration test invokes the CLI builder.
		exec( $command, $output, $status );

		return array(
			'output' => implode( "\n", $output ),
			'status' => $status,
		);
	}

	private function find_windows_executable( $name ): string {
		$output = array();
		$status = 0;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- The test isolates PATH while retaining the Git executable required by the real builder.
		exec( 'where ' . escapeshellarg( $name ) . ' 2>&1', $output, $status );

		self::assertSame( 0, $status, implode( "\n", $output ) );
		self::assertNotEmpty( $output );

		return $output[0];
	}
}
