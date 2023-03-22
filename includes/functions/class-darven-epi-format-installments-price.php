<?php
/*
 *
 * Class responsible for handling installments prices. Special functions for grouped and variable prices
 * In case of a product being bundled/variable, the price considered is always the lowest.
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_Format_Installments_Price' ) ) {
	class Darven_Epi_Format_Installments_Price {

		private string $maximum_installments;
		private string $installments_prefix;
		private string $installments_suffix;
		private string $minimum_price;
		private string $number_of_installments;
		private string $mode_of_view;
		private string $interest_fee;
		private string $interest_fee_from;

		private string $install_count;
		private string $installments_popup_text;
		private string $interest_fee_first_install;
		private string $is_table_enabled;
		private string $customised_values;

		public function __construct() {

			if ( ! isset( get_option( 'darven_epi_option_general' )['darven_epi_installments_is_enabled'] ) || ! get_option( 'darven_epi_option_general' )['darven_epi_installments_is_enabled'] ) {
				return;
			}

			$this->initiate_options();


		}

		public function initiate_options(): void {
			$this->maximum_installments = get_option( 'darven_epi_option_general' )['darven_epi_max_installments'];

			if ( $this->maximum_installments <= 0 ) {
				$this->maximum_installments = 1;
			}

			$this->minimum_price       = get_option( 'darven_epi_option_general' )['darven_epi_minimum_installments_value'];
			$this->installments_prefix = get_option( 'darven_epi_option_general' )['darven_epi_installments_prefix'];
			$this->installments_suffix = get_option( 'darven_epi_option_general' )['darven_epi_installments_suffix'];


			if ( isset( get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee_is_table_enabled'] ) && get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee_is_table_enabled'] ) {
				$this->is_table_enabled  = true;
				$this->customised_values = get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee_table'];
			} else {
				$this->is_table_enabled = false;
			}


			$this->interest_fee               = get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee'] ?: 0;
			$this->interest_fee_from          = get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee_from'] ?: 0;
			$this->interest_fee_first_install = get_option( 'darven_epi_option_general' )['darven_epi_installments_interest_fee_first_install'] ?: 0;


			$this->mode_of_view            = get_option( 'darven_epi_option_general' )['darven_epi_mode_of_view'];
			$this->installments_popup_text = get_option( 'darven_epi_option_general' )['darven_epi_popup_text'];
		}

		public function get_discount_price(): string {

			$clean_price       = $this->get_active_price();
			$installment_price = $this->get_installments_price( $clean_price );
			$price_result      = $this->get_price_table( $clean_price );


			if ( $installment_price <= 0 ) {
				return "";
			}
			if ( $this->mode_of_view === 'popup' ) {

				$installments_statement = '<br><span id="installment-prefix">' . esc_attr( $this->installments_prefix ) . '</span><span id="installment-install"> ' . esc_attr( $this->number_of_installments ) . 'x de </span><span id="installment-price"> ' . wc_price( $price_result[1] ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span>';

				return $installments_statement . '<div class="messagepop pop">' . $price_result[0] . '</div> <a href="#" id="contact">' . $this->installments_popup_text . '</a>';

			}


			if ( $this->mode_of_view === 'nofee' ) {
				$installments_statement = '<br><span id="installment-prefix">' . esc_attr( $this->installments_prefix ) . '</span><span id="installment-install"> ' . esc_attr( $this->number_of_installments ) . 'x de </span><span id="installment-price"> ' . wc_price( $installment_price ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span>';

				return $installments_statement . '<div class="messagepop pop">' . $price_result[0] . '</div> <a href="#" id="contact">' . $this->installments_popup_text . '</a>';
			}

			return '<br><span id="installment-prefix">' . esc_attr( $this->installments_prefix ) . '</span><span id="installment-install"> ' . esc_attr( $this->number_of_installments ) . 'x de </span><span id="installment-price"> ' . wc_price( $price_result[1] ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span>';


		}

		public function get_active_price(): float {

			global $product;
			$regular_price = (float) $product->get_price();
			$sale_price    = (float) $product->get_sale_price();

			if ( $sale_price ) {
				return $sale_price;
			}

			return $regular_price;
		}


		public function get_installments_price( $price ): ?string {


			$maximum_installments = $this->maximum_installments;
			$minimum_price        = number_format( (float) $this->minimum_price );

			if ( $minimum_price <= 0 ) {
				$minimum_price = 1;

			}

			$install_count = floor( $price / $minimum_price );


			if ( $install_count > $maximum_installments ) {
				$install_count = $maximum_installments;
			}

			$this->number_of_installments = $install_count;
			$this->install_count          = $install_count;

			if ( ( $install_count > $this->interest_fee_from ) && $this->number_of_installments > $this->interest_fee_from && $this->mode_of_view === 'nofee' ) {
				$install_count                = $this->interest_fee_from - 1;
				$this->number_of_installments = $install_count;
			}

			//$install_price = round( $install_price, 2 );
			if ( (float) $install_count <= 0.0 ) {
				return 0.0;
			}

			return (float) $price / $install_count;


		}


		public function get_price_table( $price ): array {

			$install_count = $this->install_count;

			$html_result    = '<table id="installments_table">';
			$i_price        = "";
			$first_install  = true;
			$interest_count = 0;


			if ( $this->is_table_enabled ) {

				str_replace( " ", "", trim( $this->customised_values ) );
				$customised_values = str_replace( ",", ".", trim( $this->customised_values ) );
				$customised_values = explode( "|", $customised_values );


				for ( $shalk = 0; $shalk <= $this->interest_fee_from --; $shalk ++ ) {
					array_unshift( $customised_values, 0 );
				}
				for ( $i = 1; $i <= $install_count; $i ++ ) {

					if ( isset( $this->interest_fee_from ) && $i >= $this->interest_fee_from ) {
						$html_result .= '<tr>';
						if ( $first_install ) {
							$price_        = ( (float) $price * $customised_values[0] / 100 ) + $price;
							$first_install = false;
							$install_price = $price_ / $i;
							$i_price       = $install_price;
						} else {
							$install_price = $price / $i;
							$i_price       = ( $install_price * $customised_values[ $i ] / 100 ) + $install_price;

						}

						$html_result .= '<td>' . $i . 'x de</td><td>' . wc_price( $i_price ) . '</td>';


					} else {
						$i_price     = $price / $i;
						$html_result .= '<td>' . $i . 'x de</td><td>' . wc_price( $price / $i ) . '</td>';
					}
					$html_result .= '</tr>';
				}
				$html_result .= '</table>';

				return array( $html_result, $i_price );

			}


			for ( $i = 1; $i <= $install_count; $i ++ ) {

				if ( isset( $this->interest_fee_from ) && $i >= $this->interest_fee_from ) {
					if ( $first_install ) {
						$price         = ( $price * (float) $this->interest_fee_first_install / 100 ) + $price;
						$first_install = false;
					}

					$html_result .= '<tr>';
					$i_price = $this->get_tax_calculation( $price, 'second_period', $i );
					$html_result .= '<td>' . $i . 'x</td><td>' . wc_price( $i_price ) . '  = ' . wc_price( $i_price * $i ) . '</td>';

				} else {
					$i_price     = $price / $i;
					$html_result .= '<td>' . $i . 'x</td><td>' . wc_price( $price / $i ) . '</td>';
				}
				$html_result .= '</tr>';
			}
			$html_result .= '</table>';

			return array( $html_result, $i_price );
		}

		public function get_tax_calculation( $price, $mode, $installment ): float {

			// INICIO DO PERIODO
			$i                = $installment;
			$j                = $this->interest_fee / 100;
			$first_part_upper = ( $j + 1 ) ** $i;
			if ( $mode === 'first_period' ) {
				$second_part_upper = $price * $first_part_upper;
				$total_upper       = $second_part_upper * $first_part_upper;
				$first_part_under  = $j * $first_part_upper;
				$total_under       = $first_part_under - $j + $first_part_upper - 1;

				return $total_upper / $total_under;
			}

			$first_part_under  = 1 / $first_part_upper;
			$second_part_under = 1 - $first_part_under;
			$total_under       = $j / $second_part_under;

			return $price * $total_under;

		}
	}


}

$Darven_Epi_Format_Installments_Price = new Darven_Epi_Format_Installments_Price();