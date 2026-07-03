<?php

use PHPUnit\Framework\TestCase;

require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi-loader.php';
require_once DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi.php';

final class DarvenEpiRecordingLoader extends Darven_Epi_Loader {
	public array $registered_filters = array();

	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->registered_filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

final class DarvenEpiForHookTest extends Darven_Epi {
	public function __construct( Darven_Epi_Loader $loader ) {
		$this->loader = $loader;
	}
}

final class HookRegistrationTest extends TestCase {
	public function test_price_html_filter_accepts_the_product_argument(): void {
		$loader = new DarvenEpiRecordingLoader();
		$plugin = new DarvenEpiForHookTest( $loader );

		$plugin->define_public_filters();

		self::assertCount( 1, $loader->registered_filters );
		self::assertSame( 'woocommerce_get_price_html', $loader->registered_filters[0]['hook'] );
		self::assertSame( 2, $loader->registered_filters[0]['accepted_args'] );
	}
}
