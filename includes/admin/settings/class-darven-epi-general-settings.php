<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_General_Settings' ) ) {
	class Darven_Epi_General_Settings {


		/**
		 * @var false|mixed|null
		 */
		private $darven_epi_options;

		public function __construct() {

			add_action( 'admin_menu', array( $this, 'darven_epi_add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'darven_epi_page_init' ) );

		}

		public function darven_epi_add_plugin_page(): void {
			add_submenu_page( 'woocommerce',__('In Cash and Installments Price', DARVEN_EPI_LANGUAGE_DOMAIN), 'Preço á Vista e com Parcelamento', 'manage_options', 'darven-epi-admin', array(
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



			if ( $tab === 'colorsandstyles' ) {
				add_settings_section( 'darven_epi_incash_settings_section', __('In Cash Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
					$this,
					'darven_epi_incash_section_info'
				), 'darven-epi-admin' );

				add_settings_section( 'darven_epi_installments_settings_section', __('Installments Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
					$this,
					'darven_epi_installments_section_info'
				), 'darven-epi-admin' );
				new Darven_Epi_Colorsandstyles_Settings_Fields();
			} elseif ($tab === 'positions'){
				add_settings_section( 'darven_epi_incash_settings_section', __('Statements Positions Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
					$this,
					'darven_epi_incash_section_info'
				), 'darven-epi-admin' );
                new Darven_Epi_Positions_Fields();
            } elseif ($tab === 'compatibility'){
				add_settings_section( 'darven_epi_incash_settings_section', __('Compatibility Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
					$this,
					'darven_epi_incash_section_info'
				), 'darven-epi-admin' );
				new Darven_Epi_Compatibility_Settings_Fields();
			}
            else {
	            add_settings_section( 'darven_epi_incash_settings_section', __('In Cash Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
		            $this,
		            'darven_epi_incash_section_info'
	            ), 'darven-epi-admin' );

	            add_settings_section( 'darven_epi_installments_settings_section', __('Installments Settings', DARVEN_EPI_LANGUAGE_DOMAIN), array(
		            $this,
		            'darven_epi_installments_section_info'
	            ), 'darven-epi-admin' );
	            add_settings_section('darven_epi_advanced_installments_settings_section', __('Advanced Installments Settings Section', DARVEN_EPI_LANGUAGE_DOMAIN), array(
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

            printf('<div class="wrap"> %s</div>', esc_attr(__('Use this section to set up customized interest fees. If your gateway has incremental fees (each month the same value is summed up), please use the incremental interest fee settings in the previous section for a better experience.', DARVEN_EPI_LANGUAGE_DOMAIN)))
            ?>
		<?php
		}

	}
}

if ( is_admin() ) {
	$darven_multiplos_precos_informativos = new Darven_Epi_General_Settings();
}
