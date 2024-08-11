<?php

namespace Darven\Epi\Components;

use Darven\Epi\Components\Renderers\PriceRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoreComponentsInitializer {
	public static function build() {
		PriceRenderer::build();
	}
}
