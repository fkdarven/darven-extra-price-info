<?php

namespace Darven\ExtraPriceInfo\Services;

use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class ProductPriceResolver {
	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	public function getActivePrice( \WC_Product $product ): float {
		$compatibility = $this->settings_repository->getSection( 'compatibility' );

		if (
			! empty( $compatibility['darven_epi_is_yith_dynamic_compatibility_enabled'] )
			&& class_exists( 'YWDPD_Frontend' )
		) {
			$dynamic_price = \YWDPD_Frontend::get_instance()->get_dynamic_price( $product->get_price(), $product, 1 );

			if ( is_numeric( $dynamic_price ) ) {
				return (float) $dynamic_price;
			}
		}

		if ( $product->is_type( 'variable' ) ) {
			return (float) $product->get_variation_price( 'min', true );
		}

		return (float) $product->get_price();
	}
}
