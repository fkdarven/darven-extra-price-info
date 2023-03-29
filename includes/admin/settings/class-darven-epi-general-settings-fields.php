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

		private function register_settings_field( $id, $text, $section ): void {
			add_settings_field(
				$id,
				__( $text, DARVEN_EPI_LANGUAGE_DOMAIN ),
				[ $this, $id . '_callback' ],
				DARVEN_EPI_ADMIN_PAGE,
				$section );

		}

		public function darven_epi_general_settings(): void {


			// $settings_fields = new Darven_Epi_Settings_Fields($this->darven_epi_options, 'darven_epi_option_general');
			//$settings_fields->register_text_field('darven_epi_value_of_incash_discount', 'Value of Discount', 'darven_epi_incash_settings_section', null);
            //In Cash Section
            $this->register_settings_field('darven_epi_incash_is_enabled', 'Enable', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_type_of_discount', 'Type of Discount', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_value_of_incash_discount', 'Value of Discount', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_minimum_incash_value', 'Minimum price to the discount to be applied', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_incash_prefix', 'Prefix', 'darven_epi_incash_settings_section');
			$this->register_settings_field('darven_epi_incash_suffix', 'Suffix', 'darven_epi_incash_settings_section');
            
            //Installments Section
			$this->register_settings_field('darven_epi_installments_is_enabled', 'Enable', 'darven_epi_installments_settings_section');

			$this->register_settings_field('darven_epi_minimum_installments_value', 'Minimum price to the discount to be applied', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_prefix', 'Prefix', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_suffix', 'Suffix', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_popup_text', 'Popup Text', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_max_installments', 'Maximum of Installments', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_minimum_installments_value', 'Minimum price necessary to apply the installments', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_interest_fee_from', 'Installments from', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_interest_fee_first_install', 'Interest fee in the first install (%)', 'darven_epi_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_interest_fee', 'Incremental interest fee (%)', 'darven_epi_installments_settings_section');


            //Advanced Installments Section
            $this->register_settings_field('darven_epi_installments_interest_fee_is_table_enabled', 'Customize the interest fees', 'darven_epi_advanced_installments_settings_section');
			$this->register_settings_field('darven_epi_installments_interest_fee_table', 'Customize the interest fees', 'darven_epi_advanced_installments_settings_section');


            /*
			add_settings_field( 'darven_epi_incash_is_enabled', __( 'Enable' ), array(
				$this,
				'darven_epi_incash_is_enabled_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_type_of_discount', __( 'Type of Discount', 'darven-epi' ), array(
				$this,
				'darven_epi_type_of_discount_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_value_of_incash_discount', __( 'Value of Discount' ), array(
				$this,
				'darven_epi_value_of_incash_discount_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_minimum_incash_value', __( 'Minimum price to the discount to be applied' ), array(
				$this,
				'darven_epi_minimum_incash_value_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_incash_prefix', __( 'Prefix' ), array(
				$this,
				'darven_epi_incash_prefix_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );

			add_settings_field( 'darven_epi_incash_suffix', __( 'Suffix' ), array(
				$this,
				'darven_epi_incash_suffix_callback'
			), 'darven-epi-admin', 'darven_epi_incash_settings_section' );


			add_settings_field( 'darven_epi_installments_is_enabled', __( 'Enable' ), array(
				$this,
				'darven_epi_installments_is_enabled_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_mode_of_view', // id
				__( 'Exhibition Mode' ), // title
				array( $this, 'darven_epi_mode_of_view_callback' ), // callback
				'darven-epi-admin', // page
				'darven_epi_installments_settings_section' // section
			);

			add_settings_field( 'darven_epi_popup_text', __( 'Popup Text' ), array(
				$this,
				'darven_epi_popup_text_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_max_installments', __( 'Maximum of Installments' ), array(
				$this,
				'darven_epi_max_installments_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_minimum_installments_value', __( 'Minimum price necessary to apply the installments' ), array(
				$this,
				'darven_epi_minimum_installments_value_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_prefix', __( 'Prefix' ), array(
				$this,
				'darven_epi_installments_prefix_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_suffix', __( 'Suffix' ), array(
				$this,
				'darven_epi_installments_suffix_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_interest_fee_from', __( 'Installments from' ), array(
				$this,
				'darven_epi_installments_interest_fee_from_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );


			add_settings_field( 'darven_epi_installments_interest_fee_first_install', __( 'Interest fee in the first install (%)' ), array(
				$this,
				'darven_epi_installments_interest_fee_first_install_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );


			add_settings_field( 'darven_epi_installments_interest_fee', __( 'Incremental interest fee (%) ' ), array(
				$this,
				'darven_epi_installments_interest_fee_callback'
			), 'darven-epi-admin', 'darven_epi_installments_settings_section' );

			add_settings_field( 'darven_epi_installments_interest_fee_is_table_enabled', __( 'Customize the interest fees' ), array(
				$this,
				'darven_epi_installments_interest_fee_is_table_enabled_callback'
			),
				'darven-epi-admin', 'darven_epi_advanced_installments_settings_section' );


			add_settings_field( 'darven_epi_installments_interest_fee_table', __( 'Customized values' ), array(
				$this,
				'darven_epi_installments_interest_fee_table_callback'
			),
				'darven-epi-admin', 'darven_epi_advanced_installments_settings_section' );

            */
		}

		public function darven_epi_incash_is_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_incash_is_enabled]" id="darven_epi_incash_is_enabled" value="darven_epi_incash_is_enabled" %s>', ( isset( $this->darven_epi_options['darven_epi_incash_is_enabled'] ) && $this->darven_epi_options['darven_epi_incash_is_enabled'] === 'darven_epi_incash_is_enabled' ) ? 'checked' : '' );
		}

		public function darven_epi_type_of_discount_callback(): void {


			?> <label for="darven_epi_type_of_discount"></label><select
                    name="darven_epi_option_general[darven_epi_type_of_discount]" id="darven_epi_type_of_discount">
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_type_of_discount'] ) && $this->darven_epi_options['darven_epi_type_of_discount'] === 'percent' ) ? 'selected' : ''; ?>
                <option value="percent" <?php echo $selected; ?>> <?php echo esc_attr( __( 'Percent' ) ) ?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_type_of_discount'] ) && $this->darven_epi_options['darven_epi_type_of_discount'] === 'fixed' ) ? 'selected' : ''; ?>
                <option value="fixed" <?php echo $selected; ?>>  <?php echo esc_attr( __( 'Fixed' ) ) ?></option>
            </select> <?php
		}

		public function darven_epi_value_of_incash_discount_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_value_of_incash_discount]" id="darven_epi_value_of_incash_discount" value="%s"><p></p>', isset( $this->darven_epi_options['darven_epi_value_of_incash_discount'] ) ? esc_attr( $this->darven_epi_options['darven_epi_value_of_incash_discount'] ) : '' );
		}

		public function darven_epi_minimum_incash_value_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_minimum_incash_value]" id="darven_epi_minimum_incash_value" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_minimum_incash_value'] ) ? esc_attr( $this->darven_epi_options['darven_epi_minimum_incash_value'] ) : '', esc_attr( __( 'If there is not a minimum price, you may leave the field blank.' ) ) );
		}

		public function darven_epi_incash_prefix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_incash_prefix]" id="darven_epi_incash_prefix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_incash_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_incash_prefix'] ) : '', esc_attr( __( 'Text before the in cash price.' ) ) );
		}

		public function darven_epi_incash_suffix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_incash_suffix]" id="darven_epi_incash_suffix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_incash_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_incash_suffix'] ) : '', esc_attr( __( 'Text after the in cash price.' ) ) );
		}

		public function darven_epi_installments_is_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_installments_is_enabled]" id="darven_epi_installments_is_enabled" value="darven_epi_installments_is_enabled" %s>', ( isset( $this->darven_epi_options['darven_epi_installments_is_enabled'] ) && $this->darven_epi_options['darven_epi_installments_is_enabled'] === 'darven_epi_installments_is_enabled' ) ? 'checked' : '' );
		}

		public function darven_epi_mode_of_view_callback(): void {
			?> <select name="darven_epi_option_general[darven_epi_mode_of_view]" id="darven_epi_mode_of_view">
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'default' ) ? 'selected' : ''; ?>
                <option value="default" <?php echo $selected; ?>> <?php echo esc_attr( __( 'Maximum of installments', DARVEN_EPI_LANGUAGE_DOMAIN ) ); ?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'popup' ) ? 'selected' : ''; ?>
                <option value="popup" <?php echo $selected; ?>><?php echo esc_attr( __( 'Maximum of installments and a popup with each installment' , DARVEN_EPI_LANGUAGE_DOMAIN) ); ?>
                </option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_mode_of_view'] ) && $this->darven_epi_options['darven_epi_mode_of_view'] === 'nofee' ) ? 'selected' : ''; ?>
                <option value="nofee" <?php echo $selected; ?>> <?php echo esc_attr( __( 'Maximum of installments without interest fee and a popup with each installment', DARVEN_EPI_LANGUAGE_DOMAIN) ); ?>
                </option>
            </select> <?php
		}

		public function darven_epi_popup_text_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_popup_text]" id="darven_epi_popup_text" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_popup_text'] ) ? esc_attr( $this->darven_epi_options['darven_epi_popup_text'] ) : '', esc_attr( __( 'Only applied if the exhibition mode has the popup.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_max_installments_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_max_installments]" id="darven_epi_max_installments" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_max_installments'] ) ? esc_attr( $this->darven_epi_options['darven_epi_max_installments'] ) : '', esc_attr( __( 'Maximum installments possible. With or without interest fee.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_minimum_installments_value_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_minimum_installments_value]" id="darven_epi_minimum_installments_value" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_minimum_installments_value'] ) ? esc_attr( $this->darven_epi_options['darven_epi_minimum_installments_value'] ) : '', esc_attr( __( 'If there is not a minimum price, you may leave this field blank.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_prefix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_prefix]" id="darven_epi_installments_prefix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_prefix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_prefix'] ) : '', esc_attr( __( 'Text before the installment.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_suffix_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_suffix]" id="darven_epi_installments_suffix" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_suffix'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_suffix'] ) : '', esc_attr( __( 'Text after the installment.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_interest_fee_first_install_callback(): void {

			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_first_install]" id="darven_epi_installments_interest_fee_first_install" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_first_install'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_first_install'] ) : '', esc_attr( __( 'Interest fee applied in the first installment (with i.f).', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_interest_fee_callback(): void {

			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee]" id="darven_epi_installments_interest_fee" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee'] ) : '', esc_attr( __( 'Interest fee incrementally applied on each install.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_interest_fee_from_callback(): void {
			printf( '<input class="regular-text" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_from]" id="darven_epi_installments_interest_fee_from" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_from'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_from'] ) : '', esc_attr( __( 'At which installment should the interest fee start being applied.', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_interest_fee_is_table_enabled_callback(): void {
			printf( '<input type="checkbox" name="darven_epi_option_general[darven_epi_installments_interest_fee_is_table_enabled]" id="darven_epi_installments_interest_fee_is_table_enabled" value="darven_epi_installments_interest_fee_is_table_enabled" %s><p class="description">%s</p>', ( isset( $this->darven_epi_options['darven_epi_installments_interest_fee_is_table_enabled'] ) && $this->darven_epi_options['darven_epi_installments_interest_fee_is_table_enabled'] === 'darven_epi_installments_interest_fee_is_table_enabled' ) ? 'checked' : '', esc_attr( __( 'If enabled, only the customized interest fee values will be considered. Be aware!', DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
		}

		public function darven_epi_installments_interest_fee_table_callback(): void {

			printf( '<input class="" type="text" name="darven_epi_option_general[darven_epi_installments_interest_fee_table]" id="darven_epi_installments_interest_fee_table" value="%s"><p class="description">%s</p>', isset( $this->darven_epi_options['darven_epi_installments_interest_fee_table'] ) ? esc_attr( $this->darven_epi_options['darven_epi_installments_interest_fee_table'] ) : '', esc_attr( __( "To personalize the interest fees, enter the percentage of interest to be charged for each installment that has a fee. Separate the values with a vertical bar (|), and use the format 'fee,fee,fee', where each fee corresponds to an installment with an interest fee. For example: '8,25|9,50|10,12'. Please note that you should only include installments that have an interest fee.",  DARVEN_EPI_LANGUAGE_DOMAIN ) ) );
			?>
            <a href="javascript:void(0)" id="show_auxiliar_table">Mostrar Tabela Auxiliar</a>
            <div id="installments_auxiliar_table_div" style="display: none">
                <p>
                </p>

            </div>
            <a id="customized_values_button" href="javascript:void(0)" style="display: none">OK</a>
            <br>
            <script type="text/javascript">
                let customized_values = "<?php echo $this->darven_epi_options['darven_epi_installments_interest_fee_table']; ?>";
                let max_install = "<?php echo $this->darven_epi_options['darven_epi_max_installments']; ?>";
                let first_install = "<?php echo $this->darven_epi_options['darven_epi_installments_interest_fee_from']; ?>";
            </script>
			<?php
		}

		public function darven_epi_sanitize( $input ): array {
			$sanitary_values = [
				'darven_epi_max_installments',
				'darven_epi_minimum_installments_value',
				'darven_epi_installments_prefix',
				'darven_epi_installments_suffix',
				'darven_epi_installments_interest_fee',
				'darven_epi_installments_interest_fee_first_install',
				'darven_epi_installments_interest_fee_from',
				'darven_epi_installments_is_enabled',
				'darven_epi_incash_suffix',
				'darven_epi_incash_prefix',
				'darven_epi_minimum_incash_value',
				'darven_epi_value_of_incash_discount',
				'darven_epi_installments_interest_fee_table',
				'darven_epi_installments_interest_fee_is_table_enabled',
				'darven_epi_color_of_installments_install',
				'darven_epi_popup_text',
				'darven_epi_incash_is_enabled',
				'darven_epi_type_of_discount',
				'darven_epi_mode_of_view'
			];
			$sanitized_values = [];
			foreach ( $sanitary_values as $sanitary_value ) {
				if ( isset( $input[ $sanitary_value ] ) ) {
					$sanitized_values[ $sanitary_value ] = sanitize_text_field( $input[ $sanitary_value ] );
				}
			}

			return $sanitized_values;

		}


	}


}