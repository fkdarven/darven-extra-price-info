<?php

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ): string {
		$GLOBALS['darven_epi_test_plugin_dir_url_file'] = $file;

		return 'https://example.test/wp-content/plugins/darven-extra-price-info/';
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ): void {
		$GLOBALS['darven_epi_test_enqueued_styles'][] = array(
			'handle' => $handle,
			'src'    => $src,
			'deps'   => $deps,
			'ver'    => $ver,
			'media'  => $media,
		);
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ): void {
		$GLOBALS['darven_epi_test_enqueued_scripts'][] = array(
			'handle'    => $handle,
			'src'       => $src,
			'deps'      => $deps,
			'ver'       => $ver,
			'in_footer' => $in_footer,
		);
	}
}
