<?php

namespace Darven\Epi\Admin\Tabs;

use Darven\Epi\Abstracts\AbstractSettingsFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeneralTab extends AbstractSettingsFields {

	public function get_slug(): string {
		return 'general';
	}

	public function get_settings_fields(): array {
		return array(
			'incash'       => array(
				'section' => $this->get_section( 'incash' ),
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar', 'checkbox', 'Controla se o preço á vista será exibido ou não', [], ),
					$this->build_field( 'type_of_discount', 'Tipo de Desconto', 'select', '', array(
						'fixed'      => 'Fixo',
						'percentage' => 'Porcentagem',
					) ),
					$this->build_field( 'value_of_discount', 'Valor do Desconto', 'text', '', [] ),
					$this->build_field( 'minimum_value', 'Valor Mínimo', 'text', 'Produtos com valores abaixo desse limiar não receberão desconto', [] ),
					$this->build_field( 'prefix', 'Prefixo', 'text', 'Texto que antecede o preço á vista', [] ),
					$this->build_field( 'suffix', 'Sufixo', 'text', 'Texto que sucede o preço á vista', [] ),
				),
			),
			'installments' => array(
				'section' => $this->get_section( 'installments' ),
				'fields'  => array(
					$this->build_field( 'is_enabled', 'Ativar', 'checkbox', 'Controla se o preço parcelado será exibido ou não', [] ),
					$this->build_field( 'type_of_interest', 'Tipo de Juros', 'select', 'Em caso de dúvidas, consulte o manual do plugin',
						array(
							'simple'   => 'Simples',
							'compound' => 'Composto',
						),
					),
					$this->build_field( 'prefix', 'Prefixo', 'text', 'Texto que antecede o preço parcelado', [] ),
					$this->build_field( 'suffix', 'Sufixo', 'text', 'Texto que sucede o preço parcelado', [] ),
					$this->build_field( 'mode_of_view', 'Modo de Exibição', 'select', 'Caso alguma opção de <i>div</i> seja habilitada, ela aparecerá embaixo do botão comprar',
						array(
							'default' => 'Máximo de parcelas',
							'popup'   => 'Máximo de parcelas e uma div informativa de parcela a parcela',
							'nofee'   => 'Máximo de parcelas sem juros e uma div informativa de parcela a parcela',
						),
					),
					$this->build_field( 'max_installments', 'Max. Parcelas', 'text', 'Máximo de parcelas possíveis, com ou sem juros', [] ),
					$this->build_field( 'interest_fee_from', 'Taxa de juros a partir da parcela', 'text', 'Parcelas antes desse limiar não terão juros', [] ),
					$this->build_field( 'interest_fee_first_install', 'Taxa de juros da primeira parcela', 'text', 'Taxa de juros exclusiva para a primeira parcela (com juros)', [] ),
					$this->build_field( 'interest_fee', 'Taxa de Juros Incremental', 'text', 'Taxa de juros incremental a partir da segunda parcela (com juros)', [] ),
					$this->build_field( 'minimum_value', 'Valor Mínimo', 'text', 'Valor mínimo de cada parcela', [] ),
					$this->build_field( 'interest_fee_is_table_enabled', 'Usar tabela de juros', 'checkbox', 'Permite customizar os juros de cada parcela separadamente', [] ),
					$this->build_field( 'interest_fee_table', 'Juros Customizados', 'text', 'Personalize a sua taxa de juros de acordo com suas necessidades', []),
				),
			),
		);
	}

}
