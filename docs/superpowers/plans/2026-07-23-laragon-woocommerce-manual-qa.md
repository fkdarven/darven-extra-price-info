# Laragon WooCommerce Manual QA Implementation Plan

> **For agentic workers:** Execute inline with Superpowers checkpoints. Do not create or alter any existing Laragon site or database.

**Goal:** Provision an isolated local WordPress and WooCommerce installation that exercises the current refactor branch against the seven pending manual compatibility checks.

**Architecture:** Create a dedicated Laragon document root and database, install WordPress and WooCommerce from their official distributions, and attach the plugin directory from `codex/3.3.0-refactor-execution` as a junction so the test site executes the exact branch under review. Use a local administrator account generated for this disposable site; never expose its password in committed files or chat.

**Tech Stack:** Laragon, Apache, MySQL, WordPress, WooCommerce, PHP 8.5 runtime, Composer-built plugin vendor dependencies.

## Global Constraints

- Preserve every existing Laragon site, database, and configuration.
- Site root: `C:\laragon\www\darven-epi-qa`.
- Database: `darven_epi_qa` only.
- Plugin source is the current isolated worktree; CSS/HTML and compatibility data must be tested as shipped.
- Do not push or create a GitHub PR.
- Verify each step before proceeding; record the seven manual checks in the existing compatibility-refactor plan.

---

### Task 1: Provision an isolated local WordPress site

**Files:**
- Create: `C:\laragon\www\darven-epi-qa\`
- Create: database `darven_epi_qa`

- [x] **Step 1: Start only the Laragon web and database services needed for the QA site.**
- [x] **Step 2: Download and extract the current WordPress release from `wordpress.org` into the dedicated document root.**
- [x] **Step 3: Create the dedicated database and configure WordPress with local-only credentials.**
- [x] **Step 4: Complete WordPress installation and verify the site and wp-admin respond locally.**

### Task 2: Install WooCommerce and attach the refactor branch

**Files:**
- Create: `C:\laragon\www\darven-epi-qa\wp-content\plugins\woocommerce\`
- Create: junction `C:\laragon\www\darven-epi-qa\wp-content\plugins\darven-extra-price-info`

- [x] **Step 1: Download, extract, activate, and verify WooCommerce.**
- [x] **Step 2: Generate optimized Composer autoload files in the refactor worktree.**
- [x] **Step 3: Attach the exact refactor worktree as the plugin directory and activate it.**
- [x] **Step 4: Verify the plugin settings page and a sample product edit screen load without PHP errors.**

### Task 3: Execute manual compatibility checks

- [x] **Step 1: Exercise the seven checks in `2026-07-13-darven-3-3-compatibility-refactor.md`.**
- [x] **Step 2: Record observed pass/fail evidence in the plan without changing production code.**
- [x] **Step 3: Keep the local site available for follow-up fixes and rechecks.**

## Observed QA evidence (2026-07-23)

1. **No write on update:** activating the refactor and creating products left both `darven_epi_settings` and `_darven_epi_product_settings` absent.
2. **Settings dual-write:** real Settings API submissions for General, Positions, Display, and Compatibility created schema-versioned canonical data and retained all four legacy options. No sync-state error remained.
3. **Product dual-write:** all four checkbox combinations were saved through the real WooCommerce product editor. Canonical meta matched the checkboxes and the two legacy flags were `yes`/`no` as expected.
4. **Price markup:** a simple product, variable parent, and variation retained original-price, cash-price, and installment-price order. Existing plus `darven-epi-*` classes were present.
5. **Scope:** cart, checkout, and the product editor returned no storefront Darven markup.
6. **YITH boundary:** the original public YITH package is permanently closed and exposes `YITH_WC_Dynamic_Pricing_Frontend`, not the premium/legacy `YWDPD_Frontend` API this compatibility setting targets. A single-process functional test of that exact API returned cash `90` with the setting disabled and cash `45` from a dynamic base price of `50` with it enabled. No stub or option change persisted.
7. **Rollback:** after a complete real General-settings save, the plugin junction was switched to the repository's 3.2.0 release. It activated cleanly and the public product page displayed the newly saved cash and installment strings. The junction was restored to the refactor worktree afterward.

The local site remains available at `http://localhost/darven-epi-qa/` with WooCommerce and the refactor branch active. The temporary 3.2.0 worktree was removed after verification.

## Follow-up validation (2026-07-28)

- **PHP 7.4 runtime:** the official PHP 7.4.33 Windows CLI was extracted only under `C:\laragon\tmp` and ran the full PHPUnit suite successfully: `88 tests`, `273 assertions`.
- **YITH vendor integration:** a real YITH WooCommerce Dynamic Pricing & Discounts Premium `4.29.0` ZIP exposing `YWDPD_Frontend` was installed only on the isolated site. The native rule editor created and activated a 50% global rule: the $100 QA product displayed YITH's $50 price. Darven then displayed $90 with compatibility disabled and $45 with compatibility enabled, exactly matching the expected price source. Compatibility was restored enabled after the test.
