<?php

if(!class_exists('Darven_Epi_Positions_Fields')){
	class Darven_Epi_Positions_Fields{
		private $darven_epi_options;
		public function __construct() {
			register_setting('darven_epi_option_group',
			'darven_epi_option_positions',
			array($this, 'darven_epi_sanitize'));
			$this->darven_epi_options = get_option('darven_epi_option_positions');
			$this->darven_epi_positions_settings();
		}

		private function darven_epi_positions_settings(): void{
            printf(__('Position in single product page', 'darven-epi'));
			add_settings_field('darven_epi_single_product_position',
			__('Position in single product page ', 'darven-epi'),
			array($this, 'darven_epi_single_product_position_callback'),
			'darven-epi-admin',
			'darven_epi_incash_settings_section');
		}


		public function darven_epi_single_product_position_callback(): void {


			?> <label for="darven_epi_single_product_position"></label><select onchange="updateDropdowns(this)"
                name="darven_epi_option_general[darven_epi_single_product_position]" id="darven_epi_single_product_position">
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'first' ) ? 'selected' : ''; ?>
				<option value="first" <?php echo $selected; ?> > <?php echo esc_attr(__('Preço Original, Preço á Vista e Preço Parcelado'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'second' ) ? 'selected' : ''; ?>
				<option value="second" <?php echo $selected; ?>>  <?php echo esc_attr(__('Preço Original, Preço Parcelado e Preço á Vista'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'third' ) ? 'selected' : ''; ?>
                <option value="third" <?php echo $selected; ?>>  <?php echo esc_attr(__('Preço á Vista, Preço Original e Preço Parcelado'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'third' ) ? 'selected' : ''; ?>
                <option value="third" <?php echo $selected; ?>>  <?php echo esc_attr(__('Preço á Vista, Preço Parcelado e Preço Original'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'third' ) ? 'selected' : ''; ?>
                <option value="third" <?php echo $selected; ?>>  <?php echo esc_attr(__('Preço Parcelado, Preço Original e Preço á Vista'))?></option>
				<?php $selected = ( isset( $this->darven_epi_options['darven_epi_single_product_position'] ) && $this->darven_epi_options['darven_epi_single_product_position'] === 'third' ) ? 'selected' : ''; ?>
                <option value="third" <?php echo $selected; ?>>  <?php echo esc_attr(__('Preço Parcelado, Preço á Vista e Preço Oirignal'))?></option>
			</select>



            <?php
		}
	}
}