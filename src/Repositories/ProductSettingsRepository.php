<?php

namespace Darven\ExtraPriceInfo\Repositories;

use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;

final class ProductSettingsRepository {
	public const META_KEY = '_darven_epi_product_settings';

	private const LEGACY_INCASH_META_KEY = '_darven_epi_is_incash_enabled';
	private const LEGACY_INSTALLMENTS_META_KEY = '_darven_epi_is_installment_enabled';

	/**
	 * @var LegacyProductSettingsAdapter
	 */
	private $adapter;

	public function __construct( LegacyProductSettingsAdapter $adapter ) {
		$this->adapter = $adapter;
	}

	public function getSettings( \WC_Product $product ): array {
		$direct_settings = $this->getDirectSettings( $product );

		if ( null !== $direct_settings ) {
			return $direct_settings;
		}

		if ( $product->is_type( 'variation' ) ) {
			$parent_id = (int) $product->get_parent_id();
			$parent    = $parent_id > 0 ? wc_get_product( $parent_id ) : false;

			if ( $parent instanceof \WC_Product ) {
				$parent_settings = $this->getDirectSettings( $parent );

				if ( null !== $parent_settings ) {
					return $parent_settings;
				}
			}
		}

		return $this->adapter->fromLegacyMeta( '', '' );
	}

	private function getDirectSettings( \WC_Product $product ): ?array {
		$canonical_settings = $product->get_meta( self::META_KEY, true );

		if ( $this->isCanonicalSettings( $canonical_settings ) ) {
			return $this->normalizeSettings( $canonical_settings );
		}

		$legacy_incash       = $product->get_meta( self::LEGACY_INCASH_META_KEY, true );
		$legacy_installments = $product->get_meta( self::LEGACY_INSTALLMENTS_META_KEY, true );

		if ( '' === $legacy_incash && '' === $legacy_installments ) {
			return null;
		}

		return $this->adapter->fromLegacyMeta(
			$legacy_incash, $legacy_installments
		);
	}

	public function save( \WC_Product $product, array $settings, bool $persist = true ): bool {
		$current_settings = $this->getSettings( $product );
		$canonical_settings = array(
			'schema_version'       => 1,
			'disable_incash'       => array_key_exists( 'disable_incash', $settings )
				? true === $settings['disable_incash']
				: $current_settings['disable_incash'],
			'disable_installments' => array_key_exists( 'disable_installments', $settings )
				? true === $settings['disable_installments']
				: $current_settings['disable_installments'],
		);
		$legacy_meta = $this->adapter->projectToLegacyMeta( $canonical_settings );

		$product->update_meta_data( self::META_KEY, $canonical_settings );
		$product->update_meta_data( self::LEGACY_INCASH_META_KEY, $legacy_meta[ self::LEGACY_INCASH_META_KEY ] );
		$product->update_meta_data( self::LEGACY_INSTALLMENTS_META_KEY, $legacy_meta[ self::LEGACY_INSTALLMENTS_META_KEY ] );
		if ( $persist ) {
			$product->save();
		}

		return $canonical_settings === $product->get_meta( self::META_KEY, true )
			&& $legacy_meta[ self::LEGACY_INCASH_META_KEY ] === $product->get_meta( self::LEGACY_INCASH_META_KEY, true )
			&& $legacy_meta[ self::LEGACY_INSTALLMENTS_META_KEY ] === $product->get_meta( self::LEGACY_INSTALLMENTS_META_KEY, true );
	}

	private function isCanonicalSettings( $settings ): bool {
		return is_array( $settings )
			&& isset( $settings['disable_incash'], $settings['disable_installments'] )
			&& is_bool( $settings['disable_incash'] )
			&& is_bool( $settings['disable_installments'] );
	}

	private function normalizeSettings( array $settings ): array {
		return array(
			'schema_version'       => 1,
			'disable_incash'       => $settings['disable_incash'],
			'disable_installments' => $settings['disable_installments'],
		);
	}
}
