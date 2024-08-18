<?php

namespace Darven\Epi\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractSettingsFields {
	public $darven_epi_options;
	abstract public function get_slug(): string;
	abstract public function get_settings_fields(): array;

	protected function get_section( string $section ): string {
		return DARVEN_EXTRA_PRICE_OPTIONS_BASE . "_{$section}_settings_" . $this->get_slug() . '_section';
	}
	public function __construct() {
		register_setting( 'darven_epi_option_group', 'darven_epi_option_' . $this->get_slug(), array() );
		$this->darven_epi_options = get_option( 'darven_epi_option_' . $this->get_slug() );
		$this->build_settings();
	}

	protected function build_settings(): void {
		$this->register_all_settings_fields();
	}

	protected function build_field( string $slug, string $name, string $type, string $instructions, array $extra_args ): array {
		return array( $slug, $name, $type, $extra_args, $instructions );
	}
	protected function register_settings_field( string $id, string $text, string $section, string $input_type = 'text', array $args = array(), $instructions = '' ): void {
		add_settings_field(
			$id,
			__( $text, DARVEN_EXTRA_PRICE_INFO_DOMAIN ),
			array(
				$this,
				'build_settings_field',
			),
			'darven-epi-admin',
			$section,
			array(
				'label_for'    => $id,
				'input_type'   => $input_type,
				'instructions' => $instructions,
				'options'      => $args,

			)
		);
	}

	public function build_settings_field( array $args ): void {
		$option_name  = $args[ 'label_for' ];
		$input_type   = $args[ 'input_type' ];
		$instructions = $args[ 'instructions' ] ?? null;
		$option       = $this->darven_epi_options[ $option_name ] ?? '';

		match ( $input_type ) {
			'checkbox'    => $this->render_checkbox_field( $option_name, ( isset( $this->darven_epi_options[ $option_name ] ) && ( $this->darven_epi_options[ $option_name ] === $option_name ) ) ? 'checked' : '' ),
			'colorpicker' => $this->render_text_field( $option_name, $option, 'colorpicker-input' ),
			'select'      => $this->render_select_field( $option_name, $args[ 'options' ] ?? array(), $option ),
			'int'         => $this->render_text_field( $option_name, $option, 'number' ),
			default       => $this->render_text_field( $option_name, $option ),
		};
		if ( '' !== $instructions ) {
			$this->render_instructions_field( $instructions ?? '' );
		}

	}

	private function render_text_field( $option_name, $option, $class = 'text' ): void {
		?>
		<label for="<?php echo esc_attr( $option_name ); ?>"></label>
		<input type="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $option_name ); ?>"
			name="darven_epi_option_<?php echo esc_attr( $this->get_slug() . '[' . $option_name . ']' ); ?>"
			value="<?php echo esc_attr( $option ); ?>" class="darven-epi-<?php echo esc_attr( $class ); ?>">
		<?php
	}

	private function render_checkbox_field( $option_name, $is_checked ): void {
		?>
		<label class="darven-switch" for="<?php echo esc_attr( $option_name ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $option_name ); ?>"
				name="darven_epi_option_<?php echo esc_attr( $this->get_slug() . '[' . $option_name . ']' ); ?>" <?php echo esc_attr( $is_checked ); ?> value="<?php echo esc_attr( $option_name ); ?>" class="darven-epi-checkbox-input">
			<span class="slider round"></span>
		</label>
		<?php
	}

	private function render_select_field( $option_name, $options, $selected_option ): void {
		?>
		<label for="<?php echo esc_attr( $option_name ); ?>">
			<select name="darven_epi_option_<?php echo esc_attr( $this->get_slug() . '[' . $option_name . ']' ); ?>"
				id="<?php echo esc_attr( $option_name ); ?>" class="darven-epi-select-input">
				<?php foreach ( $options as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $selected_option ); ?>>
						<?php echo esc_attr( $value ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	private function render_instructions_field( $instructions ): void {
		?>
		<p class="description">
			<span class="dashicons dashicons-info"></span>
			<span class="instruction-text"><?php echo $instructions; ?></span>
		</p>
		<?php
	}
	private function register_all_settings_fields(): void {
		$settings_fields = $this->get_settings_fields();

		foreach ( $settings_fields as $settings_group => $settings_data ) {
			foreach ( $settings_data[ 'fields' ] as $field_data ) {
				$this->register_settings_field( DARVEN_EXTRA_PRICE_OPTIONS_BASE . "_{$settings_group}_{$field_data[ 0 ]}", ucfirst( $field_data[ 1 ] ), $settings_data[ 'section' ], $field_data[ 2 ] ?? 'text', $field_data[ 3 ] ?? array(), $field_data[ 4 ] ?? '' );
			}
		}
	}

}
