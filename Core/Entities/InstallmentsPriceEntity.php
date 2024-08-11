<?php
/**
 * Darven Extra Price Info.
 *
 * @package Darven\Epi
 */

namespace Darven\Epi\Entities;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Darven\Epi\Abstracts\AbstractEntity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class InstallmentsPriceEntity extends AbstractEntity {

	public string       $maximum_installments;
	public string       $prefix;
	public string       $suffix;
	public string       $minimum_price;
	public string       $mode_of_view;
	public string       $interest_fee;
	public string       $interest_fee_from;
	public float        $price;
	private string      $installments_popup_text;
	private string      $interest_fee_first_install;
	public string       $is_table_enabled;
	private string      $customised_values;
	private string      $type_of_interest;
	public string       $is_enabled;
	public string|float $price_with_interest;

	public $prefix_size;
	public $prefix_color;
	public $suffix_size;
	public $suffix_color;
	public function __construct( $price ) {
		$this->options_prefix             = 'darven_epi_installments_';
		$this->darven_options             = get_option( DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_option_general' );
		$this->maximum_installments       = $this->populate_field( 'max_installments' );
		$this->minimum_price              = $this->populate_field( 'minimum_value' );
		$this->mode_of_view               = $this->populate_field( 'mode_of_view' );
		$this->interest_fee               = $this->populate_field( 'interest_fee' );
		$this->interest_fee_from          = $this->populate_field( 'interest_fee_from' );
		$this->installments_popup_text    = $this->populate_field( 'installments_popup_text' );
		$this->interest_fee_first_install = $this->populate_field( 'installments_interest_fee_first_install' );
		$this->is_table_enabled           = $this->populate_field( 'installments_interest_fee_is_table_enabled' );
		$this->customised_values          = $this->populate_field( 'installments_interest_fee_table' );
		$this->type_of_interest           = $this->populate_field( 'type_of_interest' );
		$this->is_enabled                 = $this->populate_field( 'is_enabled' );
		$this->prefix                     = trim( $this->populate_field( 'prefix' ) );
		$this->suffix                     = trim( $this->populate_field( 'suffix' ) );

		$this->prevent_division_by_zero();
		$this->price = (float) $price;
		if ( $this->is_enabled ) {
			$this->price_with_interest = $this->get_price_with_interest();

		}
		$this->darven_options = get_option( DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_option_colors' );
		$this->prefix_color   = $this->populate_field( 'prefix_color' );
		$this->suffix_color   = $this->populate_field( 'suffix_color' );
		$this->darven_options = get_option( DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_option_sizes' );
		$this->prefix_size    = $this->populate_field( 'prefix_size' ) ?? '12px';
		$this->suffix_size    = $this->populate_field( 'suffix_size' ) ?? '12px';
	}

	private function prevent_division_by_zero(): void {
		if ( $this->maximum_installments <= 0 ) {
			$this->maximum_installments = 1;
		}

		if ( $this->minimum_price <= 0 ) {
			$this->minimum_price = 1;
		}

	}

	public function get_maximum_installments(): int {
		$maximum_installments = (int) floor( $this->price / $this->minimum_price );
		if ( $this->maximum_installments < $maximum_installments ) {
			return (int) $this->maximum_installments;
		}

		return $maximum_installments;
	}

	public function get_simple_interest_price(): float {
		$install_count       = BigDecimal::of( $this->get_maximum_installments() );
		$interest_fee        = BigDecimal::of( $this->interest_fee )->dividedBy( 100 );
		$price_with_interest = $interest_fee
			->multipliedBy( BigDecimal::of( $this->price ) )
			->multipliedBy( $install_count )
			->toScale( 2, RoundingMode::HALF_UP );

		$total_price = $price_with_interest->plus( BigDecimal::of( $this->price ) );

		return $total_price->dividedBy( $install_count, 2, RoundingMode::HALF_UP )->toFloat();
	}

	private function get_installments_with_interest(): int {
		return $this->get_maximum_installments() - (int) $this->interest_fee_from + 1;
	}

	private function get_price_with_interest(): float {
		return match ( $this->type_of_interest ) {
			'simple' => $this->get_simple_interest_price(),
			default  => $this->get_interest( $this->get_maximum_installments() ),
		};

	}

	public function get_interest( $installments ): float {
		$interest_fee          = BigDecimal::of( $this->interest_fee )->dividedBy( 100, 2, RoundingMode::HALF_UP );
		$adjusted_interest_fee = BigDecimal::one()->plus( $interest_fee );
		$total_interest_rate   = $adjusted_interest_fee->power( $installments, 6 );
		$upper_part            = $interest_fee->multipliedBy( BigDecimal::of( $this->price ) )->multipliedBy( $total_interest_rate );
		$lower_part            = $interest_fee->multipliedBy( $total_interest_rate )
			->minus( $interest_fee )
			->plus( $total_interest_rate )
			->minus( BigDecimal::one() );

		if ( $lower_part->isZero() ) {
			return $upper_part->toFloat();
		}

		$total = $upper_part->dividedBy( $lower_part, 6, RoundingMode::HALF_UP )->toFloat();

		return apply_filters( 'darven_extra_price_info_get_interest_value', $total );

	}

	public function should_show(): bool {
		$local_product = wc_get_product();

		if ( is_null( $local_product ) || is_bool( $local_product ) ) {
			return false;
		}

		$disable_installments = get_post_meta( $local_product->get_id(), "_{$this->prefix}_enabled", true );

		$should_show = true;
		if ( $disable_installments || ! $this->is_enabled ) {
			$should_show = false;
		}

		return apply_filters( 'darven_extra_price_info_should_show_installments', $should_show );
	}

}
