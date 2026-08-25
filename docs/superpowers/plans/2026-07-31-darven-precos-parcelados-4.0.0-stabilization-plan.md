# Darven Preços Parcelados 4.0.0 Stabilization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stabilize every advertised 4.0.0 capability without removing features, while preserving 3.3.1 data and update compatibility.

**Architecture:** Keep `SettingsRepository` as the canonical configuration boundary, make public assets consume its sanitized document, and centralize effective product settings in `ProductSettingsRepository`. The React UI remains class-component based for WordPress 5.0 compatibility, with a server-rendered fallback for no-JavaScript administration. Translation generation and release assertions become part of the distributable artifact rather than source-only checks.

**Tech Stack:** PHP 8.0+, WordPress 5.0+, WooCommerce, Composer, PHPUnit 9, React class components through `@wordpress/element`, `@wordpress/i18n`, Jest, `@wordpress/scripts`, WP-CLI i18n tooling.

## Global Constraints

- Preserve slug/folder `darven-extra-price-info`, namespace `Darven\ExtraPriceInfo`, update path, legacy options `darven_epi_*`, and legacy product-meta mirrors.
- PHP minimum remains **8.0** and WordPress minimum remains **5.0**; do not introduce PHP 8.1 syntax, React hooks, or `@wordpress/components`.
- A 3.3.1 installation must not be written merely by opening the admin; a React save writes normalized v2 data and all legacy mirrors.
- New behavior is additive: do not cut Display, translation, product, popup, React, fallback, or WordPress.org capabilities.
- Use TDD for every behavior change: focused RED command, minimal GREEN implementation, focused GREEN command, then task-level suite.
- Build generated assets after source changes and commit their intended distributable outputs with their source/tests.
- Do not stage `wordpress-org-assets/screenshot-2-candidate.png`; do not push, tag, use SVN, or publish. Every local commit includes `Vault-Author: codex` in its body.
- PHP 8.0 and WordPress 5.0 runtime checks are release gates. If either environment is unavailable, report it to the maintainer as a blocked gate rather than treating another runtime as equivalent.

---

## File map

| Area | Responsibility |
| --- | --- |
| `src/Repositories/SettingsRepository.php` | Normalized settings, YITH-mode inference and legacy mirroring. |
| `src/Admin/SettingsRestController.php` | REST permission order and settings/product API contracts. |
| `src/Frontend/Assets.php`, `src/Frontend/InlineStyles.php`, `src/Setup/Plugin.php` | Runtime public styles and dependency wiring. |
| `src/Repositories/ProductSettingsRepository.php`, `src/Services/*Formatter.php`, `public/js/frontend.js` | Effective variable-product flags and modal identity/behavior. |
| `src/Admin/ReactPage.php`, `src/Admin/ProductOptionsController.php`, `src/Admin/Assets.php`, `admin/src/settings/app.js` | Admin no-JS fallback, React concurrency/keyboard behavior and RTL registration. |
| `src/Setup/TextDomainLoader.php`, `languages/`, `package.json`, `scripts/` | PHP/JS translation loading and generated catalogs. |
| `readme.txt`, `scripts/build-release.php`, `tests/ReleasePackageTest.php`, `docs/release/4.0.0-qa.md` | Accurate public material, release package rules and final evidence. |

---

### Task 1: Stabilize normalized compatibility and REST authorization

**Files:**
- Modify: `src/Repositories/SettingsRepository.php`
- Modify: `src/Admin/SettingsRestController.php`
- Modify: `tests/SettingsRepositoryTest.php`
- Modify: `tests/SettingsRestControllerTest.php`

**Interfaces:**
- Consumes: `YithDynamicPricingMode::FIELD`, `YithDynamicPricingMode::LEGACY_FIELD`, existing legacy option names and `SettingsSanitizer`.
- Produces: every readable normalized document has `compatibility[ YithDynamicPricingMode::FIELD ]` set to `auto` or `disabled`; `canEditProduct()` authorizes before product existence is exposed.

- [ ] **Step 1: Add failing persistence/permission tests.**

```php
public function test_unchanged_react_save_materializes_automatic_mode_for_a_clean_installation(): void {
	$document = $this->getRepository()->getNormalizedSettings();
	self::assertSame( 'auto', $document['compatibility'][ YithDynamicPricingMode::FIELD ] );
	self::assertTrue( $this->getRepository()->saveDocument( $document ) );
	self::assertSame( 'auto', $this->getRepository()->getYithDynamicPricingMode() );
}

public function test_product_permission_does_not_reveal_an_unknown_id_to_an_unauthorized_user(): void {
	$GLOBALS['darven_epi_test_current_user_can'] = false;
	$result = $this->getSubject()->canEditProduct( new WP_REST_Request( array( 'id' => 999 ) ) );
	self::assertSame( 'darven_epi_forbidden', $result->get_error_code() );
}
```

Also add legacy-checked (`auto`) and legacy-unchecked (`disabled`) GET → unchanged PUT → mode assertions. Keep each test’s before-save option snapshot so the pre-save no-write rule remains explicit.

- [ ] **Step 2: Run the focused tests and verify RED.**

Run: `composer test -- --filter='SettingsRepositoryTest|SettingsRestControllerTest'`

Expected: the clean document lacks the YITH field or the post-save resolver returns `disabled`; an unknown product is allowed through the permission callback before authorization.

- [ ] **Step 3: Materialize the effective YITH mode during normalization and authorize first.**

Make `normalizeDocument()` receive the effective legacy/persistence context it needs and emit the explicit canonical value instead of preserving an empty compatibility array:

```php
$mode = YithDynamicPricingMode::resolve( $compatibility, $has_persisted_darven_settings );
$normalized['compatibility'] = array(
	YithDynamicPricingMode::FIELD => $mode,
);
```

Retain explicit canonical values as the highest precedence. In `canEditProduct()`, execute `current_user_can( 'edit_post', $product_id )` before `wc_get_product()`, returning `forbiddenError()` on failure; let `getProduct()` produce the 404 only for an authorized caller.

- [ ] **Step 4: Run focused tests and verify GREEN.**

Run: `composer test -- --filter='SettingsRepositoryTest|SettingsRestControllerTest'`

Expected: all compatibility round trips retain their effective mode, legacy data is not written by GET, and both unknown/existing unauthorized product IDs return the same forbidden result.

- [ ] **Step 5: Run the related regression suite.**

Run: `composer test -- --filter='SettingsRepositoryTest|SettingsRestControllerTest|YithDynamicPricingModeTest|LegacySettingsAdapterTest'`

Expected: exit 0.

- [ ] **Step 6: Commit the task.**

```text
fix: preserve YITH mode across settings saves
```

### Task 2: Make Display settings and RTL admin assets effective

**Files:**
- Modify: `src/Frontend/Assets.php`
- Modify: `src/Frontend/InlineStyles.php`
- Modify: `src/Setup/Plugin.php`
- Modify: `src/Admin/Assets.php`
- Create: `tests/FrontendAssetsTest.php`
- Modify: `tests/Services/FinalPriceFormatterTest.php`
- Modify: `tests/Support/*` doubles only as required for `wp_add_inline_style()` and `wp_style_add_data()`.

**Interfaces:**
- Consumes: `SettingsRepository::getSection( 'display' )` and static public handle `darven-epi`.
- Produces: `InlineStyles::render(): string` returns sanitized CSS declarations without a `<style>` wrapper; `Frontend\Assets::enqueue()` attaches them through `wp_add_inline_style( 'darven-epi', $css )`.

- [ ] **Step 1: Add failing frontend asset tests.**

```php
public function test_enqueue_attaches_the_sanitized_display_css_to_the_public_handle(): void {
	$assets = new Assets( new InlineStyles( $this->settingsRepositoryWith( '#123456' ) ) );
	$assets->enqueue();
	self::assertSame( 'darven-epi', $GLOBALS['darven_epi_test_inline_styles'][0]['handle'] );
	self::assertStringContainsString( 'color: #123456 !important;', $GLOBALS['darven_epi_test_inline_styles'][0]['css'] );
}
```

Add assertions that each registered admin bundle calls `wp_style_add_data( $handle, 'rtl', 'replace' )`, and that the CSS string has no `<style` tag.

- [ ] **Step 2: Run focused tests and verify RED.**

Run: `composer test -- --filter='FrontendAssetsTest|AdminAssetsTest|FinalPriceFormatterTest'`

Expected: no inline style call is recorded and no RTL metadata exists.

- [ ] **Step 3: Wire the existing generator into the public asset lifecycle.**

Inject `InlineStyles` into `Frontend\Assets` from `Plugin::boot()`, enqueue static CSS first, then attach the generated CSS:

```php
wp_enqueue_style( 'darven-epi', $plugin_url . 'public/css/styles.css', array(), $version, 'all' );
wp_add_inline_style( 'darven-epi', $this->inline_styles->render() );
```

Change `InlineStyles::render()` to produce declarations only, retaining `esc_attr()` allowlisted values. Register `rtl => replace` for both admin bundle handles after `wp_enqueue_style()`.

- [ ] **Step 4: Run focused tests and verify GREEN.**

Run: `composer test -- --filter='FrontendAssetsTest|AdminAssetsTest|FinalPriceFormatterTest'`

Expected: static and inline styles use the same public handle; all 14 persisted display values remain sanitized; both admin handles advertise RTL replacement.

- [ ] **Step 5: Commit the task.**

```text
fix: apply display settings on the storefront
```

### Task 3: Preserve product flags for variations and isolate popup instances

**Files:**
- Modify: `src/Repositories/ProductSettingsRepository.php`
- Modify: `src/Services/InstallmentPriceFormatter.php`
- Modify: `public/js/frontend.js`
- Modify: `public/js/frontend.test.js`
- Modify: `tests/ProductSettingsRepositoryTest.php`
- Modify: `tests/Services/FinalPriceFormatterTest.php`
- Modify: `tests/Services/InstallmentPriceFormatterTest.php`
- Modify: WooCommerce/WordPress test doubles only as needed for parent-product lookup and `wp_generate_uuid4()`.

**Interfaces:**
- Consumes: direct canonical/legacy product meta, `WC_Product_Variation::get_parent_id()`, `wc_get_product()`, and installment statement wrappers.
- Produces: `ProductSettingsRepository::getSettings( WC_Product $product )` returns direct variation flags when present, otherwise parent flags for a variation; every popup receives a cross-request unique ID and JS resolves it in its own statement wrapper.

- [ ] **Step 1: Add failing parent/variation and popup-isolation tests.**

```php
public function test_variation_inherits_parent_flags_when_it_has_no_direct_meta(): void {
	$parent = $this->productWithSettings( true, false, 10 );
	$variation = new WC_Product( '90.00', 'variation', $parent, array(), 11 );
	self::assertSame( array( 'schema_version' => 1, 'disable_incash' => true, 'disable_installments' => false ), $this->repository->getSettings( $variation ) );
}
```

Add a direct-variation-meta test proving it wins over the parent. In `frontend.test.js`, render two statements whose dialogs deliberately share no global lookup path, click the second trigger, and assert only the second close button receives focus. In PHP, render popup markup from two independent formatter instances and assert distinct `aria-controls`/dialog IDs.

- [ ] **Step 2: Run focused tests and verify RED.**

Run: `composer test -- --filter='ProductSettingsRepositoryTest|FinalPriceFormatterTest|InstallmentPriceFormatterTest'`; `npm test -- public/js/frontend.test.js --runInBand`

Expected: variation falls back to all-false local defaults and popup IDs/global lookup make the isolation test fail.

- [ ] **Step 3: Implement effective parent fallback and local modal resolution.**

Split direct metadata lookup from effective lookup so absence can be distinguished from explicit false settings. For a variation without direct canonical or legacy meta, resolve its parent once; keep the variation object passed to cash/installment price calculation. Generate popup IDs with `wp_generate_uuid4()` (fallback to `uniqid( '', true )` only if unavailable).

Replace global lookup with wrapper-local lookup:

```js
const statement = trigger.closest( '.darven-epi-installments-price-statement' );
const modal = Array.prototype.find.call(
	statement ? statement.querySelectorAll( popupSelector ) : [],
	( candidate ) => candidate.id === modalId
);
```

Translate the hard-coded installment connector using an English msgid and a count placeholder, for example `sprintf( __( '%1$sx of', domain ), numberOfInstallments )`.

- [ ] **Step 4: Run focused tests and verify GREEN.**

Run: `composer test -- --filter='ProductSettingsRepositoryTest|FinalPriceFormatterTest|InstallmentPriceFormatterTest'`; `npm test -- public/js/frontend.test.js --runInBand`

Expected: parent/direct precedence, variation price usage, unique IDs and second-dialog focus all pass.

- [ ] **Step 5: Commit the task.**

```text
fix: stabilize variation settings and popup identity
```

### Task 4: Harden settings interaction, tabs and no-JavaScript admin fallback

**Files:**
- Modify: `admin/src/settings/app.js`
- Modify: `admin/src/settings/__tests__/app.test.js`
- Modify: `src/Admin/ReactPage.php`
- Modify: `src/Admin/ProductOptionsController.php`
- Modify: `src/Setup/Plugin.php`
- Create/Modify: `tests/ReactPageTest.php`
- Modify: `tests/ProductOptionsTest.php`

**Interfaces:**
- Consumes: `SettingsRepository::saveDocument()`, existing classic product POST field names, REST settings document and WordPress nonce/capability APIs.
- Produces: pending settings PUT disables all editable controls; React tabs implement roving focus; settings/product pages expose classic `<noscript>` controls that write through existing sanitizers/legacy mirrors.

- [ ] **Step 1: Add failing React interaction and PHP fallback tests.**

```js
it( 'disables editable controls while a settings save is pending', async () => {
	let resolveSave;
	renderApp( { loadSettings: jest.fn().mockResolvedValue( settingsDocument ), saveSettings: jest.fn( () => new Promise( ( resolve ) => { resolveSave = resolve; } ) ) } );
	await flushPromises();
	click( findButton( 'Save settings' ) );
	expect( container.querySelector( '[name="darven_epi_max_installments"]' ).disabled ).toBe( true );
	resolveSave( settingsDocument );
} );
```

Add ArrowRight/Home/End tab assertions. Add PHP render tests asserting a settings `<noscript>` form has nonce, current values and a submit path; assert the product panel fallback uses `_darven_epi_is_incash_enabled` and `_darven_epi_is_installment_enabled`, the exact fields already accepted by `save()`.

- [ ] **Step 2: Run focused tests and verify RED.**

Run: `npm test -- admin/src/settings/__tests__/app.test.js --runInBand`; `composer test -- --filter='ReactPageTest|ProductOptionsTest'`

Expected: fields remain editable while pending, keyboard does not move tabs, and no server-rendered controls exist.

- [ ] **Step 3: Implement pending-state and fallback contracts.**

Wrap settings sections and save action in a disabled fieldset while `isSaving`, with `aria-busy={ isSaving }`; ignore `updateField()` when saving. Give the selected tab `tabIndex={ 0 }`, every other tab `tabIndex={ -1 }`, and handle ArrowLeft/ArrowRight/Home/End by selecting and focusing the next tab.

Give `ReactPage` and `ProductOptionsController` the repository dependencies needed to render nonce-protected `<noscript>` forms. The settings handler must check `manage_options` and nonce before calling `saveDocument()` with a sanitized document; product fallback must continue passing `false` as the repository persistence argument because WooCommerce owns its classic save. Keep the React mount point unchanged for JS users.

- [ ] **Step 4: Run focused tests and verify GREEN.**

Run: `npm test -- admin/src/settings/__tests__/app.test.js --runInBand`; `composer test -- --filter='ReactPageTest|ProductOptionsTest|ProductOptionsInputTest'`

Expected: edits cannot occur during PUT, keyboard tab behavior follows ARIA, and fallback forms round-trip without bypassing capability/nonce checks.

- [ ] **Step 5: Build affected assets and commit.**

Run: `npm run build:settings`; `npm run build:product-options`

Expected: both bundles and asset hashes rebuild successfully.

```text
fix: harden settings interaction and admin fallback
```

### Task 5: Deliver complete PHP and JavaScript translations

**Files:**
- Modify: `src/Setup/TextDomainLoader.php`
- Modify: `src/Admin/Assets.php`
- Modify: `darven-extra-price-info.php`
- Modify: `src/Services/InstallmentPriceFormatter.php`
- Modify: `languages/darven-multiplos-precos-informativos.pot`
- Modify: `languages/darven-multiplos-precos-informativos-pt_BR.po`
- Modify/Create: `languages/*.mo`, `languages/*settings*.json`, `languages/*product-options*.json`
- Modify: `package.json`
- Create/Modify: `scripts/build-translations.php`
- Modify/Create: `tests/TextDomainTest.php`, `tests/AdminAssetsTest.php`, `tests/ReleasePackageTest.php`

**Interfaces:**
- Consumes: `DARVEN_EPI_DIR_PATH`, built handles `darven-precos-parcelados-settings` and `darven-precos-parcelados-product-options`, the actual `build/*/index.js` names, and English source strings.
- Produces: plugin-relative PHP loader path; a `pt_BR` MO; one valid aggregated JSON catalogue per current admin handle or exact current bundle hash that WordPress resolves through `wp_set_script_translations()`.

- [ ] **Step 1: Add failing loader/catalogue tests.**

```php
public function test_php_loader_uses_the_plugin_relative_languages_directory(): void {
	( new TextDomainLoader() )->load();
	self::assertSame(
		dirname( plugin_basename( DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' ) ) . '/languages/',
		$GLOBALS['darven_epi_test_textdomain_calls'][0]['path']
	);
}
```

Add package tests that resolve each `wp_set_script_translations()` handle to a JSON file in `languages/`, fail for source-path-only JSON naming, reject blank functional `msgstr` entries and reject retired `i18n/` catalogs.

- [ ] **Step 2: Run focused tests and verify RED.**

Run: `composer test -- --filter='TextDomainTest|AdminAssetsTest|ReleasePackageTest'`

Expected: current `'languages/'` path, missing handle/hash JSON files, blank PO strings and retained `i18n/` cause failures.

- [ ] **Step 3: Correct source, generation and runtime resolution.**

Load PHP translations with:

```php
load_plugin_textdomain(
	\DARVEN_EPI_LANGUAGE_DOMAIN,
	false,
	dirname( plugin_basename( \DARVEN_EPI_DIR_PATH . 'darven-extra-price-info.php' ) ) . '/languages/'
);
```

Keep `wp_set_script_translations()` calls for both built handles and generate their catalogues from final source strings. `scripts/build-translations.php` must: regenerate POT from source; preserve complete `pt_BR` translations; create MO; build JSON; aggregate/copy JSON using the exact handle or built-path hash WordPress resolves; and fail if any functional translated string is empty. Update the `i18n:*` package scripts to invoke it after the admin build.

Restore English-only plugin header/source msgids; translate Portuguese only in `pt_BR`. Complete YITH explanation, typography labels, position orders and all new React/modal strings.

- [ ] **Step 4: Run translation build and focused tests.**

Run: `npm run build:admin`; `npm run i18n:pot`; `npm run i18n:json`; `composer test -- --filter='TextDomainTest|AdminAssetsTest|ReleasePackageTest'`

Expected: PHP loader path is plugin-relative, both admin handles resolve current JSON catalogues, PO functional entries are non-empty and the release tests recognize only the current domain catalogs.

- [ ] **Step 5: Commit the task.**

```text
fix: deliver complete Portuguese translations
```

### Task 6: Align WordPress.org material and release-package hygiene

**Files:**
- Modify: `readme.txt`
- Modify: `scripts/build-release.php`
- Modify: `tests/ReleasePackageTest.php`
- Modify: `tests/ReleaseMetadataTest.php`
- Modify: `docs/release/4.0.0-qa.md`

**Interfaces:**
- Consumes: tracked `wordpress-org-assets/screenshot-1.png`, `screenshot-2.png`, `screenshot-3.png`; release slug/version; current translation runtime paths.
- Produces: three accurate screenshot captions, React-era FAQ/compatibility text, clean runtime path list and package assertions rejecting retired catalogues as well as test files.

- [ ] **Step 1: Add failing metadata/package tests.**

```php
public function test_release_package_excludes_retired_i18n_catalogues(): void {
	$entries = $this->getZipEntries( $this->buildReleaseArchive() );
	self::assertSame( array(), array_filter( $entries, static fn( string $entry ): bool => 0 === strpos( $entry, 'darven-extra-price-info/i18n/' ) ) );
}
```

Add a readme assertion for exactly three `== Screenshots ==` captions that name Settings React, the Darven product tab and the installment dialog; assert no `screenshot-4` reference remains.

- [ ] **Step 2: Run focused tests and verify RED.**

Run: `composer test -- --filter='ReleasePackageTest|ReleaseMetadataTest'`

Expected: `i18n/` appears in the staged package and readme metadata disagrees with the assets.

- [ ] **Step 3: Correct public copy and runtime paths.**

Rewrite the obsolete compatibility warning/FAQ/changelog entries in `readme.txt` to use Display, Compatibility and the Darven product tab. Keep historical continuity text and the technical slug unchanged. Remove `i18n` from `$runtime_paths`; do not remove `languages`. Extend package validation to reject `i18n/`, `*.test.js`, test directories, local configuration, symlinks and WordPress.org staging.

- [ ] **Step 4: Build the exact archive and verify GREEN.**

Run: `npm run build:admin`; `composer test -- --filter='ReleasePackageTest|ReleaseMetadataTest'`; `php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0-stabilization.zip`

Expected: the ZIP contains built admin assets, current `languages/` catalogs and runtime PHP, but no retired catalogs, source tests, docs or WordPress.org assets.

- [ ] **Step 5: Commit the task.**

```text
docs: align 4.0.0 release material
```

### Task 7: Repeat end-to-end QA and enforce release gates

**Files:**
- Modify: `docs/release/4.0.0-qa.md`
- Modify/Create: `tests/ReleasePackageTest.php`
- Modify/Create: `tests/ReleaseMetadataTest.php`

**Interfaces:**
- Consumes: exact `dist/darven-extra-price-info-4.0.0.zip`, SHA-256 digest, local clean/upgrade fixtures, PHP 8.0/current and WordPress 5.0/current environments.
- Produces: a release QA document separating actual passes from blocked environment gates; no external release operation.

- [ ] **Step 1: Add release assertions for the stabilization contracts.**

Add assertions that the archive has a single 4.0.0 version, exact built assets, active PHP/JS catalogues, no source tests/retired catalogs/symlinks, and that release metadata still requires PHP 8.0, WordPress 5.0 and WooCommerce.

- [ ] **Step 2: Run the assertion subset and verify RED/GREEN as needed.**

Run: `composer test -- --filter='ReleasePackageTest|ReleaseMetadataTest|PluginMetadataTest'`

Expected: RED for a missing stabilization artifact; GREEN after the required Task 1–6 output is present.

- [ ] **Step 3: Run the complete automated matrix on each available PHP binary.**

Run with PHP 8.0 and current PHP separately:

```text
<php-bin> vendor/bin/phpunit
<php-bin> vendor/bin/phpcs
npm ci
npm run build:admin
npm test -- --runInBand
composer validate --strict
php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip
```

Expected: all commands exit 0. Record exact binary paths, versions, test/assertion counts, warnings and ZIP SHA-256 in `docs/release/4.0.0-qa.md`.

- [ ] **Step 4: Execute local WordPress/WooCommerce verification.**

Install the exact ZIP into a clean local WordPress/WooCommerce instance and an upgrade copy seeded from 3.3.1. Validate: no write before save; v2 document plus four option mirrors after React save; YITH auto/disabled semantics; unauthorized REST behavior; simple parent/direct variation flags; Display CSS; all three frontend modes; separate AJAX-style modal fragments; keyboard and mobile; PHP/JS logs. Run an actual WordPress 5.0 instance too. Record every environment and result.

- [ ] **Step 5: Treat unavailable PHP 8.0 or WordPress 5.0 as a hard gate.**

If either runtime cannot be executed, mark the QA **BLOCKED**, identify the missing binary/image and stop before release preparation. Do not substitute PHP 8.3/WordPress 7.0 as proof of the minimums.

- [ ] **Step 6: Commit the QA evidence.**

```text
chore: verify 4.0.0 stabilization
```

---

## Review and completion sequence

1. Execute each task with a fresh implementation agent and a task-scoped independent review.
2. If a task review finds defects, resume the same implementer for at most three fix rounds, then use a fresh higher-capability implementer for rounds four and five.
3. After Task 7, run one whole-branch review against merge base `e5059ae8107a5f304aea4b87f6da331cf96e3d07`.
4. Run `superpowers:verification-before-completion` with fresh command output before any completion claim or integration decision.
5. Present the ZIP, hash, QA, screenshots, asset diff and unresolved environment gates to the maintainer. Only explicit human approval may open the existing WordPress.org/SVN publication flow.
