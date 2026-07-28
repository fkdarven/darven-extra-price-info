<?php

namespace Darven\ExtraPriceInfo\Frontend;

final class Assets {
	public function enqueue(): void {
		$plugin_url = plugin_dir_url( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		$version    = defined( 'DARVEN_EPI_VERSION' ) ? DARVEN_EPI_VERSION : '1.0.0';

		wp_enqueue_style( 'darven-epi', $plugin_url . 'public/css/styles.css', array(), $version, 'all' );
		wp_enqueue_script( 'darven-epi', $plugin_url . 'public/js/frontend.js', array( 'jquery' ), $version, false );
	}
}
