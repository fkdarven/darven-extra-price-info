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
            $darven_options             = get_option( 'darven_epi_option_general' );
            $this->maximum_installments = $darven_options['darven_epi_max_installments'];

            if ( $this->maximum_installments <= 0 ) {
                $this->maximum_installments = 1;
            }

            $this->minimum_price       = $darven_options['darven_epi_minimum_installments_value'];
            $this->installments_prefix = $darven_options['darven_epi_installments_prefix'];
            $this->installments_suffix = $darven_options['darven_epi_installments_suffix'];

            if ( isset( $darven_options['darven_epi_installments_interest_fee_is_table_enabled'] ) ) {
                $this->is_table_enabled  = true;
                $this->customised_values = $darven_options['darven_epi_installments_interest_fee_table'];
            } else {
                $this->is_table_enabled = false;
            }

            $this->interest_fee               = $darven_options['darven_epi_installments_interest_fee'] ?: 0;
            $this->interest_fee_from          = $darven_options['darven_epi_installments_interest_fee_from'] ?: 0;
            $this->interest_fee_first_install = $darven_options['darven_epi_installments_interest_fee_first_install'] ?: 0;

            

            $this->mode_of_view            = $darven_options['darven_epi_mode_of_view'];
            $this->installments_popup_text = $darven_options['darven_epi_popup_text'];
        }

        public function get_discount_price(): string {

            $darven_product_price = new Darven_Epi_Product_Price();
            $clean_price          = $darven_product_price->get_active_price();
            $installment_price    = $this->get_installments_price( $clean_price );
            $price_result         = $this->get_price_table( $clean_price );


            if ( $installment_price <= 0 ) {
                return "";
            }

            if ( $this->mode_of_view === 'popup' ) {
                    return "teste1";
                $installments_statement = '<span id="installment-prefix">' . $this->installments_prefix . '</span><span id="installment-install"> ' . $this->number_of_installments . 'x de </span><span id="installment-price"> ' . strip_tags( wc_price( $price_result[1] ) ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span>';

                return '<div id="installments-price-statement">' . $installments_statement . '<div class="messagepop pop">' . $price_result[0] . '</div> <a href="#" id="contact">' . $this->installments_popup_text . '</a></div>';
            }
            if ( $this->mode_of_view === 'nofee' ) {
                $installments_statement = '<span id="installment-prefix">' . $this->installments_prefix . '</span><span id="installment-install"> ' . $this->number_of_installments . 'x de </span><span id="installment-price"> ' . strip_tags( wc_price( $installment_price ) ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span>';

                return '<div id="installments-price-statement">' . $installments_statement . '<div class="messagepop pop">' . $price_result[0] . '</div> <a href="#" id="contact">' . $this->installments_popup_text . '</a></div>';
            }

            return '<div id="installments-price-statement"><span id="installment-prefix">' . $this->installments_prefix . '</span><span id="installment-install"> ' . $this->number_of_installments . 'x de </span><span id="installment-price"> ' . strip_tags( wc_price( $price_result[1] ) ) . '</span><span id="installment-suffix"> ' . $this->installments_suffix . '</span></div>';


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


            if ( (float) $install_count <= 0.0 ) {
                return 0.0;
            }

            return (float) $price / $install_count;


        }

        public function get_price_table( $price ): array {

            $install_count  = $this->install_count;
            $html_result    = '<table id="installments_table">';
            $i_price        = "";
            $first_install  = true;
            $interest_count = 0;


            if ( $this->is_table_enabled ) {

                str_replace( " ", "", trim( $this->customised_values ) );
                $customised_values = str_replace( ",", ".", trim( $this->customised_values ) );
                $customised_values = explode( "|", $customised_values );


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

            } else {
                return $this->get_price_table_2( $price );
            }

        }

        public function get_price_table_2( $price ): array {
            $install_count  = $this->install_count;
            $html_result    = '<table id="installments_table">';
            $i_price        = "";
            $first_install  = true;
            $interest_count = 0;

            for ( $i = 1; $i <= $install_count; $i ++ ) {

                if ( isset( $this->interest_fee_from ) && $i >= $this->interest_fee_from && $this->interest_fee_from != 0 ) {
                    if ( $first_install ) {
                        $price         = ( $price * (float) $this->interest_fee_first_install / 100 ) + $price;
                        $first_install = false;
                    }

                    $html_result .= '<tr>';
                    $i_price     = $this->get_tax_calculation( $price, 'second_period', $i );
                    $i_price     = number_format( $i_price, 2 );
                    $html_result .= '<td>' . $i . 'x</td><td>' . wc_price( $i_price ) . '  = ' . wc_price( $i_price * $i ) . '</td>';

                } else {
                    $i;
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
            if ( $second_part_under == 0 ) {
                $second_part_under = 1;
            }
            $total_under = $j / $second_part_under;

            return $price * $total_under;

        }
    }


}

$Darven_Epi_Format_Installments_Price = new Darven_Epi_Format_Installments_Price();

