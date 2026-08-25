<?php

namespace Darven\ExtraPriceInfo\Frontend;

final class Assets {
	/**
	 * @var InlineStyles
	 */
	private $inline_styles;

	public function __construct( InlineStyles $inline_styles ) {
		$this->inline_styles = $inline_styles;
	}

	public function enqueue(): void {
		$plugin_url = plugin_dir_url( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		$version    = defined( 'DARVEN_EPI_VERSION' ) ? DARVEN_EPI_VERSION : '1.0.0';

		wp_enqueue_style( 'darven-epi', $plugin_url . 'public/css/styles.css', array(), $version, 'all' );
		wp_add_inline_style( 'darven-epi', $this->inline_styles->render() );
		wp_enqueue_script( 'darven-epi', $plugin_url . 'public/js/frontend.js', array(), $version, false );
	}
}
