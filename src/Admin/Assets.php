<?php

namespace Darven\ExtraPriceInfo\Admin;

final class Assets {
	public function enqueue(): void {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null;

		if ( 'darven-epi-admin' === $page ) {
			$this->enqueueSettingsAssets();

			return;
		}

		if ( $this->isProductEditor() ) {
			$this->enqueueProductOptionsAssets();
		}
	}

	private function enqueueSettingsAssets(): void {
		$this->enqueueBundle(
			'settings', 'darven-precos-parcelados-settings', 'DarvenPrecosParceladosSettings', array(
				'restUrl'   => rest_url( 'darven-precos-parcelados/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'screen'    => 'settings',
				'productId' => 0,
			)
		);
	}

	private function enqueueProductOptionsAssets(): void {
		$this->enqueueBundle(
			'product-options', 'darven-precos-parcelados-product-options', 'DarvenPrecosParceladosProductOptions', array(
				'restUrl'   => rest_url( 'darven-precos-parcelados/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'screen'    => 'product',
				'productId' => isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0,
			)
		);
		wp_enqueue_media();
	}

	private function enqueueBundle( string $bundle, string $handle, string $object_name, array $config ): void {
		$asset = $this->getAssetMetadata( $bundle );
		$url   = plugin_dir_url( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' ) . 'build/' . $bundle . '/';
		$deps  = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
		$deps  = array_values( array_unique( array_merge( $deps, array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ) ) ) );
		$version = isset( $asset['version'] ) && is_string( $asset['version'] )
			? $asset['version']
			: ( defined( 'DARVEN_EPI_VERSION' ) ? DARVEN_EPI_VERSION : '1.0.0' );

		wp_enqueue_style( $handle, $url . 'style-index.css', array( 'wp-components' ), $version, 'all' );
		wp_enqueue_script( $handle, $url . 'index.js', $deps, $version, true );
		wp_localize_script( $handle, $object_name, $config );
	}

	private function getAssetMetadata( string $bundle ): array {
		$asset_file = DARVEN_EPI_DIR_PATH . 'build/' . $bundle . '/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return array();
		}

		$asset = require $asset_file;

		return is_array( $asset ) ? $asset : array();
	}

	private function isProductEditor(): bool {
		$screen = get_current_screen();

		return is_object( $screen )
			&& isset( $screen->post_type, $screen->base )
			&& 'product' === $screen->post_type
			&& 'post' === $screen->base;
	}
}
