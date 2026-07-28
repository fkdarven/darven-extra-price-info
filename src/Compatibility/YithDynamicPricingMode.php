<?php

namespace Darven\ExtraPriceInfo\Compatibility;

final class YithDynamicPricingMode {
	public const FIELD = 'darven_epi_yith_dynamic_pricing_mode';
	public const AUTO = 'auto';
	public const DISABLED = 'disabled';
	public const LEGACY_FIELD = 'darven_epi_is_yith_dynamic_compatibility_enabled';

	public static function sanitize( $value ): string {
		return self::AUTO === $value ? self::AUTO : self::DISABLED;
	}

	public static function resolve( array $compatibility, bool $has_persisted_darven_settings ): string {
		if ( isset( $compatibility[ self::FIELD ] ) ) {
			return self::sanitize( $compatibility[ self::FIELD ] );
		}

		if ( isset( $compatibility[ self::LEGACY_FIELD ] ) && self::LEGACY_FIELD === $compatibility[ self::LEGACY_FIELD ] ) {
			return self::AUTO;
		}

		return $has_persisted_darven_settings ? self::DISABLED : self::AUTO;
	}
}
