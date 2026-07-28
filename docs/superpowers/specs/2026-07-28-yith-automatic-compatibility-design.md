# YITH Automatic Compatibility Design

## Goal

Make YITH Dynamic Pricing detection automatic for new Darven installations while preserving the price behaviour selected by existing installations. The Compatibility Mode screen will offer `Automatic` and `Disabled`; it will no longer require a merchant to opt in with an "enable" checkbox.

## Decision

Store an explicit canonical compatibility mode in the existing `compatibility` section:

```php
'yith_dynamic_pricing_mode' => 'auto' | 'disabled'
```

The old `darven_epi_is_yith_dynamic_compatibility_enabled` value remains supported and is still projected to the legacy option during the one-release dual-write window:

| Effective mode | Legacy checkbox value |
| --- | --- |
| `auto` | present with its existing expected value |
| `disabled` | absent |

Read resolution is deliberately non-mutating:

1. A valid canonical mode wins.
2. With no canonical mode, an existing legacy checked value means `auto`.
3. With no canonical mode or legacy checked value, any existing Darven settings mean `disabled`, preserving the behaviour of a previously unchecked installation.
4. With no canonical or legacy Darven settings at all, the mode is `auto`, providing the new-install default without creating options on activation or read.

Saving the Compatibility Mode form persists the canonical mode and mirrors the legacy checkbox representation. It never infers and writes a migration merely because a storefront request was made.

## Runtime behaviour

`ProductPriceResolver` asks the settings repository for the effective mode. In `auto` mode it uses YITH only when all of the following hold:

- `YWDPD_Frontend` exists;
- its singleton and `get_dynamic_price()` method are callable; and
- the returned dynamic value is numeric.

Any unavailable API, invalid return value, or thrown `Throwable` falls back to the normal WooCommerce price path. `disabled` does not call YITH at all. The existing variable-product and regular-price fallbacks remain unchanged.

## Admin behaviour

The current Compatibility Mode tab, URL, Settings API group, and non-React HTML remain in place. The checkbox is replaced by a single accessible select or radio field with these labels:

- `Automatic (recommended)` — uses a valid installed YITH pricing API.
- `Disabled` — always ignores YITH dynamic pricing.

The description explains that Automatic safely falls back to WooCommerce when YITH is absent or cannot provide a price. Existing saved settings render their resolved mode, so an older unchecked installation visibly remains Disabled until a merchant chooses otherwise.

## Tests and verification

Unit coverage will establish the storage matrix: clean install defaults to Automatic, legacy checked settings resolve to Automatic, legacy unchecked settings with other persisted Darven configuration resolve to Disabled, and explicit canonical values override inference. Sanitization and legacy projection tests cover both save choices.

Resolver tests cover Automatic success, API absence, invalid value, and exception fallback, plus Disabled never invoking YITH. The existing Laragon fixture will re-run the real YITH rule scenario: a $100 product discounted by YITH to $50 yields $45 in Automatic and $90 when Disabled. The full PHPUnit suite must pass on the supported PHP 7.4 runtime before the work is considered complete.

## Scope boundaries

This change does not alter public markup, CSS, product flags, other pricing integrations, React plans, or historical option cleanup. It preserves PHP 7.4 syntax and the plugin's no-write-on-read migration rule.
