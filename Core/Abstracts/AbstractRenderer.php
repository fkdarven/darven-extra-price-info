<?php
/**
 * Darven Extra Price Info.
 *
 * @package Darven\Epi
 */

namespace Darven\Epi\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractRenderer {
	protected static function find_template( string $slug, array $args = array() ): string {
		$folder       = self::get_template_folder();
		$template_dir = DARVEN_EXTRA_PRICE_INFO_PATH . "Core/Templates/{$folder}/";
		$template     = "{$template_dir}{$slug}.php";

		if ( file_exists( $template ) ) {
			ob_start();

			$include_template = function ($template, $args) {
				foreach ( $args as $key => $value ) {
					$$key = $value;
				}
				include $template;
			};

			$include_template( $template, $args );

			return ob_get_clean();
		}

		return '';
	}

	private static function get_template_folder(): string {
		return match ( true ) {
			is_shop()             => 'shop',
			is_product()          => 'single-product',
			is_product_category() => 'category',
			is_product_tag()      => 'tag',
			is_checkout()         => 'checkout',
			is_cart()             => 'cart',
			default               => 'general',
		};
	}
}
