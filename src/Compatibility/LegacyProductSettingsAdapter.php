<?php

namespace Darven\ExtraPriceInfo\Compatibility;

final class LegacyProductSettingsAdapter {
	public function fromLegacyMeta( $incash_value, $installments_value ): array {
		return array(
			'disable_incash'       => 'yes' === $incash_value,
			'disable_installments' => 'yes' === $installments_value,
		);
	}

	public function projectToLegacyMeta( array $settings ): array {
		return array(
			'_darven_epi_is_incash_enabled'      => true === ( $settings['disable_incash'] ?? false ) ? 'yes' : 'no',
			'_darven_epi_is_installment_enabled' => true === ( $settings['disable_installments'] ?? false ) ? 'yes' : 'no',
		);
	}
}
