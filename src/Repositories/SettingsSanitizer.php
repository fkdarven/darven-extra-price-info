<?php

namespace Darven\ExtraPriceInfo\Repositories;

use Darven\ExtraPriceInfo\Compatibility\YithDynamicPricingMode;
use InvalidArgumentException;

final class SettingsSanitizer {
	public function sanitizeSection( string $section, array $input ): array {
		switch ( $section ) {
			case 'general':
				return $this->sanitizeGeneral( $input );
			case 'positions':
				return $this->sanitizePositions( $input );
			case 'display':
				return $this->sanitizeDisplay( $input );
			case 'compatibility':
				return $this->sanitizeCompatibility( $input );
		}

		throw new InvalidArgumentException( 'Unknown settings section.' );
	}

	private function sanitizeGeneral( array $input ): array {
		$sanitized_values = array();

		foreach ( $this->getCheckboxFields() as $field => $checked_value ) {
			if ( isset( $input[ $field ] ) && $checked_value === $this->sanitizePlainText( $input[ $field ] ) ) {
				$sanitized_values[ $field ] = $checked_value;
			}
		}

		foreach ( $this->getEnumFields() as $field => $settings ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = $this->sanitizePlainText( $input[ $field ] );
				$sanitized_values[ $field ] = in_array( $value, $settings['allowed'], true ) ? $value : $settings['default'];
			}
		}

		foreach ( $this->getDecimalFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$sanitized_values[ $field ] = $this->sanitizeDecimalValue( $input[ $field ] );
			}
		}

		foreach ( $this->getIntegerFields() as $field => $minimum ) {
			if ( array_key_exists( $field, $input ) ) {
				$sanitized_values[ $field ] = $this->sanitizeIntegerValue( $input[ $field ], $minimum );
			}
		}

		foreach ( $this->getMarkupFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$sanitized_values[ $field ] = wp_kses( $this->getScalarInputValue( $input[ $field ] ), $this->getAllowedMarkupTags() );
			}
		}

		if ( array_key_exists( 'darven_epi_installments_interest_fee_table', $input ) ) {
			$sanitized_values['darven_epi_installments_interest_fee_table'] = $this->sanitizeInterestFeeTable(
				$input['darven_epi_installments_interest_fee_table']
			);
		}

		return $sanitized_values;
	}

	private function sanitizePositions( array $input ): array {
		$sanitized_values = array();
		$allowed_values   = array( 'first', 'second', 'third', 'fourth', 'fifth', 'sixth' );

		foreach ( array( 'darven_epi_others_product_position', 'darven_epi_single_product_position', 'darven_epi_catalog_product_position' ) as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = sanitize_text_field( $input[ $field ] );
				$sanitized_values[ $field ] = in_array( $value, $allowed_values, true ) ? $value : 'first';
			}
		}

		return $sanitized_values;
	}

	private function sanitizeDisplay( array $input ): array {
		$sanitized_values = array();
		foreach ( $this->getColorFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = strtolower( sanitize_text_field( $input[ $field ] ) );
				$sanitized_values[ $field ] = preg_match( '/^#(?:[0-9a-f]{3}){1,2}$/', $value ) ? $value : '';
			}
		}

		$allowed_font_sizes = array( '1.0', '1.1', '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.8', '1.9', '2.0' );
		foreach ( $this->getFontSizeFields() as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = sanitize_text_field( $input[ $field ] );
				$sanitized_values[ $field ] = in_array( $value, $allowed_font_sizes, true ) ? $value : '1.0';
			}
		}

		return $sanitized_values;
	}

	private function sanitizeCompatibility( array $input ): array {
		$value = array_key_exists( YithDynamicPricingMode::FIELD, $input ) ? sanitize_text_field( $input[ YithDynamicPricingMode::FIELD ] ) : '';
		$mode  = YithDynamicPricingMode::sanitize( $value );
		$values = array( YithDynamicPricingMode::FIELD => $mode );

		if ( YithDynamicPricingMode::AUTO === $mode ) {
			$values[ YithDynamicPricingMode::LEGACY_FIELD ] = YithDynamicPricingMode::LEGACY_FIELD;
		}

		return $values;
	}

	private function getCheckboxFields(): array {
		return array(
			'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
			'darven_epi_installments_is_enabled' => 'darven_epi_installments_is_enabled',
			'darven_epi_installments_interest_fee_is_table_enabled' => 'darven_epi_installments_interest_fee_is_table_enabled',
		);
	}

	private function getEnumFields(): array {
		return array(
			'darven_epi_type_of_discount' => array( 'allowed' => array( 'percent', 'fixed' ), 'default' => 'percent' ),
			'darven_epi_mode_of_view' => array( 'allowed' => array( 'default', 'popup', 'nofee' ), 'default' => 'default' ),
		);
	}

	private function getDecimalFields(): array {
		return array( 'darven_epi_minimum_installments_value', 'darven_epi_installments_interest_fee', 'darven_epi_installments_interest_fee_first_install', 'darven_epi_minimum_incash_value', 'darven_epi_value_of_incash_discount' );
	}

	private function getIntegerFields(): array {
		return array( 'darven_epi_max_installments' => 1, 'darven_epi_installments_interest_fee_from' => 0 );
	}

	private function getMarkupFields(): array {
		return array( 'darven_epi_installments_prefix', 'darven_epi_installments_suffix', 'darven_epi_incash_suffix', 'darven_epi_incash_prefix', 'darven_epi_popup_text' );
	}

	private function getAllowedMarkupTags(): array {
		return array(
			'a' => array( 'href' => array(), 'class' => array() ), 'br' => array(), 'i' => array(), 'b' => array(),
			'div' => array( 'style' => array(), 'class' => array() ), 'span' => array( 'style' => array(), 'class' => array() ),
			'p' => array( 'style' => array(), 'class' => array() ), 'em' => array(),
		);
	}

	private function getColorFields(): array {
		return array( 'darven_epi_color_of_installments_install', 'darven_epi_color_of_installments_prefix', 'darven_epi_color_of_installments_suffix', 'darven_epi_color_of_installments_price', 'darven_epi_color_of_incash_prefix', 'darven_epi_color_of_incash_suffix', 'darven_epi_color_of_incash_price' );
	}

	private function getFontSizeFields(): array {
		return array( 'darven_epi_font_size_of_incash_price', 'darven_epi_font_size_of_incash_suffix', 'darven_epi_font_size_of_incash_prefix', 'darven_epi_font_size_of_installments_price', 'darven_epi_font_size_of_installments_suffix', 'darven_epi_font_size_of_installments_prefix', 'darven_epi_font_size_of_installments_install' );
	}

	private function sanitizePlainText( $value ): string {
		return sanitize_text_field( $this->getScalarInputValue( $value ) );
	}

	private function getScalarInputValue( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return (string) wp_unslash( $value );
	}

	private function sanitizeDecimalValue( $value, float $minimum = 0.0 ): string {
		$number = $this->parseDecimalValue( $value );
		if ( null === $number || $number < $minimum ) {
			$number = $minimum;
		}

		return $this->formatNumberForOption( $number );
	}

	private function sanitizeIntegerValue( $value, int $minimum ): string {
		$number = $this->parseDecimalValue( $value );
		if ( null === $number ) {
			$number = 0.0;
		}

		$number = (int) floor( $number );
		if ( $number < $minimum ) {
			$number = $minimum;
		}

		return (string) $number;
	}

	private function sanitizeInterestFeeTable( $value ): string {
		$items = explode( '|', $this->sanitizePlainText( $value ) );
		$sanitized_values = array();
		foreach ( $items as $item ) {
			$number = $this->parseDecimalValue( $item );
			if ( null !== $number && $number >= 0 ) {
				$sanitized_values[] = $this->formatNumberForOption( $number );
			}
		}

		return implode( '|', $sanitized_values );
	}

	private function parseDecimalValue( $value ): ?float {
		$value = str_replace( ',', '.', $this->sanitizePlainText( $value ) );
		if ( ! preg_match( '/-?\d+(?:\.\d+)?/', $value, $matches ) ) {
			return null;
		}

		return (float) $matches[0];
	}

	private function formatNumberForOption( float $number ): string {
		$formatted = rtrim( rtrim( number_format( $number, 6, '.', '' ), '0' ), '.' );

		return '' === $formatted ? '0' : $formatted;
	}
}
