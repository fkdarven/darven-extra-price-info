<?php

namespace Darven\ExtraPriceInfo\Services;

use Darven\ExtraPriceInfo\Compatibility\YithDynamicPricingMode;
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
		if ( YithDynamicPricingMode::AUTO === $this->settings_repository->getYithDynamicPricingMode() ) {
			$dynamic_price = $this->getYithDynamicPrice( $product );

			if ( null !== $dynamic_price ) {
				return $dynamic_price;
			}
		}

		if ( $product->is_type( 'variable' ) ) {
			return (float) $product->get_variation_price( 'min', true );
		}

		return (float) $product->get_price();
	}

	private function getYithDynamicPrice( \WC_Product $product ) {
		if ( ! class_exists( 'YWDPD_Frontend' ) || ! is_callable( array( 'YWDPD_Frontend', 'get_instance' ) ) ) {
			return null;
		}

		try {
			$frontend = \YWDPD_Frontend::get_instance();
			if ( ! is_object( $frontend ) || ! is_callable( array( $frontend, 'get_dynamic_price' ) ) ) {
				return null;
			}

			$dynamic_price = $frontend->get_dynamic_price( $product->get_price(), $product, 1 );

			return is_numeric( $dynamic_price ) ? (float) $dynamic_price : null;
		} catch ( \Throwable $exception ) {
			return null;
		}
	}
}
