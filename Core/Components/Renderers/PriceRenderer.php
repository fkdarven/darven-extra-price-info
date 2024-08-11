<?php
/**
 * Darven Extra Price Info.
 *
 * @package Darven\Epi
 */

namespace Darven\Epi\Components\Renderers;

use Darven\Epi\Abstracts\AbstractRenderer;
use Darven\Epi\Entities\IncashPriceEntity;
use Darven\Epi\Entities\InstallmentsPriceEntity;

class PriceRenderer extends AbstractRenderer {

	private static IncashPriceEntity       $incash_price;
	private static InstallmentsPriceEntity $installments_price;

	public static function build(): void {
		add_filter( 'raw_woocommerce_price', array( __CLASS__, 'get_price' ), 999, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'get_template' ), PHP_INT_MAX, 2 );
	}

	public static function get_price( mixed $price ): false|string {
		self::$incash_price       = new IncashPriceEntity( $price );
		self::$installments_price = new InstallmentsPriceEntity( $price );

		return $price;

	}

	public static function get_template( mixed $price ) {
		$installments_template = '';
		$incash_template       = '';

		if ( self::$incash_price->should_show() ) {
			$incash_template = self::find_template(
				'incash-template',
				array(
					'skeleton_price' => self::$incash_price,
				)
			);
		}

		if ( self::$installments_price->should_show() ) {
			$installments_template = self::find_template(
				'installments-template',
				array(
					'skeleton_price' => self::$installments_price,
				)
			);
		}

		return "$price<div class=\"darven-epi-price\">{$incash_template}{$installments_template}</div>";
	}

}
