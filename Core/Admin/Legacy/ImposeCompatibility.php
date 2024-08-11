<?php

namespace Darven\Epi\Admin\Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImposeCompatibility {
	public static function build() {
		add_action( 'init', array( __CLASS__, 'ensure_compatibility' ), 10, 3 );
	}

	public static function ensure_compatibility() {
		$old_general       = get_option( 'darven_epi_option_general' );
		$old_styles        = get_option( 'darven_epi_option_colorsandstyles' );
		$old_compatibility = get_option( 'darven_epi_option_compatibility' );
		$old_positions     = get_option( 'darven_epi_option_positions' );
		$mapping_general   =
			array(
				'darven_epi_value_of_incash_discount'                => 'darven_epi_incash_value_of_discount',
				'darven_epi_incash_minimum_value'                    => 'darven_epi_minimum_incash_value',
				'darven_epi_minimum_installments_value'              => 'darven_epi_installments_minimum_value',
				'darven_epi_max_installments'                        => 'darven_epi_installments_max_installments',
				'darven_epi_installments_interest_fee_first_install' => 'darven_epi_installments_installments_interest_fee_first_install',
			);
		foreach ( $mapping_general as $old => $new ) {
			if ( isset( $old_general[ $old ] ) ) {
				$old_general[ $new ] = $old_general[ $old ];
				unset( $old_general[ $old ] );
			}
		}
		update_option( 'darven_epi_option_general', $old_general );
	}
}
