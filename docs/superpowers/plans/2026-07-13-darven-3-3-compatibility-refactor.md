# Darven 3.3 Compatibility Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (\`- [ ]\`) syntax for tracking.

**Goal:** Move the plugin to a Composer-autoloaded, namespaced PHP architecture with canonical settings storage while preserving all legacy data and public output.

**Architecture:** New code lives under src/ in the Darven\ExtraPriceInfo namespace. Repositories own reads and writes, Compatibility converts normalized data to and from legacy options/meta, Services own pricing and markup, and Setup is the only composition and hook-registration layer. The current Settings API screen remains in place and delegates persistence to the new repositories.

**Tech Stack:** PHP 7.4, Composer PSR-4, WordPress Settings API, WooCommerce WC_Product, PHPUnit 9.6, PHP_CodeSniffer with WordPress Coding Standards.

## Global Constraints

- Runtime compatibility stays at PHP 7.4; do not use union types, constructor property promotion, match, attributes, or PHP 8-only functions.
- Preserve darven-epi, every existing darven_epi_option_* option, _darven_epi_is_incash_enabled, _darven_epi_is_installment_enabled, all current public hooks, and current public HTML/CSS classes.
- Keep the current admin URL, tabs, fields, labels, and Settings API form. React and REST endpoints are outside this plan.
- The canonical global option is darven_epi_settings; canonical product meta is _darven_epi_product_settings.
- Reads never write or migrate existing data. An authorized save writes canonical data and mirrors it to legacy storage for one compatibility release.
- Legacy projection overlays only Darven-owned keys, retaining unknown keys in legacy option arrays.
- Preserve current simple-product, variable-product, variation, YITH, cash-discount, installment, and markup behavior.
- Every task starts red, turns green, runs the full suite, and creates one local commit with a Vault-Author: codex trailer. Do not push.
- Use C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe for PHPUnit and PHPCS. Run final PHPUnit and lint with C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe too.

## Verification Commands

Run from C:\Users\fkdar\Documents\Seox\darven-extra-price-info.

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpcs --standard=WordPress src tests
git diff --check
~~~

After an autoload change, run:

~~~powershell
composer dump-autoload --optimize
~~~

Before creating the WordPress.org upload artifact, run:

~~~powershell
composer install --no-dev --prefer-dist --optimize-autoloader
Test-Path vendor\autoload.php
~~~

The final command must print True. vendor/ is ignored by Git but must be present in the release artifact.

---

## File Map

| Path | Responsibility |
| --- | --- |
| composer.json | PSR-4 runtime autoload mapping. |
| darven-extra-price-info.php | Metadata, legacy constants, inline Composer loader guard, lifecycle registration, and Plugin::boot(). |
| src/Setup/Plugin.php | Composes repositories, services, controllers, assets, lifecycle, and WordPress hooks. |
| src/Setup/Lifecycle.php | Registers activation/deactivation hooks against the main plugin file. |
| src/Setup/TextDomainLoader.php | Loads the current darven-epi text domain. |
| src/Compatibility/LegacySettingsAdapter.php | Converts four legacy option arrays to/from canonical settings. |
| src/Compatibility/LegacyProductSettingsAdapter.php | Converts two legacy product meta flags to/from canonical product settings. |
| src/Repositories/SettingsRepository.php | Canonical-first settings reads and verified canonical/legacy writes. |
| src/Repositories/ProductSettingsRepository.php | Canonical-first product reads and verified canonical/legacy meta writes. |
| src/Admin/LegacySettingsSync.php | Temporary bridge from current Settings API sanitizers to the repository. |
| src/Admin/ProductOptionsController.php | Unchanged WooCommerce checkboxes and repository-backed saving. |
| src/Admin/SettingsPage.php | Existing submenu, tab routing, sections, and Settings API form setup. |
| src/Admin/SettingsFields/*.php | Namespaced general, positions, compatibility, and display field presenters. |
| src/Services/ProductPriceResolver.php | Resolves WooCommerce/YITH/variable active price. |
| src/Services/CashPriceFormatter.php | Calculates and renders the cash statement. |
| src/Services/InstallmentPriceFormatter.php | Calculates and renders installments. |
| src/Services/PriceMarkupBuilder.php | Produces existing prefixed div/span markup. |
| src/Services/FinalPriceFormatter.php | Applies overrides and orders original/cash/installment statements. |
| src/Admin/Assets.php | Enqueues current admin CSS/JS. |
| src/Frontend/Assets.php | Enqueues current public CSS/JS. |
| tests/SettingsRepositoryTest.php | Fallback, precedence, dual-write, unknown key, and failed mirror tests. |
| tests/ProductSettingsRepositoryTest.php | Canonical/legacy product-meta behavior. |
| tests/PluginBootstrapTest.php | Composer guard and exact hook registrations. |
| tests/Services/*Test.php | Namespaced calculation and markup regressions. |

## Shared Interfaces

~~~php
namespace Darven\ExtraPriceInfo\Repositories;

final class SettingsRepository {
    public const OPTION_NAME = 'darven_epi_settings';
    public const SYNC_STATE_OPTION = 'darven_epi_settings_sync_state';

    public function __construct( \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter $adapter );
    public function getSettings(): array;
    public function getSection( string $section ): array;
    public function saveSection( string $section, array $values ): bool;
}
~~~

The canonical settings document is:

~~~php
array(
    'schema_version' => 1,
    'general'        => array(),
    'positions'      => array(),
    'display'        => array(),
    'compatibility'  => array(),
)
~~~

~~~php
namespace Darven\ExtraPriceInfo\Repositories;

final class ProductSettingsRepository {
    public const META_KEY = '_darven_epi_product_settings';

    public function __construct( \Darven\ExtraPriceInfo\Compatibility\LegacyProductSettingsAdapter $adapter );
    public function get( \WC_Product $product ): array;
    public function save( \WC_Product $product, array $settings ): bool;
    public function isCashDisabled( \WC_Product $product ): bool;
    public function areInstallmentsDisabled( \WC_Product $product ): bool;
}
~~~

The canonical product value is:

~~~php
array(
    'disable_incash'       => false,
    'disable_installments' => false,
)
~~~

A legacy meta value of yes means disabled. The name is counterintuitive but compatibility requires it.

The setup and presentation constructors are fixed as follows:

~~~php
final class ProductOptionsController {
    public function __construct( ProductSettingsRepository $repository );
    public function register(): void;
    public function renderFields(): void;
    public function save( \WC_Product $product ): void;
}

final class CashPriceFormatter {
    public function __construct(
        SettingsRepository $settings,
        ProductPriceResolver $price_resolver,
        PriceMarkupBuilder $markup
    );
    public function format( \WC_Product $product ): string;
}

final class InstallmentPriceFormatter {
    public function __construct(
        SettingsRepository $settings,
        ProductPriceResolver $price_resolver,
        PriceMarkupBuilder $markup
    );
    public function format( \WC_Product $product ): string;
    public function getPriceTable( float $price ): array;
}

final class FinalPriceFormatter {
    public function __construct(
        SettingsRepository $settings,
        ProductSettingsRepository $product_settings,
        CashPriceFormatter $cash,
        InstallmentPriceFormatter $installments
    );
    public function filter( string $price_html, $product ): string;
}
~~~

---

### Task 1: Add Composer PSR-4 autoloading without changing behavior

**Files:**

- Modify: composer.json
- Modify: darven-extra-price-info.php
- Create: tests/PluginAutoloadTest.php
- Modify: tests/bootstrap.php

**Interfaces:**

- Produces: Composer mapping Darven\ExtraPriceInfo\ to src/.
- Consumes: no namespaced runtime service; the existing Darven_Epi boot remains active in this task.

- [x] **Step 1: Write the failing autoload contract test**

~~~php
public function test_declares_the_psr4_namespace_and_loads_the_runtime_autoloader(): void {
    $composer = json_decode( file_get_contents( DARVEN_EPI_DIR_PATH . 'composer.json' ), true );
    $plugin   = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );

    self::assertSame(
        'src/',
        $composer['autoload']['psr-4']['Darven\\ExtraPriceInfo\\']
    );
    self::assertStringContainsString( 'vendor/autoload.php', $plugin );
}
~~~

- [x] **Step 2: Run the test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/PluginAutoloadTest.php
~~~

Expected: no PSR-4 mapping exists.

- [x] **Step 3: Add the minimal runtime autoload implementation**

Add this top-level Composer section while retaining the existing dependencies and scripts:

~~~json
"autoload": {
  "psr-4": {
    "Darven\\ExtraPriceInfo\\": "src/"
  }
}
~~~

Add this inline guard in the main plugin file before loading any namespaced class:

~~~php
$darven_epi_autoload_file = DARVEN_EPI_DIR_PATH . 'vendor/autoload.php';

if ( ! is_readable( $darven_epi_autoload_file ) ) {
    add_action(
        'admin_notices',
        static function (): void {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'Darven Extra Price Info is missing its runtime dependencies.', 'darven-epi' )
                . '</p></div>';
        }
    );

    return;
}

require_once $darven_epi_autoload_file;
~~~

This must precede every namespaced class reference. In tests/bootstrap.php, require vendor/autoload.php after defining DARVEN_EPI_DIR_PATH.

- [x] **Step 4: Regenerate and verify**

~~~powershell
composer dump-autoload --optimize
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/PluginAutoloadTest.php
~~~

Expected: the test passes.

- [x] **Step 5: Run regression suite and commit**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
git diff --check
git add composer.json composer.lock darven-extra-price-info.php tests/bootstrap.php tests/PluginAutoloadTest.php
git commit -m "refactor: add Composer PSR-4 autoloading" -m "Vault-Author: codex"
~~~

Expected: the current suite passes; no runtime behavior moved.

### Task 2: Define canonical settings and a lossless legacy adapter

**Files:**

- Create: src/Compatibility/LegacySettingsAdapter.php
- Create: tests/LegacySettingsAdapterTest.php

**Interfaces:**

- Produces: LegacySettingsAdapter::fromLegacyOptions( array $legacy_options ): array.
- Produces: LegacySettingsAdapter::projectToLegacyOptions( array $settings, array $existing_options ): array.
- Consumes: darven_epi_option_general, darven_epi_option_positions, darven_epi_option_colorsandstyles, and darven_epi_option_compatibility.

- [x] **Step 1: Write a legacy option fixture and failing assertions**

Use this fixture:

~~~php
$legacy_options = array(
    'darven_epi_option_general' => array(
        'darven_epi_incash_is_enabled' => 'darven_epi_incash_is_enabled',
        'darven_epi_max_installments'  => '6',
        'third_party_general_key'       => 'retain',
    ),
    'darven_epi_option_positions' => array(
        'darven_epi_single_product_position' => 'third',
        'third_party_position_key'            => 'retain',
    ),
    'darven_epi_option_colorsandstyles' => array(
        'darven_epi_color_of_incash_price' => '#123456',
        'third_party_display_key'          => 'retain',
    ),
    'darven_epi_option_compatibility' => array(
        'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
        'third_party_compatibility_key'                    => 'retain',
    ),
);
~~~

Assert that fromLegacyOptions() creates schema_version 1 and four plugin sections, but does not copy third_party keys into canonical data. Assert projectToLegacyOptions() updates a known Darven value while retaining every third_party key in the projected legacy arrays.

- [x] **Step 2: Run the focused test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/LegacySettingsAdapterTest.php
~~~

Expected: LegacySettingsAdapter is not found.

- [x] **Step 3: Implement explicit section mapping**

~~~php
private const OPTION_BY_SECTION = array(
    'general'       => 'darven_epi_option_general',
    'positions'     => 'darven_epi_option_positions',
    'display'       => 'darven_epi_option_colorsandstyles',
    'compatibility' => 'darven_epi_option_compatibility',
);
~~~

fromLegacyOptions() returns all four sections when any option is absent or non-array. projectToLegacyOptions() starts from supplied existing option arrays, replaces only keys present in a canonical section, and returns all four named option arrays. It contains no WordPress function calls.

- [x] **Step 4: Run focused verification**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/LegacySettingsAdapterTest.php
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' -l src/Compatibility/LegacySettingsAdapter.php
~~~

Expected: the adapter test passes and PHP reports no syntax errors.

- [x] **Step 5: Commit**

~~~powershell
git add src/Compatibility/LegacySettingsAdapter.php tests/LegacySettingsAdapterTest.php
git commit -m "feat: add legacy settings adapter" -m "Vault-Author: codex"
~~~

### Task 3: Add canonical settings reads, verified dual-write, and pending sync state

**Files:**

- Create: src/Repositories/SettingsRepository.php
- Create: tests/SettingsRepositoryTest.php
- Modify: tests/bootstrap.php

**Interfaces:**

- Produces: SettingsRepository from Shared Interfaces.
- Consumes: LegacySettingsAdapter.
- Produces: darven_epi_settings only after saveSection(); reading alone must not create it.

- [x] **Step 1: Extend test doubles and write failing repository tests**

Add update_option(), delete_option(), and a failure list to tests/bootstrap.php:

~~~php
function update_option( $name, $value ): bool {
    if ( in_array( $name, $GLOBALS['darven_epi_test_failing_options'] ?? array(), true ) ) {
        return false;
    }

    $GLOBALS['darven_epi_test_options'][ $name ] = $value;

    return true;
}
~~~

Write these tests:

~~~php
public function test_reads_normalized_legacy_options_without_writing_a_migration(): void;
public function test_prefers_a_valid_canonical_document(): void;
public function test_saves_a_section_to_canonical_and_legacy_options(): void;
public function test_preserves_unknown_legacy_keys_when_projecting_a_save(): void;
public function test_records_pending_sync_when_a_legacy_mirror_cannot_be_verified(): void;
~~~

For the last test, fail darven_epi_option_positions, save positions with sixth, assert canonical stores sixth, and assert darven_epi_settings_sync_state contains positions.

- [x] **Step 2: Run the focused test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/SettingsRepositoryTest.php
~~~

Expected: SettingsRepository is not found.

- [x] **Step 3: Implement verified persistence**

~~~php
public function getSettings(): array {
    $canonical = get_option( self::OPTION_NAME, array() );

    if ( $this->isCanonicalDocument( $canonical ) ) {
        return $canonical;
    }

    return $this->adapter->fromLegacyOptions( $this->getLegacyOptions() );
}

public function saveSection( string $section, array $values ): bool {
    $settings                     = $this->getSettings();
    $settings['schema_version']   = 1;
    $settings[ $section ]         = $values;

    if ( ! $this->persistAndVerify( self::OPTION_NAME, $settings ) ) {
        return false;
    }

    return $this->persistLegacyOptions(
        $this->adapter->projectToLegacyOptions( $settings, $this->getLegacyOptions() ),
        $section
    );
}
~~~

persistAndVerify() calls update_option() and strictly compares get_option( $name, null ) to the intended value. persistLegacyOptions() verifies each projection. On mirror failure, write:

~~~php
array(
    'pending_sections' => array( $section ),
    'failed_options'   => array( 'darven_epi_option_positions' ),
)
~~~

to darven_epi_settings_sync_state. On complete sync delete that option. getSection() throws InvalidArgumentException for an unknown section.

- [x] **Step 4: Run repository and full regression tests**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/SettingsRepositoryTest.php
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
~~~

Expected: repository tests and all current tests pass.

- [x] **Step 5: Commit**

~~~powershell
git add src/Repositories/SettingsRepository.php tests/bootstrap.php tests/SettingsRepositoryTest.php
git commit -m "feat: add canonical settings repository" -m "Vault-Author: codex"
~~~

### Task 4: Mirror current Settings API saves into canonical settings

**Files:**

- Create: src/Admin/LegacySettingsSync.php
- Modify: includes/admin/settings/class-darven-epi-general-settings-fields.php
- Modify: includes/admin/settings/class-darven-epi-positions-settings-fields.php
- Modify: includes/admin/settings/class-darven-epi-compatibility-settings-fields.php
- Modify: includes/admin/settings/class-darven-epi-colorsandstyles-settings-fields.php
- Create: tests/LegacySettingsSyncTest.php

**Interfaces:**

- Produces: LegacySettingsSync::save( string $section, array $sanitized_values ): array.
- Consumes: the existing sanitizer output.
- Preserves: each register_setting() option name, field name, returned legacy array, URL, and form.

- [x] **Step 1: Write failing form-save tests**

For each section, call the existing sanitizer through the reflection pattern already used by tests and assert both its normal return value and canonical storage. The general assertion is:

~~~php
self::assertSame( 'popup', $sanitized['darven_epi_mode_of_view'] );
self::assertSame(
    'popup',
    $GLOBALS['darven_epi_test_options']['darven_epi_settings']['general']['darven_epi_mode_of_view']
);
~~~

Cover sections general, positions, compatibility, and display.

- [x] **Step 2: Run the bridge test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/LegacySettingsSyncTest.php
~~~

Expected: canonical storage is absent after sanitization.

- [x] **Step 3: Implement the bridge and call it after sanitization**

~~~php
namespace Darven\ExtraPriceInfo\Admin;

final class LegacySettingsSync {
    public static function save( string $section, array $sanitized_values ): array {
        $repository = new \Darven\ExtraPriceInfo\Repositories\SettingsRepository(
            new \Darven\ExtraPriceInfo\Compatibility\LegacySettingsAdapter()
        );

        if ( ! $repository->saveSection( $section, $sanitized_values ) ) {
            add_settings_error(
                'darven_epi_option_group',
                'darven_epi_legacy_sync_failed',
                __( 'Settings were saved, but compatibility synchronization needs another save attempt.', 'darven-epi' )
            );
        }

        return $sanitized_values;
    }
}
~~~

At the end of each existing darven_epi_sanitize() method, replace its return with LegacySettingsSync::save() using its section name. The Settings API remains responsible for the legacy write; the bridge creates/updates canonical data with the same sanitized values.

- [x] **Step 4: Run focused tests**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/LegacySettingsSyncTest.php tests/GeneralSettingsSanitizationTest.php tests/SecondarySettingsSanitizationTest.php
git diff --check
~~~

Expected: existing sanitizer assertions stay unchanged and every tested save creates canonical storage.

- [x] **Step 5: Commit**

~~~powershell
git add src/Admin/LegacySettingsSync.php includes/admin/settings tests/LegacySettingsSyncTest.php
git commit -m "feat: mirror legacy settings saves to canonical storage" -m "Vault-Author: codex"
~~~

### Task 5: Add canonical per-product settings with legacy-meta projection

**Files:**

- Create: src/Compatibility/LegacyProductSettingsAdapter.php
- Create: src/Repositories/ProductSettingsRepository.php
- Create: src/Admin/ProductOptionsController.php
- Create: tests/ProductSettingsRepositoryTest.php
- Modify: tests/bootstrap.php
- Modify: includes/functions/class-darven-epi-product-options.php
- Modify: tests/ProductOptionsTest.php

**Interfaces:**

- Produces: ProductSettingsRepository from Shared Interfaces.
- Produces: ProductOptionsController::register(): void, renderFields(): void, and save( \WC_Product $product ): void.
- Preserves: WooCommerce product-options and product-object-save hooks.

- [x] **Step 1: Write failing canonical product-setting tests**

Extend the WC_Product double with save() and seed/read support for _darven_epi_product_settings. Add tests for legacy fallback, canonical precedence, dual-write, and absent-checkbox behavior. The dual-write test must assert:

~~~php
self::assertSame(
    array( 'disable_incash' => true, 'disable_installments' => false ),
    $product->get_meta( '_darven_epi_product_settings', true )
);
self::assertSame( 'yes', $product->get_meta( '_darven_epi_is_incash_enabled', true ) );
self::assertSame( 'no', $product->get_meta( '_darven_epi_is_installment_enabled', true ) );
~~~

- [x] **Step 2: Run the test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/ProductSettingsRepositoryTest.php
~~~

Expected: adapter and repository classes are not found.

- [x] **Step 3: Implement adapter, repository, and secure controller**

The adapter maps legacy yes to true and every other legacy value to false. save() writes canonical meta first, then both legacy flags, and verifies each get_meta() result.

ProductOptionsController::save() retains this authorization/nonce order:

~~~php
if ( ! current_user_can( 'edit_post', $product->get_id() ) ) {
    return;
}
if ( ! isset( $_POST['woocommerce_meta_nonce'] ) ) {
    return;
}
$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) {
    return;
}
~~~

Build the payload with strict checkbox equality:

~~~php
$settings = array(
    'disable_incash'       => 'yes' === ( $_POST['_darven_epi_is_incash_enabled'] ?? '' ),
    'disable_installments' => 'yes' === ( $_POST['_darven_epi_is_installment_enabled'] ?? '' ),
);
~~~

Turn Darven_Epi_Product_Options into a thin delegating shim until Task 8; it must not register duplicate hooks.

- [x] **Step 4: Run product regressions**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/ProductSettingsRepositoryTest.php tests/ProductOptionsTest.php tests/FinalPriceTest.php
~~~

Expected: product save, nonce, capability, and legacy disabled-flag semantics pass.

- [x] **Step 5: Commit**

~~~powershell
git add src/Compatibility/LegacyProductSettingsAdapter.php src/Repositories/ProductSettingsRepository.php src/Admin/ProductOptionsController.php includes/functions/class-darven-epi-product-options.php tests/bootstrap.php tests/ProductSettingsRepositoryTest.php tests/ProductOptionsTest.php
git commit -m "feat: add canonical product settings" -m "Vault-Author: codex"
~~~

### Task 6: Move active-price and cash-price rules into namespaced services

**Files:**

- Create: src/Services/ProductPriceResolver.php
- Create: src/Services/PriceMarkupBuilder.php
- Create: src/Services/CashPriceFormatter.php
- Create: tests/Services/ProductPriceResolverTest.php
- Create: tests/Services/CashPriceFormatterTest.php
- Modify: includes/functions/class-darven-epi-product-price.php
- Modify: includes/functions/class-darven-epi-format-incash-price.php
- Modify: tests/ProductPriceTest.php

**Interfaces:**

- Produces: ProductPriceResolver::getActivePrice( \WC_Product $product ): float.
- Produces: CashPriceFormatter::format( \WC_Product $product ): string.
- Consumes: normalized general and compatibility sections through SettingsRepository.
- Preserves: cash markup classes and visible wc_price() output.

- [x] **Step 1: Write namespaced regression tests**

Copy simple, variable, invalid, unavailable-YITH, cash output, and prefixed-selector assertions into services tests. Add a missing-options regression:

~~~php
$repository = new SettingsRepository( new LegacySettingsAdapter() );
$formatter  = new CashPriceFormatter(
    $repository,
    new ProductPriceResolver( $repository ),
    new PriceMarkupBuilder()
);

self::assertSame( '', $formatter->format( new WC_Product( '100.00' ) ) );
~~~

- [x] **Step 2: Run the test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/Services/ProductPriceResolverTest.php tests/Services/CashPriceFormatterTest.php
~~~

Expected: the namespaced service classes are not found.

- [x] **Step 3: Implement injected services**

ProductPriceResolver receives SettingsRepository and preserves this decision order: valid YITH dynamic value, variable minimum display price, regular WooCommerce price.

PriceMarkupBuilder exposes:

~~~php
public function div( string $classes, string $content, string $type ): string;
public function span( string $classes, string $content, string $type ): string;
~~~

It must retain all current darven-epi-* classes and produce no repeated IDs.

CashPriceFormatter reads general settings once, returns empty unless the exact legacy checkbox value enables cash, preserves fixed/percent calculation, and uses wp_strip_all_tags( wc_price( $value ) ).

Turn legacy classes into no-side-effect shims that delegate to the new services. Do not instantiate a formatter at file scope.

- [x] **Step 4: Run focused regression tests**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/Services/ProductPriceResolverTest.php tests/Services/CashPriceFormatterTest.php tests/ProductPriceTest.php tests/FinalPriceTest.php tests/HtmlGeneratorTest.php
~~~

Expected: all price/markup tests pass and new services contain no get_option() call.

- [x] **Step 5: Commit**

~~~powershell
git add src/Services includes/functions/class-darven-epi-product-price.php includes/functions/class-darven-epi-format-incash-price.php tests/Services tests/ProductPriceTest.php
git commit -m "refactor: move cash pricing into services" -m "Vault-Author: codex"
~~~

### Task 7: Move installment, final-price, ordering, and style reads into services

**Files:**

- Create: src/Services/InstallmentPriceFormatter.php
- Create: src/Services/FinalPriceFormatter.php
- Create: src/Frontend/InlineStyles.php
- Create: tests/Services/InstallmentPriceFormatterTest.php
- Create: tests/Services/FinalPriceFormatterTest.php
- Modify: includes/functions/class-darven-epi-format-installments-price.php
- Modify: includes/functions/class-darven-epi-format-final-price.php
- Modify: public/partials/darven-epi-custom-css.php
- Modify: tests/InstallmentsPriceTest.php
- Modify: tests/FinalPriceTest.php

**Interfaces:**

- Produces: InstallmentPriceFormatter::format( \WC_Product $product ): string.
- Produces: InstallmentPriceFormatter::getPriceTable( float $price ): array.
- Produces: FinalPriceFormatter::filter( string $price_html, $product ): string.
- Consumes: CashPriceFormatter, ProductPriceResolver, SettingsRepository, and ProductSettingsRepository.

- [x] **Step 1: Write output compatibility tests**

Move existing default-installments, custom-table repeatability, popup accessibility, balanced rows, invalid-product, product-override, and markup assertions into namespaced tests. Add all six ordering cases:

~~~php
array(
    'first'  => $original . $cash . $installments,
    'second' => $original . $installments . $cash,
    'third'  => $cash . $original . $installments,
    'fourth' => $cash . $installments . $original,
    'fifth'  => $installments . $original . $cash,
    'sixth'  => $installments . $cash . $original,
)
~~~

Add an InlineStyles test seeded only with legacy display options; assert the current .darven-epi-incash-price selector and configured color are rendered.

- [x] **Step 2: Run the test to verify it fails**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/Services/InstallmentPriceFormatterTest.php tests/Services/FinalPriceFormatterTest.php
~~~

Expected: new formatter classes are not found.

- [x] **Step 3: Move the algorithms without changing markup**

InstallmentPriceFormatter reads normalized general settings once. Preserve the custom-table calculation exactly so two getPriceTable() calls do not mutate the starting installment.

FinalPriceFormatter::filter() keeps these guards:

~~~php
if ( is_admin() || is_checkout() || is_cart() ) {
    return $price_html;
}
if ( ! $product instanceof \WC_Product ) {
    return $price_html;
}
~~~

Use ProductSettingsRepository for overrides and SettingsRepository positions for ordering. Preserve every current markup class, button type, aria-expanded, and aria-hidden value.

InlineStyles reads display through SettingsRepository. The existing partial instantiates it and echoes its CSS; do not change the selector list or add an enqueue path.

- [x] **Step 4: Run presentation regressions**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/Services/InstallmentPriceFormatterTest.php tests/Services/FinalPriceFormatterTest.php tests/InstallmentsPriceTest.php tests/FinalPriceTest.php tests/HtmlGeneratorTest.php tests/FrontendAssetsTest.php
git diff --check
~~~

Expected: all current classes/accessibility attributes stay covered; new services have no direct option/meta reads.

- [x] **Step 5: Commit**

~~~powershell
git add src/Services/InstallmentPriceFormatter.php src/Services/FinalPriceFormatter.php src/Frontend/InlineStyles.php includes/functions/class-darven-epi-format-installments-price.php includes/functions/class-darven-epi-format-final-price.php public/partials/darven-epi-custom-css.php tests/Services tests/InstallmentsPriceTest.php tests/FinalPriceTest.php
git commit -m "refactor: move price presentation into services" -m "Vault-Author: codex"
~~~

### Task 8: Replace manual loader and global controllers with namespaced setup

**Files:**

- Create: src/Setup/Plugin.php
- Create: src/Setup/Lifecycle.php
- Create: src/Setup/TextDomainLoader.php
- Create: src/Admin/Assets.php
- Create: src/Frontend/Assets.php
- Create: src/Admin/SettingsPage.php
- Create: src/Admin/SettingsFields/GeneralFields.php
- Create: src/Admin/SettingsFields/PositionsFields.php
- Create: src/Admin/SettingsFields/CompatibilityFields.php
- Create: src/Admin/SettingsFields/DisplayFields.php
- Modify: darven-extra-price-info.php
- Modify: templates/admin/general-settings.php
- Modify: tests/HookRegistrationTest.php
- Create: tests/PluginBootstrapTest.php

**Interfaces:**

- Produces: Plugin::boot(): void as the only runtime composition entry point.
- Produces: direct WordPress registrations; new code does not use Darven_Epi_Loader.
- Consumes: all repositories and services from Tasks 3–7.

- [ ] **Step 1: Write failing hook and admin-route tests**

Record actions/filters in tests/bootstrap.php and assert Plugin::boot() registers:

~~~php
'plugins_loaded'
'admin_enqueue_scripts'
'wp_enqueue_scripts'
'woocommerce_get_price_html'
'woocommerce_product_options_general_product_data'
'woocommerce_admin_process_product_object'
~~~

Assert the price filter priority is 2000 with two accepted arguments. Assert the submenu slug remains darven-epi-admin with capability manage_options.

- [ ] **Step 2: Run bootstrap tests to verify they fail**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/PluginBootstrapTest.php tests/HookRegistrationTest.php
~~~

Expected: Darven\ExtraPriceInfo\Setup\Plugin is not found.

- [ ] **Step 3: Implement namespaced setup and current Settings API presenters**

Plugin::boot() constructs one LegacySettingsAdapter, one SettingsRepository, one ProductSettingsRepository, price services, FinalPriceFormatter, SettingsPage, ProductOptionsController, and asset classes. Register hooks directly:

~~~php
add_filter( 'woocommerce_get_price_html', array( $final_formatter, 'filter' ), 2000, 2 );
add_action( 'wp_enqueue_scripts', array( $frontend_assets, 'enqueue' ) );
add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue' ) );
add_action( 'plugins_loaded', array( $text_domain_loader, 'load' ) );
~~~

SettingsPage preserves the WooCommerce parent, darven-epi-admin slug, general/positions/compatibility tabs, darven_epi_option_group, and template rendering. Move the four field presenters into the listed namespaced paths without changing field names or sanitizer rules; each sanitizer calls LegacySettingsSync.

Admin/Frontend assets keep the existing CSS/JS filenames and current page conditions. TextDomainLoader keeps text domain darven-epi and i18n/languages/ path. Lifecycle preserves current activation/deactivation behavior.

Replace the main boot with:

~~~php
\Darven\ExtraPriceInfo\Setup\Lifecycle::register( __FILE__ );
\Darven\ExtraPriceInfo\Setup\Plugin::boot();
~~~

- [ ] **Step 4: Verify new boot and full suite**

~~~powershell
composer dump-autoload --optimize
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/PluginBootstrapTest.php tests/HookRegistrationTest.php tests/LifecycleTest.php tests/FrontendAssetsTest.php
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
~~~

Expected: each required hook registers once and the full suite has no failures/warnings.

- [ ] **Step 5: Commit**

~~~powershell
git add src/Setup src/Admin src/Frontend darven-extra-price-info.php templates/admin/general-settings.php tests/PluginBootstrapTest.php tests/HookRegistrationTest.php
git commit -m "refactor: boot plugin through namespaced setup" -m "Vault-Author: codex"
~~~

### Task 9: Remove superseded legacy PHP code and verify the release artifact

**Files:**

- Delete: includes/class-darven-epi.php
- Delete: includes/class-darven-epi-loader.php
- Delete: includes/class-darven-epi-i18.php
- Delete: includes/class-darven-epi-i18n.php
- Delete: includes/class-darven-epi-lifecycle.php
- Delete: includes/class-darven-activator.php
- Delete: includes/class-darven-deactivator.php
- Delete: includes/admin/settings/class-epi-general-settings.php
- Delete: includes/admin/settings/class-darven-epi-general-settings.php
- Delete: includes/admin/settings/class-darven-epi-general-settings-fields.php
- Delete: includes/admin/settings/class-darven-epi-general-settings-sanitizer.php
- Delete: includes/admin/settings/class-darven-epi-positions-settings-fields.php
- Delete: includes/admin/settings/class-darven-epi-compatibility-settings-fields.php
- Delete: includes/admin/settings/class-darven-epi-colorsandstyles-settings-fields.php
- Delete: includes/functions/class-darven-epi-product-price.php
- Delete: includes/functions/class-darven-epi-format-incash-price.php
- Delete: includes/functions/class-darven-epi-format-installments-price.php
- Delete: includes/functions/class-darven-epi-format-final-price.php
- Delete: includes/functions/class-darven-epi-product-options.php
- Delete: includes/utils/class-darven-epi-html-generator.php
- Delete: admin/class-darven-epi-admin.php
- Delete: public/class-darven-epi-public.php
- Modify: tests/bootstrap.php
- Modify: README.md
- Modify: readme.txt

**Interfaces:**

- Consumes: complete namespaced runtime from Task 8.
- Produces: no plugin runtime require chain outside Composer autoloading.
- Preserves: static CSS/JS files and their public selectors.

- [ ] **Step 1: Write a failing no-legacy-loader test**

~~~php
$plugin = file_get_contents( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' );

self::assertStringNotContainsString( 'includes/class-darven-epi.php', $plugin );
self::assertStringNotContainsString( 'new Darven_Epi()', $plugin );
self::assertStringContainsString( 'Plugin::boot()', $plugin );
~~~

Update every test requiring a deleted class to use the matching namespaced class through Composer.

- [ ] **Step 2: Run it to verify it fails before deletion**

~~~powershell
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit tests/PluginBootstrapTest.php
~~~

Expected: the test finds the old require/constructor.

- [ ] **Step 3: Remove only replaced code and update release instructions**

Before deleting, run:

~~~powershell
rg -n "Darven_Epi|class-darven-epi|class-epi-general-settings" darven-extra-price-info.php src tests
~~~

After replacement, remaining matches may only appear in test descriptions or migration-history text; no require, require_once, new expression, or class declaration may use a legacy runtime class.

Update README.md and readme.txt to document compatibility preservation and the required composer install --no-dev --prefer-dist --optimize-autoloader release-build command. Do not state that React is part of this release.

- [ ] **Step 4: Run the full verification matrix**

~~~powershell
composer dump-autoload --optimize
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' vendor/bin/phpunit --configuration phpunit.xml.dist
$phpFiles = rg --files -g '*.php' . | Where-Object { $_ -notmatch '(^|\\)vendor(\\|$)' }
foreach ( $file in $phpFiles ) { & 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' -l $file; if ( $LASTEXITCODE -ne 0 ) { exit $LASTEXITCODE } }
& 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe' vendor/bin/phpcs --standard=WordPress src tests
git diff --check
~~~

Expected: both PHPUnit runs exit 0, all PHP files lint, PHPCS reports no violations in new code/tests, and diff --check prints nothing.

- [ ] **Step 5: Perform manual WooCommerce checks**

1. Updating alone does not create darven_epi_settings or _darven_epi_product_settings.
2. Saving every current settings tab creates canonical data and retains legacy options.
3. Saving every product-checkbox combination creates canonical meta and both legacy flags.
4. A simple product, variable product, and variation retain original/cash/installment order and classes.
5. Cart, checkout, and wp-admin do not receive storefront markup.
6. YITH dynamic price is used only when its existing compatibility setting is enabled.
7. Replacing the refactored plugin with the prior release after a save displays the newly saved legacy configuration.

- [ ] **Step 6: Commit**

~~~powershell
git add -A
git commit -m "refactor: complete compatibility-first architecture" -m "Vault-Author: codex"
~~~

Do not push. Create a release or pull-request decision only after manual checks pass.

## Spec Coverage Review

- PHP 7.4: global constraints and Task 9 dual-PHP verification.
- Autoload/namespaces: Tasks 1, 6, 8, and 9.
- Legacy options/meta, canonical data, no automatic migration, dual-write, and rollback: Tasks 2–5.
- Existing admin without React: Tasks 4 and 8.
- WooCommerce, variations, YITH, and public markup/CSS: Tasks 5–7 and Task 9 manual checks.
- Sync error and retryable state: Tasks 3 and 4.
- Legacy removal only after replacement: Task 9.
