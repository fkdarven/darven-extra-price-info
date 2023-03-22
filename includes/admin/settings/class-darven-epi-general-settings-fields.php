<?php

/*
 * Class responsible for registering and handling the fields from general tab
 *
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'Darven_Epi_General_Settings_Fields' ) ) {
	class Darven_Epi_General_Settings_Fields {

		private $darven_epi_options;

		public function __construct() {

			register_setting( 'darven_epi_option_group', 'darven_epi_option_' . 'general', array(
				$this,
				'darven_epi_sanitize'
			) );


			$this->darven_epi_options = get_option( 'darven_epi_option_' . 'general' );


			$this->darven_epi_general_settings();
		}

		public function darven_epi_general_settings(): void {

			add_settings_field( 'darven_epi_incash_is_enabled', __('Enable'), array(
					$this,
					'darven_epi_incash_is_enabled_callback'
				), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_type_of_discount', __('Type of Discount'), array(
					$this,
					'darven_epi_type_of_discount_callback'
				), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_value_of_incash_discount', __('Value of Discount'), array(
				$this,
				'darven_epi_value_of_incash_discount_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_minimum_incash_value', __('Minimum price to the discount to be applied'), array(
				$this,
				'darven_epi_minimum_incash_value_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_incash_prefix', __('Prefix'), array(
				$this,
				'darven_epi_incash_prefix_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_incash_suffix', __('Suffix'), array(
				$this,
				'darven_epi_incash_suffix_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );


			add_settings_field( 'darven_epi_installments_is_enabled', __('Enable'), array(
				$this,
				'darven_epi_installments_is_enabled_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_mode_of_view', // id
				__('Exhibition Mode'), // title
				array( $this, 'darven_epi_mode_of_view_callback' ), // callback
				'darven-epi-admin', // page
				'darven_epi_installments_settings_section' // section
			);

			add_settings_field( 'darven_epi_popup_text', __('Popup Text'), array(
					$this,
					'darven_epi_popup_text_callback'
				), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_max_installments', __('Maximum of Installments'), array(
				$this,
				'darven_epi_max_installments_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_minimum_installments_value', __('Minimum price necessary to apply the installments'), array(
				$this,
				'darven_epi_minimum_installments_value_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_prefix', __('Prefix'), array(
				$this,
				'darven_epi_installments_prefix_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_suffix', __('Suffix'), array(
				$this,
				'darven_epi_installments_suffix_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_interest_fee_from', __('Installments from'), array(
				$this,
				'darven_epi_installments_interest_fee_from_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );


			add_settings_field( 'darven_epi_installments_interest_fee_first_install', __('Interest fee in the first install (%)'), array(
				$this,
				'darven_epi_installments_interest_fee_first_install_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );


			add_settings_field( 'darven_epi_installments_interest_fee', __('Incremental interest fee (%) '), array(
				$this,
				'darven_epi_installments_interest_fee_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_interest_fee_is_table_enabled', __('Customize the interest fees'), array($this, 'darven_epi_installments_interest_fee_is_table_enabled_callback'),
				'darven-epi-admin', 'darven_epi_advanced_installments_settings_section' );



			add_settings_field( 'darven_epi_installments_interest_fee_table', __('Customized values'), array($this, 'darven_epi_installments_interest_fee_table_callback'),
             'darven-epi-admin', 'darven_epi_advanced_installments_settings_section' );


		}

		public function darven_epi_incash_is_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_incash_is_enabled]" id="darven_epi_incash_is_enabled" value="darven_epi_incash_is_enabled" %s>', ( isset( $this->darven_epi_options['darven_epi_incash_is_enabled'] ) && $this->darven_epi_options['darven_epi_incash_is_enabled'] === 'darven_epi_incash_is_enabled' ) ? 'checked' : '' );
		}

		public function darven_epi_type_of_discount_callback(): void {


			?> <label for="darven_epi_type_of_discount"></label><select
                    name="darven_epi_option_general[darven_epi_type_of_discount]" id="darven_epi_type_of_discount">
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_type_of_discount'] ) && $this->darven_epi_options['darven_epi_type_of_discount'] === 'percent' ) ? 'selected' : ''; ?>
                <option value="percent" <?php echo $selected; ?>> <?php echo esc_attr(__('Percent'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_type_of_discount'] ) && $this->darven_epi_options['darven_epi_type_of_discount'] === 'fixed' ) ? 'selected' : ''; ?>
                <option value="fixed" <?php echo $selected; ?>>  <?php echo esc_attr(__('Fixed'))?></option>
            </select> <?php
		}

		public function darven_epi_value_of_incash_discount_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_value_of_incash_discount]" id="darven_epi_value_of_incash_discount" value="%s"><p></p>', isset( $this->darven_epi_options['darven_epi_value_of_incash_discount'] ) ? esc_attr( $this->darven_epi_options['darven_epi_value_of_incash_discount'] ) : '' );
		}

		public function darven_epi_minimum_incash_value_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_minimum_incash_value]" id="darven_epi_minimum_incash_value" value="%s"><p class="woocommerce-help-tip">%s</p>', isset( $this->darven_epi_options['darven_epi_minimum_incash_value'] ) ? esc_attr( $this->darven_epi_options['darven_epi_minimum_incash_value'] ) : '', esc_attr(__( 'If there is not a minimum price, you may leave the field blank.') ) );
		}

		public function darven_epi_incash_prefix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_incash_prefix]" id="darven_epi_incash_prefix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_incash_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_incash_prefix'] ) : '', esc_attr( __('Text before the in cash price.') ) );
		}

		public function darven_epi_incash_suffix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_incash_suffix]" id="darven_epi_incash_suffix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_incash_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_incash_suffix'] ) : '', esc_attr( __('Text after the in cash price.') ) );
		}

		public function darven_epi_installments_is_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_installments_is_enabled]" id="darven_epi_installments_is_enabled" value="darven_epi_installments_is_enabled" %s>', ( isset( $this->darven_epi_options['darven_epi_installments_is_enabled'] ) && $this->darven_epi_options['darven_epi_installments_is_enabled'] === 'darven_epi_installments_is_enabled' ) ? 'checked' : '' );
		}

		public function darven_epi_mode_of_view_callback(): void {
			?> <select name="darven_epi_option_general[darven_epi_mode_of_view]" id="darven_epi_mode_of_view">
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'default' ) ? 'selected' : ''; ?>
                <option value="default" <?php echo $selected; ?>> <?php echo esc_attr( __('Maximum of installments'));?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'popup' ) ? 'selected' : ''; ?>
                <option value="popup" <?php echo $selected; ?>><?php echo esc_attr( __('Maximum of installments and a popup with each installment')); ?>
                </option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'nofee' ) ? 'selected' : ''; ?>
                <option value="nofee" <?php echo $selected; ?>> <?php echo esc_attr( __('Maximum of installments without interest fee and a popup with each installment')); ?>
                </option>
            </select> <?php
		}

		public function darven_epi_popup_text_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_popup_text]" id="darven_epi_popup_text" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_popup_text'] ) ? esc_attr( $this->darven_epi_options['darven_epi_popup_text'] ) : '', esc_attr( __('Only applied if the exhibition mode has the popup.') ) );
		}

		public function darven_epi_max_installments_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_max_installments]" id="darven_epi_max_installments" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_max_installments'] ) ? esc_attr( $this->darven_epi_options['darven_epi_max_installments'] ) : '', esc_attr( __('Maximum installments possible. With or without interest fee.') ) );
		}

		public function darven_epi_minimum_installments_value_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_minimum_installments_value]" id="darven_epi_minimum_installments_value" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_minimum_installments_value'] ) ? esc_attr( $this->darven_epi_options['darven_epi_minimum_installments_value'] ) : '', esc_attr( __('If there is not a minimum price, you may leave this field blank.') ) );
		}

		public function darven_epi_installments_prefix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_prefix]" id="darven_epi_installments_prefix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_prefix'] ) : '', esc_attr( __('Text before the installment.') ) );
		}

		public function darven_epi_installments_suffix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_suffix]" id="darven_epi_installments_suffix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_suffix'] ) : '', esc_attr( __('Text after the installment.') ) );
		}

		public function darven_epi_installments_interest_fee_first_install_callback(): void {

			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_first_install]" id="darven_epi_installments_interest_fee_first_install" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_first_install'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_first_install'] ) : '', esc_attr( __('Interest fee applied in the first installment (with i.f).') ) );
		}

		public function darven_epi_installments_interest_fee_callback(): void {

			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee]" id="darven_epi_installments_interest_fee" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee'] ) : '', esc_attr( __('Interest fee incrementally applied on each install.') ) );
		}

		public function darven_epi_installments_interest_fee_from_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_from]" id="darven_epi_installments_interest_fee_from" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_from'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_from'] ) : '', esc_attr(__('At which installment should the interest fee start being applied.')) );
		}
		public function darven_epi_installments_interest_fee_is_table_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_installments_interest_fee_is_table_enabled]" id="darven_epi_installments_interest_fee_is_table_enabled" value="darven_epi_installments_interest_fee_is_table_enabled" %s><p class="description">%s</p>', ( isset( $this->darven_epi_options['darven_epi_installments_interest_fee_is_table_enabled'] ) && $this->darven_epi_options['darven_epi_installments_interest_fee_is_table_enabled'] === 'darven_epi_installments_interest_fee_is_table_enabled' ) ? 'checked' : '', esc_attr(__('If enabled, only the customized interest fee values will be considered. Be aware!')) );
		}
		public function darven_epi_installments_interest_fee_table_callback(): void {

            printf( '<input class="" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_table]" id="darven_epi_installments_interest_fee_table" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_table'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_table'] ) : '', esc_attr( __("To personalize the interest fees, enter the percentage of interest to be charged for each installment that has a fee. Separate the values with a vertical bar (|), and use the format 'fee,fee,fee', where each fee corresponds to an installment with an interest fee. For example: '8,25|9,50|10,12'. Please note that you should only include installments that have an interest fee.") ) );
			?>
            <a href="javascript:void(0)" id="show_auxiliar_table">Mostrar Tabela Auxiliar</a>
            <div id="installments_auxiliar_table_div" style="display: none">
                <p>
                </p>

            </div>
            <a id="customized_values_button" href="javascript:void(0)" style="display: none">OK</a>
            <br>
            <script type="text/javascript">
                var customized_values = "<?php echo $this->darven_epi_options['darven_epi_installments_interest_fee_table']; ?>";
                var max_install = "<?php echo $this->darven_epi_options['darven_epi_max_installments']; ?>";
                var first_install = "<?php echo $this->darven_epi_options['darven_epi_installments_interest_fee_from']; ?>";
            </script>
			<?php
        }

		public function darven_epi_sanitize( $input ): array {
			$sanitary_values = array();

			if ( isset( $input['darven_epi_installments_interest_fee_table'] ) ) {
				$sanitary_values['darven_epi_installments_interest_fee_table'] = sanitize_text_field( $input['darven_epi_installments_interest_fee_table'] );
			}

			if ( isset( $input['darven_epi_installments_interest_fee_is_table_enabled'] ) ) {
				$sanitary_values['darven_epi_installments_interest_fee_is_table_enabled'] = sanitize_text_field( $input['darven_epi_installments_interest_fee_is_table_enabled'] );
			}
			if ( isset( $input['darven_epi_color_of_installments_install'] ) ) {
				$sanitary_values['darven_epi_color_of_installments_install'] = sanitize_text_field( $input['darven_epi_color_of_installments_install'] );
			}
			if ( isset( $input['darven_epi_popup_text'] ) ) {
				$sanitary_values['darven_epi_popup_text'] = sanitize_text_field( $input['darven_epi_popup_text'] );
			}
			if ( isset( $input['darven_epi_incash_is_enabled'] ) ) {
				$sanitary_values['darven_epi_incash_is_enabled'] = sanitize_text_field( $input['darven_epi_incash_is_enabled'] );
			}

			if ( isset( $input['darven_epi_type_of_discount'] ) ) {
				$sanitary_values['darven_epi_type_of_discount'] = sanitize_text_field( $input['darven_epi_type_of_discount'] );
			}

			if ( isset( $input['darven_epi_mode_of_view'] ) ) {
				$sanitary_values['darven_epi_mode_of_view'] = sanitize_text_field( $input['darven_epi_mode_of_view'] );
			}

			if ( isset( $input['darven_epi_value_of_incash_discount'] ) ) {
				$sanitary_values['darven_epi_value_of_incash_discount'] = sanitize_text_field( $input['darven_epi_value_of_incash_discount'] );
			}

			if ( isset( $input['darven_epi_minimum_incash_value'] ) ) {
				$sanitary_values['darven_epi_minimum_incash_value'] = sanitize_text_field( $input['darven_epi_minimum_incash_value'] );
			}

			if ( isset( $input['darven_epi_incash_prefix'] ) ) {
				$sanitary_values['darven_epi_incash_prefix'] = sanitize_text_field( $input['darven_epi_incash_prefix'] );
			}

			if ( isset( $input['darven_epi_incash_suffix'] ) ) {
				$sanitary_values['darven_epi_incash_suffix'] = sanitize_text_field( $input['darven_epi_incash_suffix'] );
			}

			if ( isset( $input['darven_epi_installments_is_enabled'] ) ) {
				$sanitary_values['darven_epi_installments_is_enabled'] = $input['darven_epi_installments_is_enabled'];
			}

			if ( isset( $input['darven_epi_max_installments'] ) ) {
				$sanitary_values['darven_epi_max_installments'] = sanitize_text_field( $input['darven_epi_max_installments'] );
			}

			if ( isset( $input['darven_epi_minimum_installments_value'] ) ) {
				$sanitary_values['darven_epi_minimum_installments_value'] = sanitize_text_field( $input['darven_epi_minimum_installments_value'] );
			}

			if ( isset( $input['darven_epi_installments_prefix'] ) ) {
				$sanitary_values['darven_epi_installments_prefix'] = sanitize_text_field( $input['darven_epi_installments_prefix'] );
			}

			if ( isset( $input['darven_epi_installments_suffix'] ) ) {
				$sanitary_values['darven_epi_installments_suffix'] = sanitize_text_field( $input['darven_epi_installments_suffix'] );
			}

			if ( isset( $input['darven_epi_installments_interest_fee'] ) ) {
				$sanitary_values['darven_epi_installments_interest_fee'] = sanitize_text_field( $input['darven_epi_installments_interest_fee'] );
			}

			if ( isset( $input['darven_epi_installments_interest_fee_first_install'] ) ) {
				$sanitary_values['darven_epi_installments_interest_fee_first_install'] = sanitize_text_field( $input['darven_epi_installments_interest_fee_first_install'] );
			}

			if ( isset( $input['darven_epi_installments_interest_fee_from'] ) ) {
				$sanitary_values['darven_epi_installments_interest_fee_from'] = sanitize_text_field( $input['darven_epi_installments_interest_fee_from'] );
			}

			return $sanitary_values;
		}


	}


}