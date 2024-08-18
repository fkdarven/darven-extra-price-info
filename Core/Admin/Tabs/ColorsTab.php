<?php

namespace Darven\Epi\Admin\Tabs;

use Darven\Epi\Abstracts\AbstractSettingsFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ColorsTab extends AbstractSettingsFields {

	public function get_slug(): string {
		return 'colors';
	}

	public function get_settings_fields(): array {
		return array(
			'incash'       => array(
				'section' => DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_incash_settings_colors_section',
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar Cores Customizadas', 'checkbox', '', [] ),
					$this->build_field( 'prefix_color', 'Cor do Prefixo', 'colorpicker', '', [] ),
					$this->build_field( 'suffix_color', 'Cor do Sufixo', 'colorpicker', '', [] ),
					$this->build_field( 'price_color', 'Cor do Preço', 'colorpicker', '', [] ),
				),
			),
			'installments' => array(
				'section' => DARVEN_EXTRA_PRICE_OPTIONS_BASE . '_installments_settings_colors_section',
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar Cores Customizadas', 'checkbox', '', [] ),
					$this->build_field( 'prefix_color', 'Cor do Prefixo', 'colorpicker', '', [] ),
					$this->build_field( 'suffix_color', 'Cor do Sufixo', 'colorpicker', '', [] ),
					$this->build_field( 'price_color', 'Cor do Preço', 'colorpicker', '', [] ),
					$this->build_field( 'installments_price', 'Cor das parcelas', 'colorpicker', '', [] ),
				),
			),
		);
	}

}
