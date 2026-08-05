# Settings Information Architecture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the category-based settings page with the approved Pricing, Presentation, and Advanced workflow, including a visual and keyboard-accessible placement editor.

**Architecture:** Keep the REST document and persisted WordPress option values unchanged. Add a pure mapping module for the six legacy position values, compose existing settings domains into three task-oriented panels, and let the visual placement control translate direct reordering back into those values. Conditional rendering hides irrelevant controls without deleting their state.

**Tech Stack:** WordPress 5+ admin UI, WooCommerce, `@wordpress/element`, `@wordpress/i18n`, SCSS, Jest/jsdom through `@wordpress/scripts`, PHP 8.0+, WP-CLI translation tooling, LEMP QA site.

## Global Constraints

- Preserve the existing REST document keys: `general`, `display`, `positions`, and `compatibility`.
- Preserve every existing option name, default, sanitizer, and storefront output.
- Preserve the six position values `first`, `second`, `third`, `fourth`, `fifth`, and `sixth` exactly.
- Do not add npm or Composer dependencies.
- Use WordPress admin colors and the system font stack; do not add a custom application shell.
- Reordering must work without drag and drop; Move up and Move down buttons are the primary interaction.
- Hidden or collapsed controls must retain their values.
- Maintain visible focus states, programmatic labels, live announcements, and reduced-motion behavior.
- Verify layouts at 360, 768, and 1280 CSS pixels without page-level horizontal scrolling.
- Begin every production-code task with a failing test and verify the intended failure before implementation.
- The worktree already contains authorized 4.0.0 changes. Stage only the paths named by each task, inspect `git diff --cached`, and never stage unrelated files.

## File Structure

- Create `admin/src/settings/placement-order.js`: pure conversion and movement functions for persisted position values.
- Create `admin/src/settings/__tests__/placement-order.test.js`: exhaustive mapping and boundary tests.
- Create `admin/src/settings/sections/presentation-section.js`: composes placement and appearance settings under one tab.
- Create `admin/src/settings/sections/advanced-section.js`: composes YITH compatibility and legacy synchronization guidance.
- Create `admin/src/settings/sections/pricing-section.js`: replaces General with ordered pricing groups and conditional controls.
- Modify `admin/src/settings/app.js`: render the three task-oriented tabs and route updates to the unchanged document sections.
- Modify `admin/src/settings/sections/positions-section.js`: replace permutation selects with visual order cards.
- Modify `admin/src/settings/sections/display-section.js`: group color and size controls by the statement they affect.
- Modify `admin/src/settings/sections/compatibility-section.js`: keep the YITH field focused and remove redundant outer structure where the Advanced wrapper owns it.
- Delete `admin/src/settings/sections/general-section.js`: superseded by `pricing-section.js`.
- Modify `admin/src/settings/style.scss`: card layout, order preview, disclosure, stable save action, focus states, and responsive rules.
- Modify `admin/src/settings/__tests__/app.test.js`: integration coverage for navigation, conditional fields, visual reordering, persistence, and saving.
- Regenerate `build/settings/index.js`, `build/settings/index.asset.php`, `build/settings/style-index.css`, and `build/settings/style-index-rtl.css`.
- Update the Portuguese POT, PO, MO, and settings JSON files under `languages/`.
- Replace `wordpress-org-assets/screenshot-1.png` after LEMP verification and update `docs/release/4.0.0-qa.md` with the new evidence.

---

### Task 1: Persisted Position-Order Model

**Files:**
- Create: `admin/src/settings/placement-order.js`
- Create: `admin/src/settings/__tests__/placement-order.test.js`

**Interfaces:**
- Produces: `POSITION_ORDERS: Record<string, string[]>`
- Produces: `getPositionOrder(value: string): string[]`
- Produces: `getPositionValue(order: string[]): string`
- Produces: `movePositionStatement(value: string, statement: string, offset: -1|1): string`

- [ ] **Step 1: Write the failing exhaustive mapping tests**

```js
import {
	POSITION_ORDERS,
	getPositionOrder,
	getPositionValue,
	movePositionStatement,
} from '../placement-order';

const expectedOrders = {
	first: [ 'original', 'cash', 'installments' ],
	second: [ 'original', 'installments', 'cash' ],
	third: [ 'cash', 'original', 'installments' ],
	fourth: [ 'cash', 'installments', 'original' ],
	fifth: [ 'installments', 'original', 'cash' ],
	sixth: [ 'installments', 'cash', 'original' ],
};

it( 'round-trips every persisted position value', () => {
	expect( POSITION_ORDERS ).toEqual( expectedOrders );
	Object.entries( expectedOrders ).forEach( ( [ value, order ] ) => {
		expect( getPositionOrder( value ) ).toEqual( order );
		expect( getPositionValue( order ) ).toBe( value );
	} );
} );

it( 'falls back safely and ignores boundary moves', () => {
	expect( getPositionOrder( 'unknown' ) ).toEqual( expectedOrders.first );
	expect( movePositionStatement( 'first', 'original', -1 ) ).toBe( 'first' );
	expect( movePositionStatement( 'first', 'installments', 1 ) ).toBe( 'first' );
} );

it( 'maps a valid movement to the matching persisted value', () => {
	expect( movePositionStatement( 'second', 'cash', -1 ) ).toBe( 'first' );
	expect( movePositionStatement( 'first', 'cash', -1 ) ).toBe( 'third' );
} );
```

- [ ] **Step 2: Run the focused test and verify the missing-module failure**

Run: `npm test -- --runInBand admin/src/settings/__tests__/placement-order.test.js`

Expected: FAIL because `../placement-order` does not exist.

- [ ] **Step 3: Implement the pure mapping module**

```js
export const POSITION_ORDERS = {
	first: [ 'original', 'cash', 'installments' ],
	second: [ 'original', 'installments', 'cash' ],
	third: [ 'cash', 'original', 'installments' ],
	fourth: [ 'cash', 'installments', 'original' ],
	fifth: [ 'installments', 'original', 'cash' ],
	sixth: [ 'installments', 'cash', 'original' ],
};

export const getPositionOrder = ( value ) => [
	...( POSITION_ORDERS[ value ] || POSITION_ORDERS.first ),
];

export const getPositionValue = ( order ) =>
	Object.keys( POSITION_ORDERS ).find(
		( value ) => POSITION_ORDERS[ value ].join( '|' ) === order.join( '|' )
	) || 'first';

export const movePositionStatement = ( value, statement, offset ) => {
	const order = getPositionOrder( value );
	const currentIndex = order.indexOf( statement );
	const targetIndex = currentIndex + offset;

	if (
		-1 === currentIndex ||
		targetIndex < 0 ||
		targetIndex >= order.length
	) {
		return value;
	}

	[ order[ currentIndex ], order[ targetIndex ] ] = [
		order[ targetIndex ],
		order[ currentIndex ],
	];

	return getPositionValue( order );
};
```

- [ ] **Step 4: Run the focused test and verify it passes**

Run: `npm test -- --runInBand admin/src/settings/__tests__/placement-order.test.js`

Expected: PASS with three tests.

- [ ] **Step 5: Commit the isolated model**

```bash
git add admin/src/settings/placement-order.js admin/src/settings/__tests__/placement-order.test.js
git diff --cached --check
git commit -m "feat: model visual price statement order"
```

---

### Task 2: Three Task-Oriented Tabs

**Files:**
- Create: `admin/src/settings/sections/presentation-section.js`
- Create: `admin/src/settings/sections/advanced-section.js`
- Modify: `admin/src/settings/app.js`
- Modify: `admin/src/settings/__tests__/app.test.js`

**Interfaces:**
- Consumes: unchanged settings document with `general`, `display`, `positions`, and `compatibility` objects.
- Produces: `PresentationSection({ document, onChange })`, where `onChange(section, field, value)` updates the existing document.
- Produces: `AdvancedSection({ settings, onChange })`, where `onChange(field, value)` updates `compatibility`.

- [ ] **Step 1: Replace the navigation expectations with a failing three-tab integration test**

Add a test that renders `SettingsApp`, then asserts the role-tab labels are exactly:

```js
expect(
	Array.from( container.querySelectorAll( '[role="tab"]' ) ).map(
		( tab ) => tab.textContent
	)
).toEqual( [ 'Pricing', 'Presentation', 'Advanced' ] );
```

Update the existing roving-focus test to move from Pricing to Presentation with ArrowRight, from Presentation to Advanced with End, and back to Pricing with Home.

- [ ] **Step 2: Run the app test and verify it fails on the old labels**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Expected: FAIL showing General, Display, Positions, and Compatibility instead of the three approved labels.

- [ ] **Step 3: Add the two composition components**

`PresentationSection` renders `PositionsSection` with `document.positions` and `DisplaySection` with `document.display`. Each child callback calls `onChange( 'positions', field, value )` or `onChange( 'display', field, value )`.

`AdvancedSection` renders `CompatibilitySection`, then this localized technical notice:

```jsx
<div className="darven-precos-parcelados-admin__notice darven-precos-parcelados-admin__notice--info">
	{ __(
		'Existing legacy settings remain synchronized automatically. No action is normally required.',
		'darven-multiplos-precos-informativos'
	) }
</div>
```

- [ ] **Step 4: Rewire `SettingsApp` without changing the REST shape**

Use this tab definition:

```js
const domain = 'darven-multiplos-precos-informativos';

const tabs = [
	{ name: 'pricing', title: __( 'Pricing', domain ) },
	{ name: 'presentation', title: __( 'Presentation', domain ) },
	{ name: 'advanced', title: __( 'Advanced', domain ) },
];
```

Set `createSettingsState().activeTab` to `pricing`. Render General with `document.general`, Presentation with the full document, and Advanced with `document.compatibility`. Remove the global legacy-synchronization notice from above the tabs.

- [ ] **Step 5: Update the whole-document save test**

The test must edit maximum installments on Pricing, font size and one position value on Presentation, YITH mode on Advanced, then assert `saveSettings` receives the full document with all four existing sections and only the edited fields changed.

- [ ] **Step 6: Run the app test and verify navigation and saving pass**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Expected: PASS, including load, error, save, busy state, and three-tab keyboard navigation.

- [ ] **Step 7: Commit the navigation slice**

```bash
git add admin/src/settings/app.js admin/src/settings/sections/presentation-section.js admin/src/settings/sections/advanced-section.js admin/src/settings/__tests__/app.test.js
git diff --cached --check
git commit -m "feat: organize settings by merchant task"
```

---

### Task 3: Visual Placement Editor

**Files:**
- Modify: `admin/src/settings/sections/positions-section.js`
- Modify: `admin/src/settings/__tests__/app.test.js`

**Interfaces:**
- Consumes: `getPositionOrder(value)` and `movePositionStatement(value, statement, offset)` from Task 1.
- Produces: three visual position cards that continue to call `onChange(fieldName, persistedValue)`.

- [ ] **Step 1: Add a failing visual-order integration test**

After opening Presentation, locate the single-product card through `data-position-field="darven_epi_single_product_position"`. Assert its `data-statement` rows match the saved `second` value:

```js
expect(
	Array.from( singleProduct.querySelectorAll( '[data-statement]' ) ).map(
		( row ) => row.dataset.statement
	)
).toEqual( [ 'original', 'installments', 'cash' ] );
```

Click the button labeled `Move cash price up in Single product`, assert the rows become `original`, `cash`, `installments`, save, and assert `document.positions.darven_epi_single_product_position` is `first`.

- [ ] **Step 2: Run the app test and verify the old select cannot satisfy it**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Expected: FAIL because no visual rows or labeled reorder buttons exist.

- [ ] **Step 3: Replace the three permutation selects with visual cards**

Define contexts in usage order: Single product, Catalog and shop, Other pages. Define statement labels for Original price, Cash price, and Installment price. For every card:

- render its current `getPositionOrder( settings[ name ] || 'first' )`;
- render a compact product placeholder followed by the three price rows;
- give each row `data-statement={ statement }`;
- add Move up and Move down buttons;
- disable Move up on the first row and Move down on the last row;
- call `onChange( name, movePositionStatement( value, statement, offset ) )`;
- write the resulting order into a card-local `aria-live="polite"` status;
- mark the preview description as order-only, not theme-accurate.

Use `sprintf` from `@wordpress/i18n` for complete translatable accessible names:

```js
sprintf(
	__( 'Move %1$s up in %2$s', domain ),
	statementLabel,
	contextTitle
)
```

Extend the Jest `@wordpress/i18n` mock with a numbered `%s` replacement implementation so the test observes the same accessible string.

- [ ] **Step 4: Run the mapping and app tests**

Run: `npm test -- --runInBand admin/src/settings/__tests__/placement-order.test.js admin/src/settings/__tests__/app.test.js`

Expected: PASS; the UI reorders visually and saves the unchanged legacy value.

- [ ] **Step 5: Commit the interaction slice**

```bash
git add admin/src/settings/sections/positions-section.js admin/src/settings/__tests__/app.test.js
git diff --cached --check
git commit -m "feat: add visual price placement editor"
```

---

### Task 4: Pricing Progressive Disclosure

**Files:**
- Create: `admin/src/settings/sections/pricing-section.js`
- Delete: `admin/src/settings/sections/general-section.js`
- Modify: `admin/src/settings/app.js`
- Modify: `admin/src/settings/__tests__/app.test.js`

**Interfaces:**
- Produces: `PricingSection({ settings, onChange })` for the unchanged `general` object.
- Preserves: every field listed in `admin/src/shared/settings-fields.json` under `general`.

- [ ] **Step 1: Add failing conditional-visibility and preservation tests**

Cover these exact states:

- `darven_epi_popup_text` is absent in `default` display mode and appears after changing the mode to `popup`.
- `darven_epi_installments_interest_fee_table` is absent when its enable field is empty and appears after enabling customized fees.
- Disabling installments hides dependent installment controls but a subsequent save retains their previous values in the REST document.
- The native Advanced interest rules disclosure contains the interest controls and remains keyboard reachable.

- [ ] **Step 2: Run the focused app test and verify the current always-visible fields fail it**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Expected: FAIL because popup, interest-table, and dependent installment controls currently render unconditionally.

- [ ] **Step 3: Implement ordered Pricing groups**

Create `pricing-section.js` by moving the reusable field renderer from General, then split definitions into:

- cash enablement and dependent cash fields;
- installment enablement and dependent core installment fields;
- interest calculation fields inside native `<details>`;
- custom table field rendered only when customized fees are enabled.

Render popup text only when mode is `popup` or `nofee`. Render each feature enable checkbox in its section heading. Keep all values in parent state when controls are absent. The details summary is `Custom rates configured` when the custom table is enabled and `Standard calculation` otherwise.

- [ ] **Step 4: Update the app import and remove the superseded file**

Import `PricingSection` from `./sections/pricing-section` and render it with `document.general`. Delete `general-section.js` only after the new component passes the same field assertions against `settings-fields.json`.

- [ ] **Step 5: Run the app test and full JavaScript suite**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Run: `npm test -- --runInBand`

Expected: both commands PASS, including preservation of values hidden by conditional rendering.

- [ ] **Step 6: Commit the pricing slice**

```bash
git add admin/src/settings/app.js admin/src/settings/sections/pricing-section.js admin/src/settings/sections/general-section.js admin/src/settings/__tests__/app.test.js
git diff --cached --check
git commit -m "feat: progressively disclose pricing settings"
```

---

### Task 5: Statement-Oriented Appearance and Responsive Styling

**Files:**
- Modify: `admin/src/settings/sections/display-section.js`
- Modify: `admin/src/settings/sections/compatibility-section.js`
- Modify: `admin/src/settings/style.scss`
- Modify: `admin/src/settings/__tests__/app.test.js`

**Interfaces:**
- Preserves: all 14 fields under `settings-fields.json.display`.
- Produces: one Appearance group containing Cash price and Installment price subgroups.

- [ ] **Step 1: Add a failing appearance-grouping test**

Open Presentation and assert:

- Placement by page appears before Appearance;
- Cash price appearance appears before Installment price appearance;
- each semantic row contains its matching color input and font-size select;
- all 14 existing display field names occur exactly once.

- [ ] **Step 2: Run the app test and verify the property-based layout fails it**

Run: `npm test -- --runInBand admin/src/settings/__tests__/app.test.js`

Expected: FAIL because the existing component separates every color from every font size.

- [ ] **Step 3: Rebuild the appearance field definitions by statement**

Use two groups:

```js
const appearanceGroups = [
	{
		title: __( 'Cash price', domain ),
		parts: [
			[ 'Price', 'darven_epi_color_of_incash_price', 'darven_epi_font_size_of_incash_price' ],
			[ 'Text before price', 'darven_epi_color_of_incash_prefix', 'darven_epi_font_size_of_incash_prefix' ],
			[ 'Text after price', 'darven_epi_color_of_incash_suffix', 'darven_epi_font_size_of_incash_suffix' ],
		],
	},
	{
		title: __( 'Installment price', domain ),
		parts: [
			[ 'Price', 'darven_epi_color_of_installments_price', 'darven_epi_font_size_of_installments_price' ],
			[ 'Installment number', 'darven_epi_color_of_installments_install', 'darven_epi_font_size_of_installments_install' ],
			[ 'Text before price', 'darven_epi_color_of_installments_prefix', 'darven_epi_font_size_of_installments_prefix' ],
			[ 'Text after price', 'darven_epi_color_of_installments_suffix', 'darven_epi_font_size_of_installments_suffix' ],
		],
	},
];
```

Pass every raw field name through the existing `assertField`. Give color and size controls complete localized accessible labels.

- [ ] **Step 4: Apply the approved WordPress-native layout**

In `style.scss`, add focused classes for:

- section headings with enable toggles;
- two-column field grids that collapse to one column;
- three visual placement cards that stack when their minimum readable width is unavailable;
- price rows with clear boundaries and non-color labels;
- 44-pixel reorder controls with visible focus states;
- appearance subgroups and semantic rows;
- native details/summary styling;
- a stable Save changes footer inside the settings surface;
- horizontal tab overflow without page-level overflow;
- 360, 768, and 1280-pixel behavior;
- no new animation beyond the existing reduced-motion-aware loading spinner.

Keep `#2271b1`, `#135e96`, `#1d2327`, `#50575e`, `#c3c4c7`, `#dcdcde`, `#f0f0f1`, and white surfaces so every contrast pair remains within the established WordPress admin palette.

- [ ] **Step 5: Run unit tests and lint the changed source**

Run: `npm test -- --runInBand`

Run: `npx wp-scripts lint-js admin/src/settings`

Run: `npx wp-scripts lint-style admin/src/settings/style.scss`

Expected: all commands exit 0.

- [ ] **Step 6: Commit the presentation slice**

```bash
git add admin/src/settings/sections/display-section.js admin/src/settings/sections/compatibility-section.js admin/src/settings/style.scss admin/src/settings/__tests__/app.test.js
git diff --cached --check
git commit -m "feat: clarify settings presentation"
```

---

### Task 6: Translations, Bundles, and Automated Verification

**Files:**
- Modify: `languages/darven-multiplos-precos-informativos.pot`
- Modify: `languages/darven-multiplos-precos-informativos-pt_BR.po`
- Modify: `languages/darven-multiplos-precos-informativos-pt_BR.mo`
- Modify: `languages/darven-multiplos-precos-informativos-pt_BR-darven-precos-parcelados-settings.json`
- Modify: `build/settings/index.js`
- Modify: `build/settings/index.asset.php`
- Modify: `build/settings/style-index.css`
- Modify: `build/settings/style-index-rtl.css`

**Interfaces:**
- Consumes: all localized strings introduced by Tasks 2 through 5.
- Produces: deterministic WordPress translation and runtime build artifacts.

- [ ] **Step 1: Run the translation build once to expose every missing Portuguese string**

Run: `npm run build:admin && php scripts/build-translations.php`

Expected: the translation builder exits nonzero and lists new `pt_BR` entries with blank translations.

- [ ] **Step 2: Add complete Brazilian Portuguese translations**

Use these canonical terms consistently:

- Pricing → Preços
- Presentation → Apresentação
- Advanced → Avançado
- Placement by page → Posicionamento por página
- Single product → Produto individual
- Catalog and shop → Catálogo e loja
- Other pages → Outras páginas
- Visual preview → Prévia visual
- Original price → Preço original
- Cash price → Preço à vista
- Installment price → Preço parcelado
- Standard calculation → Cálculo padrão
- Custom rates configured → Taxas personalizadas configuradas
- Advanced interest rules → Regras avançadas de juros
- Move up → Mover para cima
- Move down → Mover para baixo
- Enabled → Ativado

Translate the complete sentences naturally, preserve `%1$s` and `%2$s` placeholders in their original order, and leave only the PO header with an empty `msgid`.

- [ ] **Step 3: Regenerate deterministic bundles and translations**

Run: `npm run build:admin`

Run: `php scripts/build-translations.php`

Expected: both commands exit 0; the settings JSON contains all new UI strings with non-empty `pt_BR` values.

- [ ] **Step 4: Run the complete automated matrix**

Run: `npm test -- --runInBand`

Run: `composer test`

Run: `composer phpcs`

Run: `php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip`

Expected: every command exits 0 and the release ZIP contains regenerated settings assets but no test or source-only files.

- [ ] **Step 5: Commit generated and source-language artifacts**

```bash
git add build/settings languages/darven-multiplos-precos-informativos.pot languages/darven-multiplos-precos-informativos-pt_BR.po languages/darven-multiplos-precos-informativos-pt_BR.mo languages/darven-multiplos-precos-informativos-pt_BR-darven-precos-parcelados-settings.json
git diff --cached --check
git commit -m "build: refresh settings assets and translations"
```

---

### Task 7: LEMP Interaction and Release Evidence

**Files:**
- Modify: `wordpress-org-assets/screenshot-1.png`
- Modify: `docs/release/4.0.0-qa.md`

**Interfaces:**
- Consumes: a freshly rebuilt `dist/darven-extra-price-info-4.0.0.zip` containing the Task 8 visibility bundle.
- Produces: verified LEMP installation and current WordPress.org settings screenshot.

- [ ] **Step 1: Install the exact release ZIP on the persistent QA site**

Rebuild the release ZIP after Task 8 so the artifact includes the final Presentation visibility predicate:

```bash
php scripts/build-release.php --output=dist/darven-extra-price-info-4.0.0.zip
```

Run from the project root:

```bash
wp plugin install "$PWD/dist/darven-extra-price-info-4.0.0.zip" --force --activate --path=/srv/www/staging/public/darven-epi-qa
```

Expected: WP-CLI reports the plugin installed and activated successfully at `https://staging.test/darven-epi-qa`.

- [ ] **Step 2: Verify the three-tab workflow in the browser**

Authenticate with the already-provisioned local QA administrator without recording its password in any file. Visit:

`https://staging.test/darven-epi-qa/wp-admin/admin.php?page=darven-epi-admin`

Verify Pricing is initially selected, keyboard arrows move through all three tabs, hidden values survive disable/re-enable cycles, and Save changes persists after reload.

With both cash price and installments disabled, verify Presentation is absent. Enable only installments and verify Presentation returns.

- [ ] **Step 3: Verify every visual placement permutation**

For each page context, use Move up and Move down to produce all six possible orders. Save and reload after each order. Confirm the same visual order returns and the public price statement order matches on a representative product page.

- [ ] **Step 4: Verify responsive and accessibility behavior**

At 360, 768, and 1280 CSS pixels, confirm no page-level horizontal scroll, readable visual cards, 44-pixel reorder controls, visible focus, and a stable save action. Complete one reorder-and-save flow with keyboard only and confirm the live region announces the new order without moving focus.

- [ ] **Step 5: Refresh the release screenshot and QA record**

Capture the approved Pricing or Presentation view at 1280×720 with no browser chrome or sensitive data. Replace `wordpress-org-assets/screenshot-1.png`. Add the exact automated command results, viewport checks, keyboard result, placement round-trip evidence, ZIP identity, and QA date to `docs/release/4.0.0-qa.md`.

- [ ] **Step 6: Commit only the refreshed evidence**

```bash
git add wordpress-org-assets/screenshot-1.png docs/release/4.0.0-qa.md
git diff --cached --check
git commit -m "docs: refresh settings release evidence"
```

---

### Task 8: Hide Presentation Without Visible Prices

**Files:**
- Modify: `admin/src/settings/app.js`
- Modify: `admin/src/settings/__tests__/app.test.js`
- Regenerate: `build/settings/index.js`
- Regenerate: `build/settings/index.asset.php`

**Interfaces:**
- Produces: `getAvailableTabs(document): Array<{ name: string, title: string }>`.
- Preserves: `Pricing` and `Advanced` when both price modes are disabled; includes `Presentation` whenever cash price or installments is enabled.

- [ ] **Step 1: Add a failing visibility test**

Render a document with both `darven_epi_incash_is_enabled` and `darven_epi_installments_is_enabled` set to the empty string. Assert the role-tab labels are exactly `Pricing` and `Advanced`, and that no Presentation panel is rendered. Render a second document with only installments enabled and assert all three tabs are available.

- [ ] **Step 2: Run the focused test and verify the old unconditional tab list fails**

Run: `/tmp/node-v24.19.0-linux-x64/bin/node node_modules/@wordpress/scripts/scripts/test-unit-js.js --runInBand admin/src/settings/__tests__/app.test.js`

Expected: FAIL because the current app always renders Presentation.

- [ ] **Step 3: Filter tabs from the existing general settings state**

Add `getAvailableTabs(document)` beside the tab definitions. Treat a value equal to its checkbox field name as enabled:

```js
const hasVisiblePrice = ( settings, field ) => settings[ field ] === field;

const getAvailableTabs = ( document ) => {
	const general = document?.general || {};
	const hasPrice =
		hasVisiblePrice( general, 'darven_epi_incash_is_enabled' ) ||
		hasVisiblePrice( general, 'darven_epi_installments_is_enabled' );

	return hasPrice ? tabs : tabs.filter( ( tab ) => 'presentation' !== tab.name );
};
```

Use the available-tab list for rendering, roving keyboard indices, and tab activation. If a loaded document has no visible prices while the state points at Presentation, render Pricing and make Pricing the roving tab. Do not mutate or clear any hidden Presentation settings.

- [ ] **Step 4: Run focused and full JavaScript tests**

Run: `/tmp/node-v24.19.0-linux-x64/bin/node node_modules/@wordpress/scripts/scripts/test-unit-js.js --runInBand admin/src/settings/__tests__/app.test.js`

Run: `/tmp/node-v24.19.0-linux-x64/bin/node node_modules/@wordpress/scripts/scripts/test-unit-js.js --runInBand`

Expected: PASS with the new visibility coverage and all existing tests.

- [ ] **Step 5: Regenerate only the settings JavaScript bundle and manifest**

Run: `/tmp/node-v24.19.0-linux-x64/bin/node node_modules/@wordpress/scripts/scripts/build.js --webpack-src-dir=admin/src/settings --output-path=build/settings`

Verify `build/settings/index.js` contains the Presentation visibility predicate and `build/settings/index.asset.php` has the new content hash.

- [ ] **Step 6: Commit the visibility slice and generated settings bundle**

```bash
git add admin/src/settings/app.js admin/src/settings/__tests__/app.test.js build/settings/index.js build/settings/index.asset.php
git diff --cached --check
git commit -m "fix: hide presentation without visible prices"
```

---

## Self-Review Results

- Spec coverage: all approved navigation, field ordering, conditional disclosure, visual placement, mapping, accessibility, responsive, saving, translation, and LEMP evidence requirements map to Tasks 1 through 7.
- Placeholder scan: no deferred implementation markers or unspecified error-handling steps remain.
- Interface consistency: `movePositionStatement` returns an existing persisted string; every component callback ultimately calls `updateSettingsField(document, section, field, value)`; the REST shape never changes.
- Risk check: the only data transformation is exhaustively tested against all six existing order values before the visual component consumes it.
