---
target: resources/js/Pages/SalesOrders/Create/Index.vue
total_score: 20
p0_count: 1
p1_count: 2
timestamp: 2026-06-30T04-20-48Z
slug: resources-js-pages-salesorders-create-index-vue
---
## Critique: Sales Order Create — Product Picker & Line Items Table

**Target:** `resources/js/Pages/SalesOrders/Create/Index.vue` (orchestrator) + `Components/SOProductPicker.vue` (picker) + `Components/SOLineItemsTable.vue` (line items). The Create page itself is a thin shell; all the friction lives in these two children.

### Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Stock Tag is a **static snapshot** from search time; doesn't reflect in-progress allocation or cross-item consumption of the same variant |
| 2 | Match System / Real World | 3 | Terminology is domain-correct; "Sale Unit" + conversion factor is the right mental model |
| 3 | User Control and Freedom | 2 | **Cannot change the sale unit of an added line** — must delete + re-search + re-add; no undo |
| 4 | Consistency and Standards | 2 | Picker Stock column shows **base** stock; table Stock column shows **converted**; severity mixes base vs converted semantics; warn state is lost for non-base pills |
| 5 | Error Prevention | 2 | Per-line max-qty is capped (good), but **no aggregate stock guard** across multiple line items of the same variant in different units → can oversell |
| 6 | Recognition Rather Than Recall | 3 | Pills surface name + factor + price + stock; but the base→converted relationship is never explained inline |
| 7 | Flexibility and Efficiency | 1 | **Enter on highlighted result does nothing** (dead `onKeydown` branch) — no keyboard add; no bulk; no unit change post-add |
| 8 | Aesthetic and Minimalist Design | 2 | 4 pills × 4 data points each, crammed into a `2fr` column → wraps tall, hard to scan; expander column re-lists sale units read-only (noise) |
| 9 | Error Recovery | 3 | Delete has confirm; oversell only caught at backend FIFO deduction (late, after form submit) |
| 10 | Help and Documentation | 2 | No inline explanation of what `×12` means for a first-time user |
| **Total** | | **20/40** | **Acceptable — significant improvements needed** |

### Anti-Patterns Verdict

**LLM assessment:** Not AI-slop in the gradient/eyebrow sense — this is a genuine operational tool and it mostly reads as one. The slop here is *operational slop*: a custom-built combobox that reinvents PrimeVue's AutoComplete affordances (defensible only because of the multi-unit requirement), a read-only expander that restates information already shown, and a stock column whose meaning shifts between two screens.

**Deterministic scan:** 1 advisory — `#b45309` (amber-700 dark border) at `SOProductPicker.vue:839` is outside DESIGN.md's documented palette. This is a **false positive** — it's an intentional dark-mode amber variant; the real issue is that DESIGN.md doesn't document dark-mode status tints, not that the code is wrong.

**Visual overlays:** Skipped — read-only mode at critique time; no live-server started.

### Overall Impression

The architecture is sound: search → pills-per-unit → add → editable table. But the **sale unit is treated as a second-class attribute** bolted onto a product row, when it is in fact a first-class sellable line with its own price and its own available stock. That single mismatch is the root of both of the user's concerns. Fix the data model's presentation (one selectable line per unit) and most of the friction dissolves.

### What's Working

1. **Conversion math is actually implemented** — `availableInSaleUnit()` (`SOLineItemsTable.vue:34`) and `buildPills()` (`SOProductPicker.vue:70`) both do `Math.floor(baseStock / cf)`. The arithmetic isn't the bug; the *presentation and aggregation* of that number is.
2. **Disabled-already-added state** — `addedKeys` correctly disables the specific `${variantId}:${unitId}` pill, letting the same product be added in multiple units without disabling unrelated pills. Good granularity.
3. **Mobile/desktop split** in the picker — the stacked mobile card and the grid desktop row are properly separated, not one responsive compromise.

### Priority Issues

**[P0] Stock display is a static snapshot with no live allocation feedback, and no cross-item aggregation**
- *Why it matters:* Heart of concern #1. When a variant has base stock 24 and you add "1 Caja ×12", the line's Stock Tag still reads "In stock: 2" (the converted snapshot). Add a second line for the same variant in Unidades, and *that* row also shows "In stock: 24" (its own snapshot). The operator sees two rows claiming 24 and 2 available simultaneously, with no notion that the 12 base units already claimed by the Caja reduce what the Unidad row can sell. Overselling is only caught at backend FIFO deduction — after submit. On a tool whose success metric is "stock discrepancy under 2%", this is the showstopper.
- *Fix:* Maintain a **running per-variant base-stock ledger** in the Create page (or in `SOLineItemsTable`). Each line item's displayed "available" = `floor((baseStock − sumOfBaseAlreadyAllocatedAcrossAllLinesForThisVariant) / cf)`. Update it reactively as quantities change. The Stock Tag then becomes a *live remaining* indicator, not a snapshot. Pair with a warn/danger state when remaining hits 0.
- *Suggested command:* `/impeccable harden`

**[P1] Sale-unit selection is crammed into a wrapping pill row — hard to scan and pick with 3+ units**
- *Why it matters:* Concern #2. The `so-col-units` column is `2fr` of a 5-column grid (~16% width). Each pill carries name + `×factor` + price + stock icon + count — 4 data points. With base + 3 sale units = 4 pills, they wrap into 2–3 rows inside one cell, making the option row tall, the hit targets small, and price/stock comparison across units require reading wrapping micro-text. The "pick one of three" task becomes "scan a wrapping micro-list."
- *Fix (decided):* **Separate search result per unit** — each sale unit becomes its own row in the dropdown, differentiated by unit name. Simplest mental model, makes keyboard Enter-add natural. Accept the trade-off that a product with 3 units takes 3 of the 20 result slots.
- *Suggested command:* `/impeccable shape` (re-plan the unit selection UX), then `/impeccable polish`

**[P1] Stock severity semantics are inconsistent between base and sale-unit pills, and between picker and table**
- *Why it matters:* In `buildPills` (`SOProductPicker.vue:78`), sale-unit pills call `getStockSeverity(avail, null)` — `null` minStock means **sale-unit pills can never show the amber "low stock" warn state**, only green or red. The base pill *can* show warn. So a low-stock product shows its base pill amber and its Caja pill green — contradictory signals for the same product. Separately, the table's `getStockSeverity` (`SOLineItemsTable.vue:40`) uses converted-avail for the danger check but **base stock** for the warn check (`baseStock <= minStock`), mixing units in one severity computation.
- *Fix:* Define one rule: warn = remaining (in the *selected unit*) ≤ a per-unit threshold derived from `minimum_stock_level`. Apply it identically in picker rows and table Tag. Make the threshold unit-aware so all rows for one product agree on severity.
- *Suggested command:* `/impeccable clarify`

**[P2] Cannot change a line item's sale unit after adding — must delete and re-add**
- *Why it matters:* Quantity and unit price are editable inline; sale unit is not. The expander column re-lists "Available Sale Units" **read-only** — teasing an affordance that isn't there. To switch a line from Caja to Unidad, the operator deletes the row, re-searches, re-picks. On a "sale in under 60 seconds" tool, that's a real cost.
- *Fix:* Replace the read-only "Sale Unit" column in the table with a PrimeVue `Select` bound to `sale_units`, or make the expander's unit list actionable. On change, recompute `conversion_factor`, `unit_price` (default to the unit's price, editable), `line_total`, and the live available stock. Remove the redundant read-only expander.
- *Suggested command:* `/impeccable shape`

**[P2] Keyboard add is dead — Enter on the highlighted result does nothing**
- *Why it matters:* `SOProductPicker.vue:169` — the Enter branch `preventDefault()`s but never calls `addFromPill`. `aria-activedescendant` points at the *option row*, but the actionable elements are the pills inside it. A keyboard user can arrow through products but cannot add any; they must mouse to a pill. For Alex (power user) and Sam (keyboard/a11y) this is a red flag.
- *Fix:* With the one-row-per-unit restructure, Enter adds the focused unit row naturally. Ensure `aria-activedescendant` points at the actionable option and the Enter handler calls the add function.
- *Suggested command:* `/impeccable harden`

### Persona Red Flags

**Alex (Power User):** Cannot complete the core add-item action from the keyboard — Tab/Enter into the dropdown highlights products but Enter is a no-op. Must leave the keyboard to click a pill. No way to change a line's unit without delete+re-search. Will reach for the mouse, lose seconds per line, and resent it.

**Jordan (First-Timer):** Sees `×12` on a pill with no explanation of what it means (12 base units per box? 12 boxes?). The picker Stock column says "24" while the Caja pill says "2" — the relationship (2 boxes = 24 units) is never stated. Will guess, possibly wrong.

**Sam (Accessibility):** `aria-activedescendant` points at `so-opt-{vIndex}` (the product row), but the row is `role="option"` while the real choices are nested `<button>` pills that aren't in the activedescendant chain. Screen reader announces a product as "selected" but the user has no keyboard way to actually select a unit. The combobox role/aria model is half-wired.

**Riley (Stress Tester):** Adds the same variant in Caja (claims 12 base) then in Unidad (still shows "In stock: 24"), sets Unidad qty to 24 — the form accepts it, backend FIFO deduction later throws "Insufficient stock." The UI silently let them oversell. No aggregate guard.

### Minor Observations

- The line-items table `Column expander` renders a chevron for *all* rows; `hasExpandableData` only gates the *content*, so single-unit rows show a non-functional chevron. Hide the expander when `!hasExpandableData`.
- Picker search API query (`VariantsController.php:103-109`) has a SQL-precedence bug: `whereHas(product...)->orWhere(identifier...)` then `->where('status','!=','archived')` binds status only to the `orWhere` branch, so archived products whose *name* matches still leak in. Backend issue, but it surfaces in this picker.
- `fetchVariantDetailsApi` exists in the composable but is unused in the create flow — dead path.
- The `Sale Unit` table column shows `×cf` only when `conversion_factor !== 1`; the base unit shows nothing, so the column reads "Unit" (the measurement-unit name) with no factor — fine, but inconsistent with the pill which always shows price.
- Detector's `#b45309` flag is a false positive; the real action is to document dark-mode status tints in DESIGN.md.

### Questions to Consider

- The sale unit is a *sellable line* with its own price and stock. Why is it rendered as an attribute of a product row instead of as the row itself?
- What would a POS operator at the counter (60-second sale) expect: tap a product once, or tap a product then tap a unit? Which is faster when the product has 3 units?
- If two line items of the same variant in different units both draw from one base stock, shouldn't the interface show one shared "remaining" number that both lines deplete?

### Decisions Locked

1. **Separate search result per unit** — each sale unit becomes its own row in the dropdown.
2. **Live shared per-variant ledger, per-row converted display** — each unit-row shows converted available that decreases as other unit-rows of the same variant are added/edited; oversell blocked before submit.

### Recommended Actions

1. **`/impeccable shape`** — Re-plan the picker as one row per sellable unit and the line-items table to draw from a shared per-variant base-stock ledger. Focus: picker row structure, table stock column semantics, unit-change affordance, keyboard Enter-add, expander removal.
2. **`/impeccable harden`** — Implement the live stock ledger (reactive running balance across line items per `product_variant_id`), per-row converted display, aggregate oversell prevention, and the dead-keyboard-Enter fix. Also fix the picker search SQL precedence bug and the `aria-activedescendant`/`role="option"` mismatch.
3. **`/impeccable clarify`** — Fix the inconsistent stock severity (base vs converted, warn lost on sale-unit pills): one unit-aware threshold rule, applied identically in picker and table. Add inline explanation of `×factor` for first-timers.
4. **`/impeccable polish`** — Final pass: hide non-functional expander chevrons on single-unit rows, remove the read-only "Available Sale Units" expander block, document the dark-mode status tints in DESIGN.md to clear the detector false positive, dead-code removal (`fetchVariantDetailsApi`).
