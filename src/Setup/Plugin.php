<?php

namespace Darven\ExtraPriceInfo\Setup;

use Darven\ExtraPriceInfo\Admin\Assets as AdminAssets;
use Darven\ExtraPriceInfo\Admin\ProductOptionsController;
use Darven\ExtraPriceInfo\Admin\ReactPage;
use Darven\ExtraPriceInfo\Admin\SettingsRestController;
use Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter;
use Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter;
use Darven\ExtraPriceInfo\Frontend\Assets as FrontendAssets;
use Darven\ExtraPriceInfo\Repositories\ProductSettingsRepository;
use Darven\ExtraPriceInfo\Repositories\SettingsRepository;
use Darven\ExtraPriceInfo\Services\CashPriceFormatter;
use Darven\ExtraPriceInfo\Services\FinalPriceFormatter;
use Darven\ExtraPriceInfo\Services\InstallmentPriceFormatter;
use Darven\ExtraPriceInfo\Services\PriceMarkupBuilder;
use Darven\ExtraPriceInfo\Services\ProductPriceResolver;

final class Plugin {
	public static function boot(): void {
		$settings_adapter  = new LegacySettingsAdapter();
		$settings          = new SettingsRepository( $settings_adapter );
		$product_settings  = new ProductSettingsRepository( new LegacyProductSettingsAdapter() );
		$price_resolver    = new ProductPriceResolver( $settings );
		$markup_builder    = new PriceMarkupBuilder();
		$cash_formatter    = new CashPriceFormatter( $settings, $price_resolver, $markup_builder );
		$installment_formatter = new InstallmentPriceFormatter( $settings, $price_resolver, $markup_builder );
		$final_formatter   = new FinalPriceFormatter(
			$cash_formatter,
			$installment_formatter,
			$settings,
			$product_settings
		);
		$settings_page     = new ReactPage();
		$settings_rest     = new SettingsRestController( $settings, $product_settings );
		$product_options   = new ProductOptionsController( $product_settings );
		$admin_assets      = new AdminAssets();
		$frontend_assets   = new FrontendAssets();
		$text_domain_loader = new TextDomainLoader();

		add_filter( 'woocommerce_get_price_html', array( $final_formatter, 'filter' ), 2000, 2 );
		add_action( 'wp_enqueue_scripts', array( $frontend_assets, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue' ) );
		add_action( 'plugins_loaded', array( $text_domain_loader, 'load' ) );

		$settings_page->register();
		add_action( 'rest_api_init', array( $settings_rest, 'register' ) );
		$product_options->register();
	}
}
