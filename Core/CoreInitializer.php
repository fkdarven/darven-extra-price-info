<?php

namespace Darven\Epi;

use Darven\Epi\Admin\BaseOptionsPage;
use Darven\Epi\Admin\Legacy\ImposeCompatibility;
use Darven\Epi\Components\CoreComponentsInitializer;

defined( 'ABSPATH' ) || exit();

class CoreInitializer {
	public static function build(): void {
		self::build_base();
		self::build_scripts();
		self::build_legacy();
	}

	private static function build_base(): void {
		BaseOptionsPage::build();
		CoreComponentsInitializer::build();
	}

	private static function build_legacy(): void {
		ImposeCompatibility::build();
	}
	private static function build_scripts(): void {}
}
