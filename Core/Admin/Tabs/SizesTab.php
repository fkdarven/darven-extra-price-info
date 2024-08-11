<?php

namespace Darven\Epi\Admin\Tabs;

use Darven\Epi\Abstracts\AbstractSettingsFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SizesTab extends AbstractSettingsFields {

	public function get_slug(): string {
		return 'sizes';
	}

	public function get_settings_fields(): array {
		return array(
			'incash'       => array(
				'section' => $this->get_section( 'incash' ),
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar Tamanhos Customizados', 'checkbox', '', [] ),
					$this->build_field( 'prefix_size', 'Tamanho do prefixo', 'int', 'Ex: 15px, 12em, 100%', [] ),
					$this->build_field( 'suffix_size', 'Tamanho do sufixo', 'int', 'Ex: 15px, 12em, 100%', [] ),
					$this->build_field( 'price_size', 'Tamanho do preço', 'int', 'Ex: 15px, 12em, 100%', [] ),
				),
			),
			'installments' => array(
				'section' => $this->get_section( 'installments' ),
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar Tamanhos Customizados', 'checkbox', '', [] ),
					$this->build_field( 'prefix_size', 'Tamanho do prefixo', 'int', 'Ex: 15px, 12em, 100%', [] ),
					$this->build_field( 'suffix_size', 'Tamanho do sufixo', 'int', 'Ex: 15px, 12em, 100%', [] ),
					$this->build_field( 'price_size', 'Tamanho do preço', 'int', 'Ex: 15px, 12em, 100%', [] ),
				),
			),
		);
	}
}
