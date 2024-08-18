<?php

namespace Darven\Epi\Admin\Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImposeCompatibility {
	public static function build() {
		add_action( 'init', array( __CLASS__, 'ensure_compatibility' ), 10, 3 );
	}

	/**
	 * This function ensures compatibility between versions 3 and 4.
	 * @return void
	 */
	public static function ensure_compatibility() {
		//	$cached_status = (bool) get_transient('darven_epi_legacy_has_updated_option');
		$cached_status = false;
		if ( true === $cached_status ) {
			return;
		}

	}

	private static function ensure_general() {
		$general_options = get_option( 'darven_epi_option_general' );
		$general_map     =
			array(
				'darven_epi_value_of_incash_discount'                => 'darven_epi_incash_value_of_discount',
				'darven_epi_minimum_incash_value'                    => 'darven_epi_incash_minimum_value',
				'darven_epi_minimum_installments_value'              => 'darven_epi_installments_minimum_value',
				'darven_epi_max_installments'                        => 'darven_epi_installments_max_installments',
				'darven_epi_installments_interest_fee_first_install' => 'darven_epi_installments_installments_interest_fee_first_install',
			);
		if ( false === $general_options ) {
			return false;
		}
		self::ensure( $general_map, $general_options, 'darven_epi_option_general' );

	}

	private static function ensure_positions() {
		$general_options = get_option( 'darven_epi_option_general' );
		$general_map     =
			array(
				'darven_epi_value_of_incash_discount'                => 'darven_epi_incash_value_of_discount',
				'darven_epi_minimum_incash_value'                    => 'darven_epi_incash_minimum_value',
				'darven_epi_minimum_installments_value'              => 'darven_epi_installments_minimum_value',
				'darven_epi_max_installments'                        => 'darven_epi_installments_max_installments',
				'darven_epi_installments_interest_fee_first_install' => 'darven_epi_installments_installments_interest_fee_first_install',
			);
		if ( false === $general_options ) {
			return false;
		}
		self::ensure( $general_map, $general_options, 'darven_epi_option_general' );
	}

	private static function ensure( $mapped_values, $options_data, $option_name ) {
		foreach ( $mapped_values as $old => $new ) {
			if ( isset( $options_data[ $old ] ) ) {
				$options_data[ $new ] = $options_data[ $old ];
			}
		}
		if ( update_option( $option_name, $options_data ) ) {
			set_transient( "darven_epi_legacy_has_updated_option_{$option_name}", true, 1 * YEAR_IN_SECONDS );
			return true;
		}
		return false;
	}
}
