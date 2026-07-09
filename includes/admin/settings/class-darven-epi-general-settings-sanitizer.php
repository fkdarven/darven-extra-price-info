<?php
/**
 * Sanitizes the values saved from the general settings tab.
 *
 * @package Darven_Epi
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_General_Settings_Sanitizer' ) ) {
	/**
	 * Keeps the general tab sanitization explicit per field type.
	 */
	class Darven_Epi_General_Settings_Sanitizer {
		/**
		 * Sanitizes a submitted general settings payload.
		 *
		 * @param mixed $input Raw settings payload.
		 *
		 * @return array<string,string>
		 */
		public function sanitize( $input ): array {
			if ( ! is_array( $input ) ) {
				return array();
			}

			$sanitized_values = array();

			foreach ( $this->get_checkbox_fields() as $field => $checked_value ) {
				if ( isset( $input[ $field ] ) && $this->sanitize_plain_text( $input[ $field ] ) === $checked_value ) {
					$sanitized_values[ $field ] = $checked_value;
				}
			}

			foreach ( $this->get_enum_fields() as $field => $enum_settings ) {
				if ( array_key_exists( $field, $input ) ) {
					$value = $this->sanitize_plain_text( $input[ $field ] );

					$sanitized_values[ $field ] = in_array( $value, $enum_settings['allowed'], true )
						? $value
						: $enum_settings['default'];
				}
			}

			foreach ( $this->get_decimal_fields() as $field ) {
				if ( array_key_exists( $field, $input ) ) {
					$sanitized_values[ $field ] = $this->sanitize_decimal_value( $input[ $field ] );
				}
			}

			foreach ( $this->get_integer_fields() as $field => $minimum ) {
				if ( array_key_exists( $field, $input ) ) {
					$sanitized_values[ $field ] = $this->sanitize_integer_value( $input[ $field ], $minimum );
				}
			}

			foreach ( $this->get_markup_fields() as $field ) {
				if ( array_key_exists( $field, $input ) ) {
					$sanitized_values[ $field ] = wp_kses(
						$this->get_scalar_input_value( $input[ $field ] ),
						$this->get_allowed_markup_tags()
					);
				}
			}

			if ( array_key_exists( 'darven_epi_installments_interest_fee_table', $input ) ) {
				$sanitized_values['darven_epi_installments_interest_fee_table'] = $this->sanitize_interest_fee_table(
					$input['darven_epi_installments_interest_fee_table']
				);
			}

			return $sanitized_values;
		}

		/**
		 * Gets checkbox fields and their accepted checked values.
		 *
		 * @return array<string,string>
		 */
		private function get_checkbox_fields(): array {
			return array(
				'darven_epi_incash_is_enabled'       => 'darven_epi_incash_is_enabled',
				'darven_epi_installments_is_enabled' => 'darven_epi_installments_is_enabled',
				'darven_epi_installments_interest_fee_is_table_enabled' => 'darven_epi_installments_interest_fee_is_table_enabled',
			);
		}

		/**
		 * Gets select fields, their allowed values, and their defaults.
		 *
		 * @return array<string,array{allowed:array<int,string>,default:string}>
		 */
		private function get_enum_fields(): array {
			return array(
				'darven_epi_type_of_discount' => array(
					'allowed' => array(
						'percent',
						'fixed',
					),
					'default' => 'percent',
				),
				'darven_epi_mode_of_view'     => array(
					'allowed' => array(
						'default',
						'popup',
						'nofee',
					),
					'default' => 'default',
				),
			);
		}

		/**
		 * Gets fields that should be saved as decimal numbers.
		 *
		 * @return array<int,string>
		 */
		private function get_decimal_fields(): array {
			return array(
				'darven_epi_minimum_installments_value',
				'darven_epi_installments_interest_fee',
				'darven_epi_installments_interest_fee_first_install',
				'darven_epi_minimum_incash_value',
				'darven_epi_value_of_incash_discount',
			);
		}

		/**
		 * Gets fields that should be saved as integer numbers.
		 *
		 * @return array<string,int>
		 */
		private function get_integer_fields(): array {
			return array(
				'darven_epi_max_installments' => 1,
				'darven_epi_installments_interest_fee_from' => 0,
			);
		}

		/**
		 * Gets fields that intentionally allow limited HTML markup.
		 *
		 * @return array<int,string>
		 */
		private function get_markup_fields(): array {
			return array(
				'darven_epi_installments_prefix',
				'darven_epi_installments_suffix',
				'darven_epi_incash_suffix',
				'darven_epi_incash_prefix',
				'darven_epi_popup_text',
			);
		}

		/**
		 * Gets the HTML tags allowed in display text settings.
		 *
		 * @return array<string,array<string,array<mixed>>>
		 */
		private function get_allowed_markup_tags(): array {
			return array(
				'a'    => array(
					'href'  => array(),
					'class' => array(),
				),
				'br'   => array(),
				'i'    => array(),
				'b'    => array(),
				'div'  => array(
					'style' => array(),
					'class' => array(),
				),
				'span' => array(
					'style' => array(),
					'class' => array(),
				),
				'p'    => array(
					'style' => array(),
					'class' => array(),
				),
				'em'   => array(),
			);
		}

		/**
		 * Sanitizes a scalar value as plain text.
		 *
		 * @param mixed $value Raw value.
		 */
		private function sanitize_plain_text( $value ): string {
			return sanitize_text_field( $this->get_scalar_input_value( $value ) );
		}

		/**
		 * Converts scalar input values to strings after unslashing.
		 *
		 * @param mixed $value Raw value.
		 */
		private function get_scalar_input_value( $value ): string {
			if ( is_array( $value ) || is_object( $value ) ) {
				return '';
			}

			return (string) wp_unslash( $value );
		}

		/**
		 * Sanitizes a value as a decimal option string.
		 *
		 * @param mixed $value   Raw value.
		 * @param float $minimum Minimum accepted value.
		 */
		private function sanitize_decimal_value( $value, float $minimum = 0.0 ): string {
			$number = $this->parse_decimal_value( $value );

			if ( null === $number || $number < $minimum ) {
				$number = $minimum;
			}

			return $this->format_number_for_option( $number );
		}

		/**
		 * Sanitizes a value as an integer option string.
		 *
		 * @param mixed $value   Raw value.
		 * @param int   $minimum Minimum accepted value.
		 */
		private function sanitize_integer_value( $value, int $minimum ): string {
			$number = $this->parse_decimal_value( $value );

			if ( null === $number ) {
				$number = 0.0;
			}

			$number = (int) floor( $number );

			if ( $number < $minimum ) {
				$number = $minimum;
			}

			return (string) $number;
		}

		/**
		 * Sanitizes the custom interest fee table list.
		 *
		 * @param mixed $value Raw value.
		 */
		private function sanitize_interest_fee_table( $value ): string {
			$items            = explode( '|', $this->sanitize_plain_text( $value ) );
			$sanitized_values = array();

			foreach ( $items as $item ) {
				$number = $this->parse_decimal_value( $item );

				if ( null === $number || $number < 0 ) {
					continue;
				}

				$sanitized_values[] = $this->format_number_for_option( $number );
			}

			return implode( '|', $sanitized_values );
		}

		/**
		 * Parses the first decimal number from a raw value.
		 *
		 * @param mixed $value Raw value.
		 */
		private function parse_decimal_value( $value ): ?float {
			$value = str_replace( ',', '.', $this->sanitize_plain_text( $value ) );

			if ( ! preg_match( '/-?\d+(?:\.\d+)?/', $value, $matches ) ) {
				return null;
			}

			return (float) $matches[0];
		}

		/**
		 * Formats a numeric option without unnecessary trailing zeroes.
		 *
		 * @param float $number Parsed value.
		 */
		private function format_number_for_option( float $number ): string {
			$formatted = rtrim( rtrim( number_format( $number, 6, '.', '' ), '0' ), '.' );

			return '' === $formatted ? '0' : $formatted;
		}
	}
}
