<?php

namespace Darven\Epi;

use Darven\Epi\Admin\BaseOptionsPage;
use Darven\Epi\Components\CoreComponentsInitializer;

defined( 'ABSPATH' ) || exit();

class CoreInitializer {
	public static function build(): void {
		self::build_base();
		self::build_scripts();
	}

	private static function build_base(): void {
		BaseOptionsPage::build();
		CoreComponentsInitializer::build();
	}

	private static function build_scripts(): void {}
}
