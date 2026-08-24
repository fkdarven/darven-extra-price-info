<?php

namespace Darven\ExtraPriceInfo\Frontend;

use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class InlineStyles {
	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	public function render(): string {
		$display = $this->settings_repository->getSection( 'display' );

		return str_replace( '\\n', "\n", '\n    .darven-epi-incash-prefix {\n\n        color: ' . $this->getValue( $display, 'darven_epi_color_of_incash_prefix' ) . ';\n        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_incash_prefix', '1' ) . 'em;\n    }\n\n    .darven-epi-incash-price {\n\n        color: ' . $this->getValue( $display, 'darven_epi_color_of_incash_price' ) . ' !important;\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_incash_price', '1' ) . 'em;\n\n'
			. '    }\n\n'
			. '    .darven-epi-incash-suffix {\n\n'
			. '        color: ' . $this->getValue( $display, 'darven_epi_color_of_incash_suffix' ) . ';\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_incash_suffix', '1' ) . 'em;\n'
			. '    }\n\n\n'
			. '    .darven-epi-installment-prefix {\n\n'
			. '        color: ' . $this->getValue( $display, 'darven_epi_color_of_installments_prefix' ) . ';\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_installments_prefix', '1' ) . 'em;\n\n'
			. '    }\n\n'
			. '    .darven-epi-installment-count {\n\n'
			. '        color: ' . $this->getValue( $display, 'darven_epi_color_of_installments_install' ) . ';\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_installments_install', '1' ) . 'em;\n\n'
			. '    }\n\n'
			. '    .darven-epi-installment-price {\n\n'
			. '        color: ' . $this->getValue( $display, 'darven_epi_color_of_installments_price' ) . ';\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_installments_price', '1' ) . 'em;\n\n'
			. '    }\n\n'
			. '    .darven-epi-installment-suffix {\n\n'
			. '        color: ' . $this->getValue( $display, 'darven_epi_color_of_installments_suffix' ) . ';\n'
			. '        font-size: ' . $this->getValue( $display, 'darven_epi_font_size_of_installments_suffix', '1' ) . 'em;\n'
			. '    }\n\n\n' );
	}

	private function getValue( array $display, string $key, string $default = '' ): string {
		return esc_attr( (string) ( $display[ $key ] ?? $default ) );
	}
}
