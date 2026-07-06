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
		 * @param string $component_class Component CSS class.
		 * @param string $content         Component content.
		 * @param string $page            Page context.
		 *
		 * @return string
		 */
		public function generate_div( $component_class, $content, $page ): string {

			$page_class = $this->get_page( $page );

			return sprintf( '<div class="%s %s">%s</div>', $component_class, $page_class, $content );
		}

		/**
		 * Responsible for generating the span output.
		 *
		 * @param string $component_class Component CSS class.
		 * @param string $content         Component content.
		 * @param string $page            Page context.
		 *
		 * @return string
		 */
		final public function generate_span( $component_class, $content, $page ): string {
			$page_class = $this->get_page( $page );

			return sprintf( '<span class="%s %s"> %s </span>', $component_class, $page_class, $content );
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
