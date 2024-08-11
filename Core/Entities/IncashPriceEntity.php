<?php

namespace Darven\Epi\Entities;

use Darven\Epi\Abstracts\AbstractEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IncashPriceEntity extends AbstractEntity {

	public float  $price;
	public float  $value_of_discount;
	public string $type_of_discount;
	public float  $minimum_value;
	public string $prefix;
	public string $suffix;
	public        $is_enabled;

	public function __construct( $price ) {
		$this->options_prefix    = 'darven_epi_incash_';
		$this->darven_options    = get_option( DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_option_general' );
		$this->type_of_discount  = $this->populate_field( 'type_of_discount' );
		$this->prefix            = $this->populate_field( 'prefix' ) ?? '';
		$this->suffix            = $this->populate_field( 'suffix' ) ?? '';
		$this->is_enabled        = $this->populate_field( 'is_enabled' ) ?? 0;
		$this->value_of_discount = (float) $this->populate_field( 'value_of_discount' ) ?? 0;
		$this->minimum_value     = (float) $this->populate_field( 'minimum_value' ) ?? 0;

		$this->price = (float) $price;
	}

	public function get_price_with_discount(): float|int {
		if ( 'fixed' === $this->type_of_discount ) {
			$price = $this->price - $this->value_of_discount;
			if ( ( $price < $this->minimum_value ) || $price === 0 ) {
				$price = $this->minimum_value;
			}

			return apply_filters( 'darven_extra_price_info_incash_price_with_discount', $price );
		}
		$value_of_discount = ( (int) $this->value_of_discount ) / 100;
		$price             = $this->price - $this->price * $value_of_discount;
		$price             = round( $price, 2 );
		if ( $price < $this->minimum_value ) {
			$price = $this->minimum_value;
		}

		return apply_filters( 'darven_extra_price_info_incash_price_with_discount', $price );
	}

	public function should_show(): bool {

		$local_product = wc_get_product();

		if ( is_null( $local_product ) || is_bool( $local_product ) ) {
			return false;
		}

		$disable_incash = get_post_meta( $local_product->get_id(), '_darven_epi_is_incash_enabled', true );

		$should_show = true;
		if ( $disable_incash || ! $this->is_enabled ) {
			$should_show = false;
		}

		return apply_filters( 'darven_extra_price_info_should_show_incas', $should_show );
	}

}
