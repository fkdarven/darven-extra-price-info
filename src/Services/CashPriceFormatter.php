<?php

namespace Darven\ExtraPriceInfo\Services;

use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class CashPriceFormatter {
	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var ProductPriceResolver
	 */
	private $price_resolver;

	/**
	 * @var PriceMarkupBuilder
	 */
	private $markup_builder;

	public function __construct(
		SettingsRepository $settings_repository,
		ProductPriceResolver $price_resolver,
		PriceMarkupBuilder $markup_builder
	) {
		$this->settings_repository = $settings_repository;
		$this->price_resolver      = $price_resolver;
		$this->markup_builder      = $markup_builder;
	}

	public function format( \WC_Product $product ): string {
		$general_settings = $this->settings_repository->getSection( 'general' );

		if ( 'darven_epi_incash_is_enabled' !== ( $general_settings['darven_epi_incash_is_enabled'] ?? null ) ) {
			return '';
		}

		$active_price  = $this->price_resolver->getActivePrice( $product );
		$minimum_price = (float) ( $general_settings['darven_epi_minimum_incash_value'] ?? 0 );

		if ( $active_price <= $minimum_price ) {
			return '';
		}

		$incash_price = $this->formatPriceFromSettings( $active_price, $general_settings );
		$prefix       = (string) ( $general_settings['darven_epi_incash_prefix'] ?? '' );
		$suffix       = (string) ( $general_settings['darven_epi_incash_suffix'] ?? '' );

		$incash_prefix = $this->markup_builder->span(
			'incash-prefix darven-epi-incash-prefix',
			$prefix,
			'incash'
		);
		$incash_price_html = $this->markup_builder->span( 'darven-epi-incash-price', $incash_price, 'incash' );
		$incash_suffix     = $this->markup_builder->span(
			'incash-suffix darven-epi-incash-suffix',
			$suffix,
			'incash'
		);

		return $this->markup_builder->div(
			'incash-price-statement darven-epi-incash-price-statement',
			$incash_prefix . $incash_price_html . $incash_suffix,
			'incash'
		);
	}

	public function formatPrice( $price ): string {
		return $this->formatPriceFromSettings( $price, $this->settings_repository->getSection( 'general' ) );
	}

	private function formatPriceFromSettings( $price, array $general_settings ): string {
		$minimum_price = (float) ( $general_settings['darven_epi_minimum_incash_value'] ?? 0 );

		if ( $price <= $minimum_price ) {
			return wp_strip_all_tags( wc_price( $price ) );
		}

		$price             = (float) $price;
		$value_of_discount = $general_settings['darven_epi_value_of_incash_discount'] ?? 0;

		if ( 'fixed' === ( $general_settings['darven_epi_type_of_discount'] ?? '' ) ) {
			if ( $price - $value_of_discount <= 0 ) {
				return (string) $price;
			}

			return (string) ( $price - $value_of_discount );
		}

		$final_price = $price - ( $price * ( (int) $value_of_discount / 100 ) );

		return wp_strip_all_tags( wc_price( round( $final_price, 2 ) ) );
	}
}
