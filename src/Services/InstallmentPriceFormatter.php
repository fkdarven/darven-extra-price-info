<?php

namespace Darven\ExtraPriceInfo\Services;

use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class InstallmentPriceFormatter {
	/**
	 * @var array
	 */
	private $general_settings;

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
		$this->general_settings = $settings_repository->getSection( 'general' );
		$this->price_resolver   = $price_resolver;
		$this->markup_builder   = $markup_builder;
	}

	public function format( \WC_Product $product ): string {
		if ( empty( $this->general_settings['darven_epi_installments_is_enabled'] ) ) {
			return '';
		}

		$price                 = $this->price_resolver->getActivePrice( $product );
		$installment_price     = $this->getInstallmentPrice( $price );
		$price_table           = $this->getPriceTable( $price );
		$installment_data      = $this->getInstallmentData( $price );
		$number_of_installments = $installment_data['display_count'];

		if ( $installment_price <= 0 ) {
			return '';
		}

		$prefix = $this->markup_builder->span(
			'installment-prefix darven-epi-installment-prefix',
			(string) ( $this->general_settings['darven_epi_installments_prefix'] ?? '' ),
			'installments'
		);
		$suffix = $this->markup_builder->span(
			'installment-suffix darven-epi-installment-suffix',
			(string) ( $this->general_settings['darven_epi_installments_suffix'] ?? '' ),
			'installments'
		);

		$mode = (string) ( $this->general_settings['darven_epi_mode_of_view'] ?? '' );
		$installment_count = sprintf(
			/* translators: %1$s: number of installments. */
			__( '%1$sx of', 'darven-multiplos-precos-informativos' ),
			$number_of_installments
		);

		if ( 'popup' === $mode ) {
			$statement = $this->getStatement(
				$prefix,
				$installment_count,
				wp_strip_all_tags( wc_price( $price_table[1] ) ),
				$suffix
			);

			return $this->markup_builder->div(
				'installments-price-statement darven-epi-installments-price-statement',
				$statement . $this->getPopupMarkup( $price_table[0] ),
				'installments'
			);
		}

		if ( 'nofee' === $mode ) {
			$statement = $this->getStatement(
				$prefix,
				$installment_count,
				wp_strip_all_tags( wc_price( $installment_price ) ),
				$suffix
			);

			return $this->markup_builder->div(
				'installments-price-statement darven-epi-installments-price-statement',
				$statement . $this->getPopupMarkup( $price_table[0] ),
				'installments'
			);
		}

		$statement = $this->getStatement(
			$prefix,
			$installment_count,
			wp_strip_all_tags( wc_price( $price_table[1] ) ),
			$suffix
		);

		return $this->markup_builder->div(
			'installments-price-statement darven-epi-installments-price-statement',
			$statement,
			'installments'
		);
	}

	private function getStatement( string $prefix, string $count, string $price, string $suffix ): string {
		return sprintf(
			'%s<span class="darven-epi-installment-count">%s</span><span class="darven-epi-installment-price"> %s</span>%s',
			$prefix,
			$count,
			$price,
			$suffix
		);
	}

	private function getPopupMarkup( string $price_table ): string {
		$instance_id = $this->getPopupInstanceId();
		$dialog_id   = $instance_id . '-dialog';
		$title_id    = $instance_id . '-title';
		$popup_text  = wp_kses(
			(string) ( $this->general_settings['darven_epi_popup_text'] ?? '' ),
			array(
				'br'   => array(),
				'b'    => array(),
				'em'   => array(),
				'i'    => array(),
				'span' => array(
					'class' => array(),
					'style' => array(),
				),
			)
		);
		$popup_label = trim(
			html_entity_decode( wp_strip_all_tags( $popup_text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
		);

		if ( '' === $popup_label ) {
			$popup_label = __( 'View installment options', 'darven-multiplos-precos-informativos' );
			$popup_text  = esc_html( $popup_label );
		}

		$title       = esc_html( __( 'Installment options', 'darven-multiplos-precos-informativos' ) );
		$close_label = esc_attr( __( 'Close installment options', 'darven-multiplos-precos-informativos' ) );

		$popup = '<div id="' . esc_attr( $dialog_id )
			. '" class="messagepop pop darven-epi-installments-popup" hidden aria-hidden="true"'
			. ' role="dialog" aria-modal="true" aria-labelledby="' . esc_attr( $title_id ) . '">'
			. '<div class="darven-epi-installments-backdrop" aria-hidden="true"></div>'
			. '<div class="darven-epi-installments-dialog" role="document">'
			. '<h2 id="' . esc_attr( $title_id )
			. '" class="screen-reader-text darven-epi-installments-title">' . $title . '</h2>'
			. '<button type="button" class="darven-epi-installments-close" aria-label="' . $close_label . '">'
			. '<span aria-hidden="true">&times;</span>'
			. '</button>'
			. '<div class="darven-epi-installments-table-wrap">' . $price_table . '</div>'
			. '</div>'
			. '</div>';
		$toggle = '<button type="button" class="darven-epi-installments-toggle" aria-expanded="false" aria-controls="'
			. esc_attr( $dialog_id ) . '" aria-label="' . esc_attr( $popup_label ) . '">' . $popup_text . '</button>';

		return $popup . $toggle;
	}

	private function getPopupInstanceId(): string {
		$unique_id = function_exists( 'wp_generate_uuid4' )
			? wp_generate_uuid4()
			: uniqid( '', true );

		return 'darven-epi-installments-' . $unique_id;
	}

	public function getPriceTable( float $price ): array {
		$install_count = $this->getInstallmentData( $price )['table_count'];
		$html_result   = '<table class="installments_table darven-epi-installments-table">';
		$installment_price = '';

		if ( ! empty( $this->general_settings['darven_epi_installments_interest_fee_is_table_enabled'] ) ) {
			$customised_values = array_map(
				static function ( $value ): float {
					$value = str_replace( ',', '.', trim( $value ) );

					return is_numeric( $value ) ? (float) $value : 0.0;
				},
				explode( '|', (string) ( $this->general_settings['darven_epi_installments_interest_fee_table'] ?? '' ) )
			);
			$interest_fee_from = max( 1, (int) ( $this->general_settings['darven_epi_installments_interest_fee_from'] ?? 0 ) );

			for ( $installment = 1; $installment <= $install_count; $installment++ ) {
				$interest_rate = 0.0;

				if ( $installment >= $interest_fee_from ) {
					$interest_index = $installment - $interest_fee_from;
					$interest_rate  = $customised_values[ $interest_index ] ?? 0.0;
				}

				$total_with_interest = $price * ( 1 + ( $interest_rate / 100 ) );
				$installment_price   = $total_with_interest / $installment;
				$html_result        .= '<tr>';
				$html_result        .= '<td>' . $installment . 'x de</td><td>' . wc_price( $installment_price ) . '</td>';
				$html_result        .= '</tr>';
			}

			return array( $html_result . '</table>', $installment_price );
		}

		$price_for_interest = $price;
		$first_install      = true;
		$interest_fee_from  = (int) ( $this->general_settings['darven_epi_installments_interest_fee_from'] ?? 0 );

		for ( $installment = 1; $installment <= $install_count; $installment++ ) {
			$html_result .= '<tr>';

			if ( 0 !== $interest_fee_from && $installment >= $interest_fee_from ) {
				if ( $first_install ) {
					$price_for_interest = ( $price_for_interest
						* (float) ( $this->general_settings['darven_epi_installments_interest_fee_first_install'] ?? 0 ) / 100 )
						+ $price_for_interest;
					$first_install = false;
				}

				$installment_price = $this->getTaxCalculation( $price_for_interest, 'second_period', $installment );
				$html_result      .= '<td>' . $installment . 'x</td><td>' . wc_price( $installment_price )
					. '  = ' . wc_price( $installment_price * $installment ) . '</td>';
			} else {
				$installment_price = $price_for_interest / $installment;
				$html_result      .= '<td>' . $installment . 'x</td><td>' . wc_price( $installment_price ) . '</td>';
			}

			$html_result .= '</tr>';
		}

		return array( $html_result . '</table>', $installment_price );
	}

	public function getInstallmentPrice( float $price ): float {
		$installment_data = $this->getInstallmentData( $price );

		if ( $installment_data['price_count'] <= 0 ) {
			return 0.0;
		}

		return $price / $installment_data['price_count'];
	}

	public function getTaxCalculation( float $price, string $mode, int $installment ): float {
		$interest_rate    = (float) ( $this->general_settings['darven_epi_installments_interest_fee'] ?? 0 ) / 100;
		$first_part_upper = ( $interest_rate + 1 ) ** $installment;

		if ( 'first_period' === $mode ) {
			$second_part_upper = $price * $first_part_upper;
			$total_upper       = $second_part_upper * $first_part_upper;
			$first_part_under  = $interest_rate * $first_part_upper;
			$total_under       = $first_part_under - $interest_rate + $first_part_upper - 1;

			return $total_upper / $total_under;
		}

		$first_part_under  = 1 / $first_part_upper;
		$second_part_under = 1 - $first_part_under;

		if ( 0.0 === $second_part_under ) {
			$second_part_under = 1;
		}

		return $price * ( $interest_rate / $second_part_under );
	}

	private function getInstallmentData( float $price ): array {
		$maximum_installments = (int) ( $this->general_settings['darven_epi_max_installments'] ?? 0 );

		if ( $maximum_installments <= 0 ) {
			$maximum_installments = 1;
		}

		$minimum_price = number_format( (float) ( $this->general_settings['darven_epi_minimum_installments_value'] ?? 0 ) );

		if ( $minimum_price <= 0 ) {
			$minimum_price = 1;
		}

		$table_count   = (int) floor( $price / $minimum_price );
		$display_count = $table_count;
		$price_count   = $table_count;

		if ( $table_count > $maximum_installments ) {
			$table_count   = $maximum_installments;
			$display_count = $maximum_installments;
			$price_count   = $maximum_installments;
		}

		$interest_fee_from = (int) ( $this->general_settings['darven_epi_installments_interest_fee_from'] ?? 0 );

		if (
			$price_count > $interest_fee_from
			&& $display_count > $interest_fee_from
			&& 'nofee' === ( $this->general_settings['darven_epi_mode_of_view'] ?? '' )
		) {
			$price_count   = $interest_fee_from - 1;
			$display_count = $price_count;
		}

		return array(
			'table_count'   => $table_count,
			'display_count' => $display_count,
			'price_count'   => $price_count,
		);
	}
}
