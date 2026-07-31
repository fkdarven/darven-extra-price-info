<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;
use Darven\ExtraPriceInfo\Repositories\SettingsSanitizer;

final class GeneralFields {
	/**
	 * @var array<string,string>
	 */
	private $options = array();

	public function register(): void {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_general', array( $this, 'sanitize' ) );
		$options       = get_option( 'darven_epi_option_general' );
		$this->options = is_array( $options ) ? $options : array();

		$this->registerFields();
	}

	public function sanitize( $input ): array {
		$values = is_array( $input ) ? $input : array();

		return LegacySettingsSync::save( 'general', ( new SettingsSanitizer() )->sanitizeSection( 'general', $values ) );
	}

	private function registerFields(): void {
		$this->registerCheckbox( 'darven_epi_incash_is_enabled', __( 'Enable', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerSelect(
			'darven_epi_type_of_discount',
			__( 'Type of Discount', 'darven-epi' ),
			'darven_epi_incash_settings_section',
			array( 'percent' => __( 'Percent', 'darven-epi' ), 'fixed' => __( 'Fixed', 'darven-epi' ) )
		);
		$this->registerText( 'darven_epi_value_of_incash_discount', __( 'Value of Discount', 'darven-epi' ), 'darven_epi_incash_settings_section' );
		$this->registerText(
			'darven_epi_minimum_incash_value',
			__( 'Minimum price to the discount to be applied', 'darven-epi' ),
			'darven_epi_incash_settings_section',
			__( 'If there is not a minimum price, you may leave the field blank.', 'darven-epi' )
		);
		$this->registerText( 'darven_epi_incash_prefix', __( 'Prefix', 'darven-epi' ), 'darven_epi_incash_settings_section', __( 'Text before the in cash price.', 'darven-epi' ) );
		$this->registerText( 'darven_epi_incash_suffix', __( 'Suffix', 'darven-epi' ), 'darven_epi_incash_settings_section', __( 'Text after the in cash price.', 'darven-epi' ) );

		$this->registerCheckbox( 'darven_epi_installments_is_enabled', __( 'Enable', 'darven-epi' ), 'darven_epi_installments_settings_section' );
		$this->registerSelect(
			'darven_epi_mode_of_view',
			__( 'Mode of View', 'darven-epi' ),
			'darven_epi_installments_settings_section',
			array(
				'default' => __( 'Maximum of installments', 'darven-epi' ),
				'popup'   => __( 'Maximum of installments and a popup with each installment', 'darven-epi' ),
				'nofee'   => __( 'Maximum of installments without interest fee and a popup with each installment', 'darven-epi' ),
			)
		);
		$this->registerText(
			'darven_epi_minimum_installments_value',
			__( 'Minimum price to the discount to be applied', 'darven-epi' ),
			'darven_epi_installments_settings_section',
			__( 'If there is not a minimum price, you may leave this field blank.', 'darven-epi' )
		);
		$this->registerText( 'darven_epi_installments_prefix', __( 'Prefix', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Text before the installment.', 'darven-epi' ) );
		$this->registerText( 'darven_epi_installments_suffix', __( 'Suffix', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Text after the installment.', 'darven-epi' ) );
		$this->registerText( 'darven_epi_popup_text', __( 'Popup Text', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Only applied if the exhibition mode has the popup.', 'darven-epi' ) );
		$this->registerText( 'darven_epi_max_installments', __( 'Maximum of Installments', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Maximum installments possible. With or without interest fee.', 'darven-epi' ) );
		$this->registerText(
			'darven_epi_minimum_installments_value',
			__( 'Minimum price necessary to apply the installments', 'darven-epi' ),
			'darven_epi_installments_settings_section',
			__( 'If there is not a minimum price, you may leave this field blank.', 'darven-epi' )
		);
		$this->registerText( 'darven_epi_installments_interest_fee_from', __( 'Installments from', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'At which installment should the interest fee start being applied.', 'darven-epi' ) );
		$this->registerText( 'darven_epi_installments_interest_fee_first_install', __( 'Interest fee in the first install (%)', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Interest fee applied in the first installment (with i.f).', 'darven-epi' ) );
		$this->registerText( 'darven_epi_installments_interest_fee', __( 'Incremental interest fee (%)', 'darven-epi' ), 'darven_epi_installments_settings_section', __( 'Interest fee incrementally applied on each install.', 'darven-epi' ) );

		$this->registerCheckbox(
			'darven_epi_installments_interest_fee_is_table_enabled',
			__( 'Customize the interest fees', 'darven-epi' ),
			'darven_epi_advanced_installments_settings_section',
			__( 'If enabled, only the customized interest fee values will be considered. Be aware!', 'darven-epi' )
		);
		$this->registerSettingsField(
			'darven_epi_installments_interest_fee_table',
			__( 'Customize the interest fees', 'darven-epi' ),
			'darven_epi_advanced_installments_settings_section',
			array( $this, 'renderInterestFeeTable' )
		);
	}

	public function renderInterestFeeTable(): void {
		$field = 'darven_epi_installments_interest_fee_table';
		printf(
			'<input class="" type="text" name="darven_epi_option_general[%1$s]" id="%1$s" value="%2$s"><p class="description">%3$s</p>',
			esc_attr( $field ),
			esc_attr( $this->optionValue( $field ) ),
			esc_html__(
				"To personalize the interest fees, enter the percentage of interest to be charged for each installment that has a fee. Separate the values with a vertical bar (|), and use the format 'fee,fee,fee', where each fee corresponds to an installment with an interest fee. For example: '8,25|9,50|10,12'. Please note that you should only include installments that have an interest fee.",
				'darven-epi'
			)
		);
		?>

                <div id="installments_auxiliar_table_div" style="display: none">
                    <p>
                    </p>

                </div>
                <a id="customized_values_button" href="javascript:void(0)" style="display: none">OK</a>
                <br>
                <script type="text/javascript">
                    let customized_values = <?php echo wp_json_encode( $this->optionValue( 'darven_epi_installments_interest_fee_table' ) ); ?>;
                    let max_install = <?php echo wp_json_encode( $this->optionValue( 'darven_epi_max_installments' ) ); ?>;
                    let first_install = <?php echo wp_json_encode( $this->optionValue( 'darven_epi_installments_interest_fee_from' ) ); ?>;
                </script>
		<?php
	}

	private function registerCheckbox( string $field, string $label, string $section, string $description = '' ): void {
		$this->registerSettingsField(
			$field,
			$label,
			$section,
			function () use ( $field, $description ): void {
				$checked = $field === ( $this->options[ $field ] ?? '' ) ? 'checked' : '';
				printf(
					'<input type="checkbox" name="darven_epi_option_general[%1$s]" id="%1$s" value="%1$s" %2$s>%3$s',
					esc_attr( $field ),
					esc_attr( $checked ),
					'' === $description ? '' : '<p class="description">' . esc_html( $description ) . '</p>'
				);
			}
		);
	}

	/**
	 * @param array<string,string> $items
	 */
	private function registerSelect( string $field, string $label, string $section, array $items ): void {
		$this->registerSettingsField(
			$field,
			$label,
			$section,
			function () use ( $field, $items ): void {
				printf(
					'<label for="%1$s"></label><select name="darven_epi_option_general[%1$s]" id="%1$s">',
					esc_attr( $field )
				);
				foreach ( $items as $value => $item_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $value ),
						selected( $this->options[ $field ] ?? null, $value, false ),
						esc_html( $item_label )
					);
				}
				echo '</select>';
			}
		);
	}

	private function registerText( string $field, string $label, string $section, string $description = '' ): void {
		$this->registerSettingsField(
			$field,
			$label,
			$section,
			function () use ( $field, $description ): void {
				printf(
					'<input class="regular-text" type="text" name="darven_epi_option_general[%1$s]" id="%1$s" value="%2$s"><p class="description">%3$s</p>',
					esc_attr( $field ),
					esc_attr( $this->optionValue( $field ) ),
					'' === $description ? '' : esc_html( $description )
				);
			}
		);
	}

	private function registerSettingsField( string $field, string $label, string $section, callable $callback ): void {
		add_settings_field( $field, $label, $callback, 'darven-epi-admin', $section );
	}

	private function optionValue( string $field ): string {
		return isset( $this->options[ $field ] ) ? (string) $this->options[ $field ] : '';
	}
}
