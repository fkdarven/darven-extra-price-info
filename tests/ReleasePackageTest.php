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
		$builder = DARVEN_EPI_DIR_PATH . 'scripts' . DIRECTORY_SEPARATOR . 'build-release.php';
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $builder ) . ' --output=' . escapeshellarg( $this->archive_path ) . ' 2>&1';
		$output  = array();
		$status  = 0;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- The integration test invokes the CLI builder.
		exec( $command, $output, $status );

		self::assertSame( 0, $status, implode( "\n", $output ) );
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
}
