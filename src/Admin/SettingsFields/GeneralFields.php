<?php

namespace Darven\ExtraPriceInfo\Admin\SettingsFields;

use Darven\ExtraPriceInfo\Admin\LegacySettingsSync;

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
		if ( ! is_array( $input ) ) {
			return LegacySettingsSync::save( 'general', array() );
		}

		$sanitized_values = array();

		foreach ( $this->getCheckboxFields() as $field => $checked_value ) {
			if ( isset( $input[ $field ] ) && $checked_value === $this->sanitizePlainText( $input[ $field ] ) ) {
				$sanitized_values[ $field ] = $checked_value;
			}
		}

		foreach ( $this->getEnumFields() as $field => $settings ) {
			if ( array_key_exists( $field, $input ) ) {
				$value = $this->sanitizePlainText( $input[ $field ] );

				$sanitized_values[ $field ] = in_array( $value, $settings['allowed'], true )
					? $value
					: $settings['default'];
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

		return LegacySettingsSync::save( 'general', $sanitized_values );
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

	/**
	 * @return array<string,string>
	 */
	private function getCheckboxFields(): array {
		return array(
			'darven_epi_incash_is_enabled'                         => 'darven_epi_incash_is_enabled',
			'darven_epi_installments_is_enabled'                   => 'darven_epi_installments_is_enabled',
			'darven_epi_installments_interest_fee_is_table_enabled' => 'darven_epi_installments_interest_fee_is_table_enabled',
		);
	}

	/**
	 * @return array<string,array{allowed:array<int,string>,default:string}>
	 */
	private function getEnumFields(): array {
		return array(
			'darven_epi_type_of_discount' => array(
				'allowed' => array( 'percent', 'fixed' ),
				'default' => 'percent',
			),
			'darven_epi_mode_of_view'     => array(
				'allowed' => array( 'default', 'popup', 'nofee' ),
				'default' => 'default',
			),
		);
	}

	/**
	 * @return array<int,string>
	 */
	private function getDecimalFields(): array {
		return array(
			'darven_epi_minimum_installments_value',
			'darven_epi_installments_interest_fee',
			'darven_epi_installments_interest_fee_first_install',
			'darven_epi_minimum_incash_value',
			'darven_epi_value_of_incash_discount',
		);
	}

	/**
	 * @return array<string,int>
	 */
	private function getIntegerFields(): array {
		return array(
			'darven_epi_max_installments'                 => 1,
			'darven_epi_installments_interest_fee_from' => 0,
		);
	}

	/**
	 * @return array<int,string>
	 */
	private function getMarkupFields(): array {
		return array(
			'darven_epi_installments_prefix',
			'darven_epi_installments_suffix',
			'darven_epi_incash_suffix',
			'darven_epi_incash_prefix',
			'darven_epi_popup_text',
		);
	}

	/**
	 * @return array<string,array<string,array<mixed>>>
	 */
	private function getAllowedMarkupTags(): array {
		return array(
			'a'    => array( 'href' => array(), 'class' => array() ),
			'br'   => array(),
			'i'    => array(),
			'b'    => array(),
			'div'  => array( 'style' => array(), 'class' => array() ),
			'span' => array( 'style' => array(), 'class' => array() ),
			'p'    => array( 'style' => array(), 'class' => array() ),
			'em'   => array(),
		);
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
		$items            = explode( '|', $this->sanitizePlainText( $value ) );
		$sanitized_values = array();

		foreach ( $items as $item ) {
			$number = $this->parseDecimalValue( $item );
			if ( null === $number || $number < 0 ) {
				continue;
			}

			$sanitized_values[] = $this->formatNumberForOption( $number );
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
