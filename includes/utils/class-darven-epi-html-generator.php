<?php
/**
 * Developed by Leticia Moreira.
 *
 * @package Darven_Epi.
 */

if ( ! class_exists( 'Darven_Epi_Html_Generator' ) ) {

	/**
	 * Class responsible for generating html outputs.
	 */
	class Darven_Epi_Html_Generator {
		/**
		 * Responsible for generating the div output.
		 *
		 * @param string $first_parameter div id.
		 * @param string $third_parameter div content.
		 * @param string $page page.
		 *
		 * @return string
		 */
		public function generate_div( $first_parameter, $third_parameter, $page ): string {

			$second_parameter = $this->get_page( $page );

			return sprintf( '<div id="%s" class="%s">%s</div>', $first_parameter, $second_parameter, $third_parameter );
		}

		/**
		 * Responsible for generating the span output.
		 *
		 * @param string $first_parameter div id.
		 * @param string $third_parameter div content.
		 * @param string $page page.
		 *
		 * @return string
		 */
		final public function generate_span( $first_parameter, $third_parameter, $page ): string {
			$second_parameter = $this->get_page( $page );

			return sprintf( '<span id="%s" class="%s"> %s </span>', $first_parameter, $second_parameter, $third_parameter );
		}

		/**
		 * Responsible for getting which page is.
		 *
		 * @param string $page page.
		 *
		 * @return string
		 */
		private function get_page( string $page ): string {
			if ( is_product() ) {
				$second_parameter = "$page-epi-single-product";
			} elseif ( is_shop() ) {
				$second_parameter = "$page-epi-shop";
			} elseif ( is_category() || is_product_category() ) {
				$second_parameter = "$page-epi-category";
			} elseif ( is_search() ) {
				$second_parameter = "$page-epi-search";
			} elseif ( is_home() ) {
				$second_parameter = "$page-epi-home";
			} else {
				$second_parameter = "$page";
			}

			return $second_parameter;
		}
	}
}
