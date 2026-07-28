# Task 7 Report — Price Presentation Services

## Delivered

- Added `InstallmentPriceFormatter`, which reads the normalized general settings once, resolves the product price through `ProductPriceResolver`, preserves the installment table markup, and keeps custom interest-table calculations repeatable.
- Added `FinalPriceFormatter`, which retains the admin/cart/checkout and invalid-product guards, obtains product overrides through `ProductSettingsRepository`, and preserves all six configured ordering variants.
- Added `InlineStyles`, which reads the display section through `SettingsRepository` and renders the established inline CSS selectors.
- Converted the installment and final-price legacy classes into dependency-wiring shims with no include-time instance creation. The existing CSS partial now creates `InlineStyles` and echoes its output; it does not introduce an enqueue path.

## Compatibility coverage

- Default installments, custom table repeatability, balanced table rows, popup classes, `aria-expanded="false"`, and `aria-hidden="true"` are covered by the namespaced installment formatter tests.
- Invalid products, canonical and legacy product overrides, markup classes, and every `first` through `sixth` position are covered by the final-price formatter tests.
- Inline CSS is covered from legacy display settings only, including the existing `.darven-epi-incash-price` selector and configured colors.
- The frontend static regression now verifies the partial's renderer responsibility and the service's selector ownership.

## TDD and verification

- RED: formatter tests initially failed because `InstallmentPriceFormatter` did not exist.
- RED: inline CSS test caught literal newline serialization; the renderer was corrected.
- RED: CSS compatibility assertion caught an unintended `!important` on the installment-price color; it was removed.
- PHP 8.5 final suite: 76 tests, 229 assertions, all passing.
- `git diff --check`: no whitespace errors.

## Follow-up review corrections

- Restored the legacy `Darven_Epi_Format_Installments_Price::get_installments_price()` return signature to `?string` and delegates its calculated value without exposing the formatter's float return value.
- Restored `initiate_options()` as a settings reload boundary by rebuilding the formatter with a fresh normalized settings repository.
- Added RED/GREEN tests for both legacy methods and explicit coverage for the cash prefix/price/suffix plus installment count/price markup classes.
