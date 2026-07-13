# Darven 3.3 compatibility-first refactor design

## Status

Approved design. This document defines the implementation boundary before work
starts.

## Goal

Rebuild the plugin's PHP architecture around Composer PSR-4 autoloading and
small, responsibility-focused modules while keeping existing installations
working without manual reconfiguration.

The work is deliberately split into two deliverables:

1. The compatibility-first PHP refactor and data-schema transition.
2. A later React replacement for the admin interface.

The first deliverable is the scope of this design. It keeps the current admin
route, tabs, and rendered form markup. It does not add React or a REST API.

## Non-negotiable compatibility contracts

- The deployed plugin remains compatible with PHP 7.4.
- Existing global options, product meta, public hooks, and the `darven-epi`
  identifier remain supported.
- Existing frontend HTML structure and CSS classes remain unchanged. New
  classes may be added, but current selectors cannot be removed or renamed.
- Existing product types, variations, and the optional YITH dynamic-pricing
  integration keep their current behaviours.
- Updating the plugin never performs a bulk or automatic data migration.
- A site that returns to the previous plugin release after saving settings in
  the refactored release still sees the last saved configuration.

## Selected migration strategy

The plugin will use progressive migration with an adapter rather than a
parallel implementation or a big-bang replacement.

The new schema is canonical after its first successful save, but legacy data
continues to be read and written for one compatibility release. This gives the
new code one normalized data source while maintaining a rollback path for
existing installations.

The other evaluated approaches were rejected:

- A fully separate V2 engine would duplicate the pricing rules and make each
  bug fix twice as expensive.
- Refactoring the legacy arrays and classes in place would not provide a safe
  fallback or a stable boundary for the later React interface.

## Source layout and responsibilities

Composer will map `Darven\\ExtraPriceInfo\\` to `src/`. The plugin entry file
will retain WordPress metadata and legacy constants, include Composer's
autoload file, and call the new bootstrap.

```text
src/
  Setup/           Bootstrap and WordPress hook registration
  Repositories/    Normalized reads and writes for settings and product meta
  Compatibility/   Legacy array/meta adaptation and dual-write projection
  Services/        Cash-price, installment, final-price, and markup rules
  Admin/           The current WordPress settings page and product fields
  Frontend/        WooCommerce filters and public asset integration
```

`Setup` is intentionally narrow: it composes services and registers hooks. It
does not contain business rules. `Repositories` are the only layer that knows
where configuration is stored. `Services` contain deterministic pricing and
formatting rules; they do not call `get_option()` or access product meta.

There is no `Skeletons` layer in this refactor. WooCommerce already owns the
product entity through `WC_Product`; the plugin will continue to receive that
object rather than duplicate or subclass it. A dedicated value object may be
introduced later only if a concrete calculation becomes too complex to express
through the repository API.

## Settings and product-data flow

The canonical global option will be `darven_epi_settings`. It contains a
`schema_version` and normalized sections for general pricing, display settings,
and compatibility settings. Per-product plugin configuration will be stored in
the structured `_darven_epi_product_settings` meta key.

Read flow:

1. `SettingsRepository` validates and reads the canonical option or product
   meta when it exists.
2. When that value is absent or invalid, the compatibility adapter reads the
   current `darven_epi_option_*` options or product meta and normalizes it in
   memory.
3. Services consume the repository's normalized API. They never inspect raw
   option arrays directly.

Save flow:

1. The admin controller validates and normalizes submitted data.
2. The repository writes the canonical global option or structured product
   meta.
3. The compatibility writer projects that same normalized data to the legacy
   option arrays and product meta.
4. Each write is read back for verification before the UI reports success.

The projection overlays only plugin-owned legacy keys, preserving unknown keys
that may have been added by integrations or old installations. WordPress
cannot make writes to several options atomic. If canonical storage succeeds
but a legacy mirror cannot be verified, the admin reports the failure, the
repository records the mirror as pending, and the next authorized save retries
the synchronization. The operation is never presented as a silent success.

The compatibility adapter is the only code allowed to know both schemas. This
keeps its removal explicit when the temporary dual-write window ends.

## WordPress and WooCommerce boundary

WordPress calls are isolated to `Setup`, `Admin`, `Frontend`, and the
repositories. The core price services receive `WC_Product` through the
existing WooCommerce filters and ask repositories for settings and product
overrides. They retain current simple, variable, variation, and YITH behavior.

No class will subclass `WC_Product`: that base class owns data stores, cache,
meta, and product-type behaviour. The plugin integrates with the existing
object instead of creating a competing product model.

## Admin and future React boundary

For this release, the WordPress Settings API page remains visually and
structurally unchanged. Its controller delegates reads and writes to the new
settings repository, allowing the legacy form to exercise the new data path.

React is a later, separate milestone. It will add authenticated REST endpoints
with the existing capability and nonce requirements and will use the same
repository API. It must not add a second business-rule or persistence path.

## Verification strategy

The existing PHPUnit suite is extended before each migration step. Required
coverage includes:

- legacy option and product-meta fixtures normalize to the same effective
  settings as the new schema;
- first save creates the canonical format and synchronizes every legacy field;
- unknown legacy keys survive a dual-write;
- invalid or absent canonical data falls back safely to legacy data;
- failed mirror verification returns an actionable admin error and leaves a
  retryable pending state;
- simple products, variable products, variations, and YITH compatibility keep
  their price results;
- the public price HTML and CSS selectors remain stable;
- the plugin loads through Composer autoloading on PHP 7.4.

Each implementation checkpoint runs PHP syntax checks for the supported PHP
versions, PHPUnit, coding standards where applicable, and a diff whitespace
check. Manual WooCommerce verification remains required before release for a
simple product, variable product, product variation, disabled feature state,
and YITH-enabled shop.

## Rollout order

1. Add Composer PSR-4 autoloading and the minimal `Setup` bootstrap without
   changing observable behaviour.
2. Introduce normalized global settings reads with legacy fallback and tests.
3. Add canonical global writes and legacy dual-write synchronization.
4. Introduce the product-settings repository and dual-write product meta.
5. Move pricing, markup, admin, and frontend consumers behind repositories and
   services while preserving public output.
6. Remove obsolete manual loaders and legacy implementations only after all
   consumers and regression tests use the new path.

React, redesigned admin templates, popup redesign, accordion support, and any
change to public markup are explicitly outside this rollout.
