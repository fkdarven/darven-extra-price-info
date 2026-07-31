<?php

namespace Darven\ExtraPriceInfo\Admin;

final class ReactPage {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
	}

	public function addMenuPage(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Darven Preços Parcelados', 'darven-multiplos-precos-informativos' ),
			__( 'Darven Preços Parcelados', 'darven-multiplos-precos-informativos' ),
			'manage_options',
			'darven-epi-admin',
			array( $this, 'renderPage' )
		);
	}

	public function renderPage(): void {
		echo '<div id="darven-precos-parcelados-settings-root"></div>';
	}
}
