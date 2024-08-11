<?php
namespace Darven\Epi\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractEntity {

	public $darven_options;
	public $options_prefix;

	abstract function should_show();

	/**
	 * Populates the field based on the initiliazed options array.
	 * @param string $field
	 * @return string
	 */
	protected function populate_field( string $field ): string {
		return $this->darven_options[ "{$this->options_prefix}{$field}" ] ?? '';
	}

}
