<?php

namespace Darven\ExtraPriceInfo\Admin;

final class Assets {
	public function enqueue(): void {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null;

		if ( 'darven-epi-admin' !== $page ) {
			return;
		}

		$plugin_url = plugin_dir_url( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );
		$version    = defined( 'DARVEN_EPI_VERSION' ) ? DARVEN_EPI_VERSION : '1.0.0';

		wp_enqueue_style( 'darven-epi', $plugin_url . 'admin/css/admin_styles.css', array(), $version, 'all' );

		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : null;
		if ( 'colorsandstyles' === $tab ) {
			wp_enqueue_script(
				'darven-epi',
				$plugin_url . 'admin/js/colorsandstyles.js',
				array( 'jquery', 'wp-color-picker' ),
				$version,
				false
			);

			return;
		}

		if ( 'positions' !== $tab ) {
			wp_enqueue_script( 'darven-epi', $plugin_url . 'admin/js/general.js', array( 'jquery' ), $version, false );
		}
	}
}
