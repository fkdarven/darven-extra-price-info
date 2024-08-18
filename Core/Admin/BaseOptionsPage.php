<?php

namespace Darven\Epi\Admin;

use Darven\Epi\Admin\Tabs\SizesTab;
use Darven\Epi\Admin\Tabs\ColorsTab;
use Darven\Epi\Admin\Tabs\GeneralTab;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BaseOptionsPage {

	public static function build(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'darven_epi_admin_enqueue_scripts' ) );
		add_filter( 'admin_menu', array( __CLASS__, 'darven_epi_admin_menu' ) );
		add_filter( 'admin_init', array( __CLASS__, 'darven_epi_page_init' ) );

	}

	public static function darven_epi_admin_menu( $meta_boxes ): void {
		add_menu_page(
			__( 'Preço á Vista e com Parcelamento', DARVEN_EXTRA_PRICE_INFO_DOMAIN ),
			__( 'Preço á Vista e com Parcelamento', DARVEN_EXTRA_PRICE_INFO_DOMAIN ),
			'manage_options',
			'darven-epi-admin',
			array(
				__CLASS__,
				'darven_epi_admin_page',
			),
			'dashicons-cart',
		);

	}

	public static function darven_epi_admin_page(): void {
		require_once DARVEN_EXTRA_PRICE_INFO_PATH . 'Core/Templates/admin/admin-page.php';
	}

	public static function darven_epi_page_init(): void {

		$tab = filter_input( INPUT_GET, 'tab' ) ?? 'general';

		self::section_builder(
			self::get_slug( 'incash', $tab ),
			__( 'Configurações do Preço á Vista', DARVEN_EXTRA_PRICE_INFO_DOMAIN ),
			function () {
			}
		);
		self::section_builder(
			self::get_slug( 'installments', $tab ),
			__( 'Configurações do Preço com Parcelamento', DARVEN_EXTRA_PRICE_INFO_DOMAIN ),
			function () {
			}
		);

		match ( $tab ) {
			'general' => new GeneralTab(),
			'colors'  => new ColorsTab(),
			'sizes'   => new SizesTab(),
			default   => 'general',
		};

	}

	public static function darven_epi_admin_enqueue_scripts(): void {
		wp_enqueue_style( 'darven-epi-admin-css', DARVEN_EXTRA_PRICE_INFO_URL . 'assets/css/admin.css' );
		wp_enqueue_script( 'darven-epi-admin-js', DARVEN_EXTRA_PRICE_INFO_URL . 'assets/js/admin.js', array( 'wp-color-picker' ) );
	}

	private static function section_builder( string $identifier, string $title, callable $callback ): void {
		add_settings_section( $identifier, __( $title, DARVEN_EXTRA_PRICE_INFO_DOMAIN ), $callback, 'darven-epi-admin' );
	}

	private static function get_slug( string $title, string $tab ): string {
		return "darven_epi_{$title}_settings_{$tab}_section";
	}

}
