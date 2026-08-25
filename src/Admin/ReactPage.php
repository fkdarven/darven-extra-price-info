<?php

namespace Darven\ExtraPriceInfo\Admin;

use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class ReactPage {
	private const SAVE_ACTION = 'darven_epi_save_settings';
	private const NONCE_NAME  = 'darven_epi_settings_nonce';

	/**
	 * @var SettingsRepository
	 */
	private $repository;

	public function __construct( ?SettingsRepository $repository = null ) {
		$this->repository = $repository ?? new SettingsRepository( new LegacySettingsAdapter() );
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handleSave' ) );
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
		$this->renderFallbackForm( $this->repository->getSettings() );
	}

	public function handleSave(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You are not allowed to manage these settings.', 'darven-multiplos-precos-informativos' ) ) );
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			wp_die( esc_html( __( 'The settings security check failed.', 'darven-multiplos-precos-informativos' ) ) );
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::SAVE_ACTION ) ) {
			wp_die( esc_html( __( 'The settings security check failed.', 'darven-multiplos-precos-informativos' ) ) );
		}

		$document = array();
		if ( isset( $_POST['darven_epi_settings'] ) && is_array( $_POST['darven_epi_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- SettingsRepository applies its field-aware SettingsSanitizer, including allowed markup.
			$document = wp_unslash( $_POST['darven_epi_settings'] );
		}
		$saved    = $this->repository->saveDocument( $document );
		$location = add_query_arg(
			array(
				'page'             => 'darven-epi-admin',
				'settings-updated' => $saved ? '1' : '0',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $location );
	}

	private function renderFallbackForm( array $settings ): void {
		echo '<noscript><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::SAVE_ACTION ) . '">';
		wp_nonce_field( self::SAVE_ACTION, self::NONCE_NAME );

		foreach ( $this->getFallbackFields() as $section => $fields ) {
			echo '<fieldset><legend>' . esc_html( ucfirst( $section ) ) . '</legend>';
			foreach ( $fields as $field => $definition ) {
				$value = isset( $settings[ $section ][ $field ] ) && is_scalar( $settings[ $section ][ $field ] )
					? (string) $settings[ $section ][ $field ]
					: '';
				$this->renderFallbackField( $section, $field, $definition, $value );
			}
			echo '</fieldset>';
		}

		echo '<button class="button button-primary" type="submit">' . esc_html( __( 'Save settings', 'darven-multiplos-precos-informativos' ) ) . '</button>';
		echo '</form></noscript>';
	}

	private function renderFallbackField( string $section, string $field, array $definition, string $value ): void {
		$name  = 'darven_epi_settings[' . $section . '][' . $field . ']';
		$label = str_replace( '_', ' ', preg_replace( '/^darven_epi_/', '', $field ) );

		echo '<p><label>' . esc_html( ucfirst( $label ) ) . '<br>';
		if ( 'checkbox' === $definition['type'] ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="">';
			echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $field ) . '"' . ( $field === $value ? ' checked' : '' ) . '>';
		} elseif ( 'select' === $definition['type'] ) {
			echo '<select name="' . esc_attr( $name ) . '">';
			foreach ( $definition['options'] as $option ) {
				echo '<option value="' . esc_attr( $option ) . '"' . ( $option === $value ? ' selected' : '' ) . '>' . esc_html( $option ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'textarea' === $definition['type'] ) {
			echo '<textarea name="' . esc_attr( $name ) . '">' . esc_html( $value ) . '</textarea>';
		} else {
			echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</label></p>';
	}

	private function getFallbackFields(): array {
		$font_sizes = array( '1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9', '2.0' );
		$positions  = array( 'first', 'second', 'third', 'fourth', 'fifth', 'sixth' );

		return array(
			'general'       => array(
				'darven_epi_incash_is_enabled'                            => array( 'type' => 'checkbox' ),
				'darven_epi_installments_is_enabled'                      => array( 'type' => 'checkbox' ),
				'darven_epi_installments_interest_fee_is_table_enabled' => array( 'type' => 'checkbox' ),
				'darven_epi_type_of_discount'                              => array( 'type' => 'select', 'options' => array( 'percent', 'fixed' ) ),
				'darven_epi_mode_of_view'                                  => array( 'type' => 'select', 'options' => array( 'default', 'popup', 'nofee' ) ),
				'darven_epi_minimum_installments_value'                    => array( 'type' => 'text' ),
				'darven_epi_installments_interest_fee'                     => array( 'type' => 'text' ),
				'darven_epi_installments_interest_fee_first_install'       => array( 'type' => 'text' ),
				'darven_epi_minimum_incash_value'                           => array( 'type' => 'text' ),
				'darven_epi_value_of_incash_discount'                       => array( 'type' => 'text' ),
				'darven_epi_max_installments'                              => array( 'type' => 'text' ),
				'darven_epi_installments_interest_fee_from'                 => array( 'type' => 'text' ),
				'darven_epi_installments_prefix'                           => array( 'type' => 'text' ),
				'darven_epi_installments_suffix'                           => array( 'type' => 'text' ),
				'darven_epi_incash_suffix'                                 => array( 'type' => 'text' ),
				'darven_epi_incash_prefix'                                 => array( 'type' => 'text' ),
				'darven_epi_popup_text'                                    => array( 'type' => 'textarea' ),
				'darven_epi_installments_interest_fee_table'               => array( 'type' => 'textarea' ),
			),
			'display'       => array(
				'darven_epi_color_of_installments_install'     => array( 'type' => 'text' ),
				'darven_epi_color_of_installments_prefix'      => array( 'type' => 'text' ),
				'darven_epi_color_of_installments_suffix'      => array( 'type' => 'text' ),
				'darven_epi_color_of_installments_price'       => array( 'type' => 'text' ),
				'darven_epi_color_of_incash_prefix'            => array( 'type' => 'text' ),
				'darven_epi_color_of_incash_suffix'            => array( 'type' => 'text' ),
				'darven_epi_color_of_incash_price'             => array( 'type' => 'text' ),
				'darven_epi_font_size_of_incash_price'         => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_incash_suffix'        => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_incash_prefix'        => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_installments_price'   => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_installments_suffix'  => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_installments_prefix'  => array( 'type' => 'select', 'options' => $font_sizes ),
				'darven_epi_font_size_of_installments_install' => array( 'type' => 'select', 'options' => $font_sizes ),
			),
			'positions'     => array(
				'darven_epi_others_product_position'  => array( 'type' => 'select', 'options' => $positions ),
				'darven_epi_single_product_position'  => array( 'type' => 'select', 'options' => $positions ),
				'darven_epi_catalog_product_position' => array( 'type' => 'select', 'options' => $positions ),
			),
			'compatibility' => array(
				'darven_epi_yith_dynamic_pricing_mode' => array( 'type' => 'select', 'options' => array( 'auto', 'disabled' ) ),
			),
		);
	}
}
