<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Admin\SettingsFields\CompatibilityFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\DisplayFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\GeneralFields;
use Darven\ExtraPriceInfo\Admin\SettingsFields\PositionsFields;

final class SettingsPage {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
		add_action( 'admin_init', array( $this, 'initializeSettings' ) );
	}

	public function addMenuPage(): void {
		add_submenu_page(
			'woocommerce',
			__( 'In Cash and Installments Price', 'darven-epi' ),
			'Preço á Vista e com Parcelamento',
			'manage_options',
			'darven-epi-admin',
			array( $this, 'renderPage' )
		);
	}

	public function renderPage(): void {
		$tab = filter_input( INPUT_GET, 'tab' );

		include DARVEN_EPI_DIR_PATH . 'templates/admin/general-settings.php';
	}

	public function initializeSettings(): void {
		$tab = filter_input( INPUT_GET, 'tab' );

		if ( 'colorsandstyles' === $tab ) {
			$this->addInCashSection( __( 'In Cash Settings', 'darven-epi' ) );
			$this->addInstallmentsSection( __( 'Installments Settings', 'darven-epi' ) );
			( new DisplayFields() )->register();

			return;
		}

		if ( 'positions' === $tab ) {
			$this->addInCashSection( __( 'Statements Positions Settings', 'darven-epi' ) );
			( new PositionsFields() )->register();

			return;
		}

		if ( 'compatibility' === $tab ) {
			$this->addInCashSection( __( 'Compatibility Settings', 'darven-epi' ) );
			( new CompatibilityFields() )->register();

			return;
		}

		$this->addInCashSection( __( 'In Cash Settings', 'darven-epi' ) );
		$this->addInstallmentsSection( __( 'Installments Settings', 'darven-epi' ) );
		add_settings_section(
			'darven_epi_advanced_installments_settings_section',
			__( 'Advanced Installments Settings Section', 'darven-epi' ),
			array( $this, 'renderAdvancedInstallmentsSection' ),
			'darven-epi-admin'
		);
		( new GeneralFields() )->register();
	}

	public function renderSection(): void {
	}

	public function renderAdvancedInstallmentsSection(): void {
		printf(
			'<div class="wrap"> %s</div>',
			esc_attr(
				__(
					'Use this section to set up customized interest fees. If your gateway has incremental fees (each month the same value is summed up), please use the incremental interest fee settings in the previous section for a better experience.',
					'darven-epi'
				)
			)
		);
	}

	private function addInCashSection( string $title ): void {
		add_settings_section(
			'darven_epi_incash_settings_section',
			$title,
			array( $this, 'renderSection' ),
			'darven-epi-admin'
		);
	}

	private function addInstallmentsSection( string $title ): void {
		add_settings_section(
			'darven_epi_installments_settings_section',
			$title,
			array( $this, 'renderSection' ),
			'darven-epi-admin'
		);
	}
}
