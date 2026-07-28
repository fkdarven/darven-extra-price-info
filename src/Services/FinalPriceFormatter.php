<?php

namespace Darven\ExtraPriceInfo\Services;

use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;

final class FinalPriceFormatter {
	/**
	 * @var CashPriceFormatter
	 */
	private $cash_price_formatter;

	/**
	 * @var InstallmentPriceFormatter
	 */
	private $installment_price_formatter;

	/**
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * @var ProductSettingsRepository
	 */
	private $product_settings_repository;

	public function __construct(
		CashPriceFormatter $cash_price_formatter,
		InstallmentPriceFormatter $installment_price_formatter,
		SettingsRepository $settings_repository,
		ProductSettingsRepository $product_settings_repository
	) {
		$this->cash_price_formatter        = $cash_price_formatter;
		$this->installment_price_formatter = $installment_price_formatter;
		$this->settings_repository         = $settings_repository;
		$this->product_settings_repository = $product_settings_repository;
	}

	public function filter( string $price_html, $product ): string {
		if ( is_admin() || is_checkout() || is_cart() ) {
			return $price_html;
		}

		if ( ! $product instanceof \WC_Product ) {
			return $price_html;
		}

		$product_settings       = $this->product_settings_repository->getSettings( $product );
		$cash_statement         = '';
		$installment_statement  = '';

		if ( empty( $product_settings['disable_incash'] ) ) {
			$cash_statement = $this->cash_price_formatter->format( $product );
		}

		if ( empty( $product_settings['disable_installments'] ) ) {
			$installment_statement = $this->installment_price_formatter->format( $product );
		}

		return $this->order( $price_html, $cash_statement, $installment_statement );
	}

	public function order( string $price_html, string $cash_statement, string $installment_statement ): string {
		$positions = $this->settings_repository->getSection( 'positions' );

		if ( is_product() ) {
			$order = $positions['darven_epi_single_product_position'] ?? null;
		} elseif ( is_product_category() ) {
			$order = $positions['darven_epi_catalog_product_position'] ?? null;
		} else {
			$order = $positions['darven_epi_others_product_position'] ?? null;
		}

		switch ( $order ) {
			case 'second':
				return $price_html . $installment_statement . $cash_statement;
			case 'third':
				return $cash_statement . $price_html . $installment_statement;
			case 'fourth':
				return $cash_statement . $installment_statement . $price_html;
			case 'fifth':
				return $installment_statement . $price_html . $cash_statement;
			case 'sixth':
				return $installment_statement . $cash_statement . $price_html;
			default:
				return $price_html . $cash_statement . $installment_statement;
		}
	}
}
