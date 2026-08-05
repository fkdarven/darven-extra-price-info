# Settings Information Architecture Design

**Date:** 2026-08-05  
**Status:** Approved direction  
**Scope:** WordPress admin settings for Darven Preços Parcelados

## Context

The current settings page divides controls into General, Display, Positions, and Compatibility. This structure mirrors internal setting categories rather than merchant tasks. General contains both routine offer configuration and advanced interest rules, appearance separates colors from font sizes, Positions represents a visual ordering decision through long text permutations, and Compatibility gives one YITH setting the same navigational weight as the primary workflows.

The redesign will use balanced progressive disclosure: common settings remain immediately available, while advanced financial and integration controls remain discoverable without dominating the page. The interface will retain the visual conventions of WordPress and WooCommerce rather than introduce a separate application shell or design language.

## Goals

- Organize settings around merchant intent.
- Make a normal cash-price and installment setup understandable without opening advanced controls.
- Turn placement ordering into a visual, direct-manipulation task.
- Preserve every existing option key, stored value, default, and storefront behavior.
- Keep all interactions accessible by keyboard and usable on narrow WordPress admin viewports.

## Non-goals

- Changing price calculations or public storefront markup.
- Migrating or renaming persisted option keys.
- Introducing a setup wizard, custom design system, or live theme-accurate preview.
- Making drag and drop the only way to reorder price statements.

## Information architecture

The four current tabs become three task-based tabs:

1. **Pricing**
2. **Presentation**
3. **Advanced**

Pricing remains the initially selected tab. The active tab uses the existing accessible tab pattern, including arrow-key navigation, Home and End keys, an associated tab panel, and a visible focus state.

### Pricing

Pricing contains three groups in order of frequency and dependency.

#### Cash price

1. Enable cash discount
2. Discount type
3. Discount value
4. Minimum product price
5. Text before the cash price
6. Text after the cash price

The enable control sits in the group heading. When disabled, dependent controls may be visually muted or collapsed, but their saved values must not be erased.

#### Installments

1. Enable installments
2. Display mode
3. Maximum installments
4. Minimum product price
5. Text before the installment statement
6. Text after the installment statement
7. Popup text, shown only for a display mode that uses the popup

The enable control follows the same behavior as Cash price. Conditional controls appear immediately after their controlling option and remain present after reload when applicable.

#### Advanced interest rules

Interest configuration appears as a disclosure below the two primary groups. Its collapsed summary communicates the active mode, for example, “Standard calculation” or “Custom rates configured.” The disclosure contains:

1. Installment where interest begins
2. First interest fee
3. Incremental interest fee
4. Use customized interest fees
5. Interest-fee table, shown only when customized fees are enabled

Opening and closing the disclosure must not alter any value. The control exposes `aria-expanded`, and the disclosed region has an accessible name.

### Presentation

Presentation contains Placement by page followed by Appearance.

#### Visual placement editor

The editor presents three page-context cards in this order:

1. Single product
2. Catalog and shop
3. Other pages, explained as widgets, related products, and other price locations

Each card contains a compact storefront abstraction and a vertical stack of the three statements:

- Original price
- Cash price
- Installment price

The vertical order is the saved order. Users reorder the statements with labeled **Move up** and **Move down** buttons. Drag and drop may be added as a pointer shortcut, but it cannot replace the buttons. After every move, an associated status region announces the context and resulting order.

The visual editor maps directly to the six existing persisted values:

| Visual order | Existing value |
| --- | --- |
| Original, cash, installments | `first` |
| Original, installments, cash | `second` |
| Cash, original, installments | `third` |
| Cash, installments, original | `fourth` |
| Installments, original, cash | `fifth` |
| Installments, cash, original | `sixth` |

The preview demonstrates hierarchy and ordering only. It must be labeled as a visual preview rather than a pixel-perfect rendering of the active theme.

#### Appearance

Appearance fields are grouped by the visible statement they affect rather than by CSS property:

- Cash price: price, prefix, and suffix color and size controls
- Installment price: price, installment number, prefix, and suffix color and size controls

This preserves the existing settings while reducing the need to compare distant color and font-size lists. Native color inputs should expose a readable text value or equivalent accessible WordPress color-picker behavior.

### Advanced

Advanced contains settings that most stores should not need during routine configuration:

1. YITH Dynamic Pricing compatibility mode
2. Legacy settings synchronization explanation

The YITH mode defaults visually to Automatic (recommended), followed by concise applicability guidance. The legacy synchronization message moves out of the global page notice and into this section because it is technical context, not an action required on every visit.

## Saving, notices, and state

One Save changes action saves the complete settings document, as it does today. The action remains reachable after navigating among tabs and should appear in a stable footer area within the settings surface. While saving, editable controls are disabled and the interface exposes its busy state. Success and failure notices remain programmatically announced. A failed save preserves unsaved field values so the user can retry.

Changing tabs, expanding disclosures, or disabling a feature must not silently discard values. The first implementation should preserve the existing REST document shape and section ownership unless an internal adapter is necessary for presenting fields in the new groups.

## Responsive behavior

- At desktop widths, page-context cards may appear in columns when each preview remains readable.
- Below the useful card width, context cards stack vertically.
- Field pairs collapse to one column on narrow screens.
- Tabs may scroll horizontally without causing page-level horizontal overflow.
- Interactive controls maintain a minimum 44-pixel target where practical.
- The complete workflow must remain usable at 360, 768, and 1280 CSS pixels.

## Accessibility requirements

- Every input has an explicit label.
- Help and validation text is connected with `aria-describedby`.
- Tab, disclosure, and reordering behavior work with keyboard-only navigation.
- Reordering never depends solely on dragging, color, or spatial position.
- Move buttons include the statement and direction in their accessible names.
- Disabled boundary actions cannot receive accidental activation.
- Reorder results and save outcomes are announced without moving focus unexpectedly.
- Visible focus styles and reduced-motion preferences are preserved.

## Acceptance criteria

- The top-level tabs are Pricing, Presentation, and Advanced in that order.
- Compatibility no longer occupies a top-level tab.
- Cash and installment essentials appear before advanced interest controls.
- Popup and custom-interest fields appear only when relevant while retaining saved values.
- Each page context visually represents the three price statements in its persisted order.
- Every one of the six visual orders round-trips to its current stored value.
- Appearance controls are grouped by cash and installment statements.
- Existing REST payloads, sanitization, and storefront output remain unchanged.
- Automated tests cover navigation, conditional disclosure, all six order mappings, keyboard reordering, save behavior, and preservation of hidden values.
- Manual checks cover keyboard-only use and 360, 768, and 1280-pixel layouts.

## Approved prototype

The approved non-production prototype demonstrated the three-tab hierarchy and an interactive visual placement editor. It used explicit Move up and Move down controls for each price row, with drag handles shown only as a possible enhancement. The prototype remains outside the plugin source and is not part of the release artifact.
