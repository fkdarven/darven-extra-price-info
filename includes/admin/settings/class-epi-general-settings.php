<?php

if ( ! class_exists( 'Darven_Epi_General_Settings' ) ) {
	class Darven_Epi_General_Settings {


		public function __construct() {

			add_action( 'admin_menu', array( $this, 'darven_epi_add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'darven_epi_page_init' ) );

		}

		public function darven_epi_add_plugin_page(): void {
			add_submenu_page( 'woocommerce','Preço á Vista e com Parcelamento', 'Preço á Vista e com Parcelamento', 'manage_options', 'darven-epi-admin', array(
				$this,
				'darven_epi_create_admin_page'
			));
		}

		public function darven_epi_create_admin_page(): void {

			$option                   = filter_input( INPUT_GET, 'tab' );
			$this->darven_epi_options = get_option( 'darven_epi_option_' . $option );
			include_once( DARVEN_EPI_DIR_PATH . 'templates/admin/general-settings.php' );
		}

		public function darven_epi_page_init(): void {

			$tab = filter_input( INPUT_GET, 'tab' );

			add_settings_section( 'darven_epi_incash_settings_section', __('Configurações do Preço á Vista'), array(
				$this,
				'darven_epi_incash_section_info'
			), 'darven-epi-admin' );

			add_settings_section( 'darven_epi_installments_settings_section', __('Configurações do Preço com Parcelamento'), array(
				$this,
				'darven_epi_installments_section_info'
			), 'darven-epi-admin' );




			if ( $tab === 'colorsandstyles' ) {
				new Darven_Epi_Colorsandstyles_Settings_Fields();
			} elseif ($tab === 'positions'){
                new Darven_Epi_Positions_Fields();
            }
            else {
	            add_settings_section('darven_epi_advanced_installments_settings_section', __('Advanced Installments Settings Section'), array(
		            $this, 'darven_epi_advanced_installments_section_info'
	            ), 'darven-epi-admin');
				new Darven_Epi_General_Settings_Fields();

			}

		}

		public function darven_epi_incash_section_info(): void {
		}

		public function darven_epi_installments_section_info(): void {
		}

		public function darven_epi_advanced_installments_section_info(): void{
			?>
			<div class="wrap">Utilize essa seção para configurar taxas de juros personalizados. Caso seu gateway possua taxas incrementais (todo mês o mesmo valor é acrescido), por gentileza, utilize as configurações de juros incrementais da seção anterior para uma melhor experiência. </div>
		<?php
		}

	}
}

if ( is_admin() ) {
	$darven_multiplos_precos_informativos = new Darven_Epi_General_Settings();
}
