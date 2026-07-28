# YITH Automatic Compatibility Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Detect and use a compatible YITH dynamic price automatically on new Darven installations while retaining each existing installation's saved compatibility behaviour.

**Architecture:** A small `YithDynamicPricingMode` policy class owns the two valid modes and legacy inference. `SettingsRepository` exposes the effective mode without writing data during reads. The existing Settings API field saves the explicit mode and the old checkbox representation, while `ProductPriceResolver` calls YITH only in Automatic mode and safely falls back to WooCommerce.

**Tech Stack:** PHP 7.4, WordPress Settings API, WooCommerce `WC_Product`, PHPUnit 9.6, PHP_CodeSniffer with WordPress Coding Standards, Laragon local QA.

## Global Constraints

- Runtime compatibility stays at PHP 7.4; do not use union types, constructor property promotion, match, attributes, or PHP 8-only functions.
- Preserve existing options, canonical/legacy dual-write behaviour, public hooks, and current public HTML/CSS classes.
- A read or storefront request must not create, migrate, or update an option.
- The existing `darven_epi_is_yith_dynamic_compatibility_enabled` legacy value remains readable and mirrors the `auto` mode for one compatibility release.
- The Compatibility Mode URL, Settings API group, and non-React admin screen remain in place.
- No new Composer dependency is permitted.
- Every implementation task starts red, turns green, and creates one local commit with a `Vault-Author: codex` trailer. Do not push.

---

## File Structure

| File | Responsibility |
| --- | --- |
| `src/Compatibility/YithDynamicPricingMode.php` | Defines valid modes, validates form input, maps the legacy checkbox, and resolves missing settings without persistence. |
| `src/Repositories/SettingsRepository.php` | Determines whether any Darven settings are persisted and exposes `getYithDynamicPricingMode()`. |
| `src/Admin/SettingsFields/CompatibilityFields.php` | Renders and saves the Automatic/Disabled choice through the existing Settings API flow. |
| `src/Services/ProductPriceResolver.php` | Uses YITH only for Automatic mode and falls back safely on unavailable, invalid, or throwing APIs. |
| `tests/SettingsRepositoryTest.php` | Covers the old-install/new-install mode-resolution matrix and read-only behaviour. |
| `tests/LegacySettingsAdapterTest.php` | Proves the prefixed explicit mode survives normalized legacy projection. |
| `tests/SecondarySettingsSanitizationTest.php` | Covers UI sanitization and the old-checkbox mirror values. |
| `tests/Services/ProductPriceResolverTest.php` | Covers Automatic, Disabled, unavailable, invalid, and throwing YITH paths. |
| `readme.txt` | Explains the automatic YITH behaviour to plugin users. |
| `docs/superpowers/plans/2026-07-23-laragon-woocommerce-manual-qa.md` | Records the real local YITH verification for the new mode model. |

### Task 1: Add the compatibility-mode policy and read-only resolution

**Files:**
- Create: `src/Compatibility/YithDynamicPricingMode.php`
- Modify: `src/Repositories/SettingsRepository.php`
- Test: `tests/SettingsRepositoryTest.php`
- Test: `tests/LegacySettingsAdapterTest.php`

**Interfaces:**
- Produces: `YithDynamicPricingMode::FIELD`, `YithDynamicPricingMode::AUTO`, `YithDynamicPricingMode::DISABLED`, `YithDynamicPricingMode::LEGACY_FIELD`.
- Produces: `YithDynamicPricingMode::sanitize( $value ): string` and `YithDynamicPricingMode::resolve( array $compatibility, bool $has_persisted_darven_settings ): string`.
- Produces: `SettingsRepository::getYithDynamicPricingMode(): string`.
- Consumes: the existing canonical `compatibility` section and the four legacy Darven option names.

- [ ] **Step 1: Write the failing mode-resolution tests**

Add tests that prove the four storage states and the adapter projection:

```php
public function test_defaults_to_automatic_without_any_persisted_darven_settings(): void {
	self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
	self::assertSame( array(), $GLOBALS['darven_epi_test_options'] );
}

public function test_keeps_an_existing_unchecked_installation_disabled(): void {
	$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
	self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
}

public function test_maps_the_existing_checked_legacy_value_to_automatic(): void {
	$GLOBALS['darven_epi_test_options'] = $this->getLegacyOptions();
	$GLOBALS['darven_epi_test_options']['darven_epi_option_compatibility'] = array(
		'darven_epi_is_yith_dynamic_compatibility_enabled' => 'darven_epi_is_yith_dynamic_compatibility_enabled',
	);
	self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
}

public function test_explicit_canonical_mode_overrides_legacy_inference(): void {
	$GLOBALS['darven_epi_test_options'][ SettingsRepository::OPTION_NAME ] = array(
		'schema_version' => 1,
		'general' => array(), 'positions' => array(), 'display' => array(),
		'compatibility' => array( 'darven_epi_yith_dynamic_pricing_mode' => 'disabled' ),
	);
	self::assertSame( 'disabled', $this->getRepository()->getYithDynamicPricingMode() );
}
```

Also assert `LegacySettingsAdapter::projectToLegacyOptions()` retains both `darven_epi_yith_dynamic_pricing_mode` and the old checkbox when supplied in the compatibility section.

- [ ] **Step 2: Run the targeted tests to verify they fail**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' vendor\bin\phpunit --filter 'SettingsRepositoryTest|LegacySettingsAdapterTest'
```

Expected: FAIL because `YithDynamicPricingMode` and `SettingsRepository::getYithDynamicPricingMode()` do not exist.

- [ ] **Step 3: Implement the policy and repository method**

Create the policy with only two accepted modes and a safe invalid-input default:

```php
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
```

In `SettingsRepository`, read the raw canonical option with a `null` default. A valid or invalid stored canonical document, or any stored legacy Darven option, counts as persisted configuration. Pass the normalized compatibility section and that boolean to `YithDynamicPricingMode::resolve()`. Do not call `update_option()` from the new method.

- [ ] **Step 4: Run the targeted tests to verify they pass**

Run the command from Step 2.

Expected: PASS; the new-install assertion leaves `$GLOBALS['darven_epi_test_options']` empty.

- [ ] **Step 5: Commit**

```powershell
git add src/Compatibility/YithDynamicPricingMode.php src/Repositories/SettingsRepository.php tests/SettingsRepositoryTest.php tests/LegacySettingsAdapterTest.php
git commit -m "feat: resolve automatic YITH mode" -m "Vault-Author: codex"
```

### Task 2: Replace the checkbox with an explicit Automatic/Disabled setting

**Files:**
- Modify: `src/Admin/SettingsFields/CompatibilityFields.php`
- Test: `tests/SecondarySettingsSanitizationTest.php`

**Interfaces:**
- Consumes: `YithDynamicPricingMode::{FIELD,AUTO,DISABLED,LEGACY_FIELD,sanitize}` and `SettingsRepository::getYithDynamicPricingMode()`.
- Produces: the existing `darven_epi_option_compatibility` form submission with an explicit mode and the legacy checkbox only for `auto`.

- [ ] **Step 1: Write the failing Settings API tests**

Replace the checkbox test with explicit mode tests:

```php
public function test_compatibility_mode_saves_auto_and_mirrors_the_legacy_checkbox(): void {
	$result = $this->get_subject( CompatibilityFields::class )->sanitize(
		array( 'darven_epi_yith_dynamic_pricing_mode' => 'auto' )
	);

	self::assertSame( 'auto', $result['darven_epi_yith_dynamic_pricing_mode'] );
	self::assertSame(
		'darven_epi_is_yith_dynamic_compatibility_enabled',
		$result['darven_epi_is_yith_dynamic_compatibility_enabled']
	);
}

public function test_compatibility_mode_saves_disabled_without_the_legacy_checkbox(): void {
	$result = $this->get_subject( CompatibilityFields::class )->sanitize(
		array( 'darven_epi_yith_dynamic_pricing_mode' => 'disabled' )
	);

	self::assertSame( 'disabled', $result['darven_epi_yith_dynamic_pricing_mode'] );
	self::assertArrayNotHasKey( 'darven_epi_is_yith_dynamic_compatibility_enabled', $result );
}
```

Capture `renderYithDynamicCompatibility()` output for an empty option store and assert it contains the select name, `Automatic (recommended)`, `Disabled`, and `value="auto" selected`.

- [ ] **Step 2: Run the targeted test to verify it fails**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' vendor\bin\phpunit --filter SecondarySettingsSanitizationTest
```

Expected: FAIL because the form still renders and sanitizes the checkbox key.

- [ ] **Step 3: Implement the select and sanitizer**

Change the settings-field id and label to `darven_epi_yith_dynamic_pricing_mode` and `YITH Dynamic Pricing`. Render a `<select>` whose current value comes from a repository instance:

```php
$mode = ( new SettingsRepository( new LegacySettingsAdapter() ) )->getYithDynamicPricingMode();

printf(
	'<select name="darven_epi_option_compatibility[%1$s]" id="%1$s"><option value="auto" %2$s>%3$s</option><option value="disabled" %4$s>%5$s</option></select><p class="description">%6$s</p>',
	esc_attr( YithDynamicPricingMode::FIELD ),
	YithDynamicPricingMode::AUTO === $mode ? 'selected' : '',
	esc_html__( 'Automatic (recommended)', 'darven-epi' ),
	YithDynamicPricingMode::DISABLED === $mode ? 'selected' : '',
	esc_html__( 'Disabled', 'darven-epi' ),
	esc_html__( 'Automatic mode uses a valid YITH price when available and otherwise uses WooCommerce pricing.', 'darven-epi' )
);
```

Sanitize only `YithDynamicPricingMode::FIELD`. Build the values array with the sanitized mode; append `YithDynamicPricingMode::LEGACY_FIELD => YithDynamicPricingMode::LEGACY_FIELD` only for `auto`; then return `LegacySettingsSync::save( 'compatibility', $sanitized_values )`. Missing and invalid input must become `disabled`.

- [ ] **Step 4: Run the targeted test to verify it passes**

Run the command from Step 2.

Expected: PASS; Automatic projects both values and Disabled projects only the explicit mode.

- [ ] **Step 5: Commit**

```powershell
git add src/Admin/SettingsFields/CompatibilityFields.php tests/SecondarySettingsSanitizationTest.php
git commit -m "feat: add automatic YITH setting" -m "Vault-Author: codex"
```

### Task 3: Safely consume YITH only in Automatic mode

**Files:**
- Modify: `src/Services/ProductPriceResolver.php`
- Modify: `tests/Services/ProductPriceResolverTest.php`

**Interfaces:**
- Consumes: `SettingsRepository::getYithDynamicPricingMode()` and `YithDynamicPricingMode::AUTO`.
- Produces: `ProductPriceResolver::getActivePrice( \WC_Product $product ): float` with unchanged WooCommerce and variable-product fallbacks.

- [ ] **Step 1: Write failing resolver tests**

Extend `DarvenEpiYithFrontendTestDouble` with `public static $call_count` and `public static $should_throw`; increment the counter in `get_dynamic_price()` and throw `RuntimeException` when requested. Add these assertions:

```php
public function test_uses_yith_automatically_without_saved_settings_on_a_clean_install(): void {
	$this->enableYithDoubleWithPrice( '65.50' );
	self::assertSame( 65.50, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
}

public function test_never_calls_yith_when_the_explicit_mode_is_disabled(): void {
	$this->enableYithDoubleWithPrice( '65.50' );
	$this->setCanonicalCompatibilityMode( 'disabled' );
	self::assertSame( 100.00, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
	self::assertSame( 0, YWDPD_Frontend::$call_count );
}

public function test_falls_back_when_yith_throws(): void {
	$this->enableYithDoubleWithPrice( '65.50' );
	YWDPD_Frontend::$should_throw = true;
	self::assertSame( 100.00, $this->getResolver()->getActivePrice( new WC_Product( '100.00' ) ) );
}
```

Retain an assertion that an invalid dynamic value and an unavailable YITH class fall back to WooCommerce.

- [ ] **Step 2: Run the resolver test to verify it fails**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' vendor\bin\phpunit --filter ProductPriceResolverTest
```

Expected: FAIL because the resolver still uses the old checkbox directly and calls YITH in Disabled mode.

- [ ] **Step 3: Implement safe YITH price retrieval**

Replace the raw compatibility-array check with the repository mode method. Isolate the YITH call in a private method that returns `null` when unavailable:

```php
private function getYithDynamicPrice( \WC_Product $product ) {
	if ( ! class_exists( 'YWDPD_Frontend' ) || ! is_callable( array( 'YWDPD_Frontend', 'get_instance' ) ) ) {
		return null;
	}

	try {
		$frontend = \YWDPD_Frontend::get_instance();
		if ( ! is_object( $frontend ) || ! is_callable( array( $frontend, 'get_dynamic_price' ) ) ) {
			return null;
		}

		$dynamic_price = $frontend->get_dynamic_price( $product->get_price(), $product, 1 );

		return is_numeric( $dynamic_price ) ? (float) $dynamic_price : null;
	} catch ( \Throwable $exception ) {
		return null;
	}
}
```

Only return this value from `getActivePrice()` when the mode is `auto` and the private method returns a non-null value. Preserve zero as a valid dynamic price and then run the existing variable/minimum and regular-price fallback logic unchanged.

- [ ] **Step 4: Run the resolver test to verify it passes**

Run the command from Step 2.

Expected: PASS; Disabled leaves the test-double call count at zero, while a throwing API falls back to the $100 WooCommerce price.

- [ ] **Step 5: Commit**

```powershell
git add src/Services/ProductPriceResolver.php tests/Services/ProductPriceResolverTest.php
git commit -m "feat: safely detect YITH dynamic prices" -m "Vault-Author: codex"
```

### Task 4: Document and verify the completed behaviour

**Files:**
- Modify: `readme.txt`
- Modify: `docs/superpowers/plans/2026-07-23-laragon-woocommerce-manual-qa.md`

**Interfaces:**
- Consumes: the complete Automatic/Disabled setting and the isolated Laragon YITH rule already created for QA.
- Produces: public documentation and reproducible evidence of both price-source outcomes.

- [ ] **Step 1: Update the user-facing compatibility description**

In `readme.txt`, replace the opt-in description with this Portuguese copy while retaining the warning about other bulk-discount plugins:

```text
O modo de compatibilidade com o YITH WooCommerce Dynamic Pricing and Discounts é automático em novas instalações: quando a integração estiver disponível, o plugin considera o preço dinâmico definido pelo YITH. Instalações anteriores que não tinham a compatibilidade ativada permanecem desativadas até uma escolha explícita. Quando o YITH não estiver disponível, o plugin usa o preço padrão do WooCommerce.
```

- [ ] **Step 2: Run the complete automated matrix**

Run:

```powershell
$php83 = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
$php85 = 'C:\laragon\bin\php\php-8.5.7-Win32-vs17-x64\php.exe'
$php74 = 'C:\laragon\tmp\php-7.4.33-Win32-vc15-x64\php.exe'
$php74Extensions = 'C:\laragon\tmp\php-7.4.33-Win32-vc15-x64\ext'

& $php85 vendor\bin\phpunit --configuration phpunit.xml.dist
& $php83 vendor\bin\phpunit --configuration phpunit.xml.dist
& $php74 -d "extension_dir=$php74Extensions" -d extension=php_mbstring.dll vendor\bin\phpunit --configuration phpunit.xml.dist
& $php83 vendor\bin\phpcs
git diff --check
```

Expected: every PHPUnit run exits 0, PHPCS reports no violations, and `git diff --check` prints nothing.

- [ ] **Step 3: Perform the real YITH smoke test in Laragon**

Use the existing active 50% global YITH rule for `Darven EPI QA Simple`:

1. Select `Automatic`, save, and confirm the product shows the YITH $50 price plus Darven cash `$45` and installments based on `$50`.
2. Select `Disabled`, save, and confirm the YITH product price remains $50 while Darven shows cash `$90` and installments based on the original $100.
3. Select `Automatic` again, save, and confirm the final state displays `$45`.
4. Record these three observations in `2026-07-23-laragon-woocommerce-manual-qa.md`, including that the final setting is Automatic.

- [ ] **Step 4: Commit**

```powershell
git add readme.txt docs/superpowers/plans/2026-07-23-laragon-woocommerce-manual-qa.md
git commit -m "docs: explain automatic YITH compatibility" -m "Vault-Author: codex"
```
