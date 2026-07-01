---
target: resources/js/Pages/SalesOrders/Create/Index.vue + Components
total_score: 25
p0_count: 0
p1_count: 4
timestamp: 2026-07-01T03-49-31Z
slug: resources-js-pages-salesorders-create-index-vue
---
---
target: resources/js/Pages/SalesOrders/Create/Index.vue + Components
total_score: 25
p0_count: 0
p1_count: 4
p2_count: 5
p3_count: 4
---

# Critique: Sales Order Create Page + Components

Target: `resources/js/Pages/SalesOrders/Create/Index.vue` and its five Components (`CustomerSelect`, `SOProductPicker`, `SOLineItemsTable`, `SOFinancialSummary`, `SOPaymentsPanel`). Register: product (admin density, instrument-grade).

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Save button has no loading/disabled state; in-form validation errors set silently (no toast, no scroll, no focus); payment discrepancy shows two numbers in amber with no "short by X" callout |
| 2 | Match System / Real World | 3 | Domain language is clear and operator-native ("Available", "Line Total", "Walk-in") |
| 3 | User Control and Freedom | 3 | Back button, clear-customer, remove-item confirm all present; no undo after submit |
| 4 | Consistency and Standards | 3 | Follows project VeeValidate + PrimeVue patterns; page title `text-2xl` is one notch below DESIGN.md Display spec but consistent across the SalesOrders module |
| 5 | Error Prevention | 2 | Oversell prevention via live stock ledger is excellent; payment mismatch only caught at submit, customer-required-or-walk-in ambiguous |
| 6 | Recognition Rather Than Recall | 3 | Product picker is exemplary (ARIA combobox, live stock tags); walk-in option is invisible until after a customer is chosen |
| 7 | Flexibility and Efficiency | 2 | No keyboard shortcut for Save, no "Apply remaining" payment helper, no quick-fill for common amounts — friction for the under-60-second sale target |
| 8 | Aesthetic and Minimalist Design | 3 | Mostly clean instrument layout; discount shown twice (editable + read-only "Discount Applied"); two redundant clear-customer affordances |
| 9 | Error Recovery | 2 | Errors display near source but `text-red-400` on white fails 4.5:1 contrast; no focus/scroll to first invalid field on silent validation returns |
| 10 | Help and Documentation | 2 | Empty states teach well; no contextual help on tax/discount math, no explanation of the payment-equals-total rule until it fails |
| **Total** | | **25/40** | **Acceptable — significant improvements needed before users are happy** |

## Anti-Patterns Verdict

**Does this look AI-generated?** No. This is instrument-grade work. The `SOProductPicker` is the opposite of AI slop: a hand-built combobox with proper ARIA combobox semantics, `aria-activedescendant`, roving `data-active`, keyboard nav (ArrowUp/Down/Enter/Escape), live stock severity shared with the table via a real ledger, mobile/desktop row variants, and a `prefers-reduced-motion` block. The stock `Tag` always carries a label ("Available: 12", "Out of stock") — the Status-Pairs Rule is honored. The absolute bans (side-stripe, gradient text, hero-metric, kicker eyebrows, numbered scaffolding) are all absent. Color is restrained; Operational Navy appears only on the primary Save button. This passes the product slop test: a Linear/Figma-fluent operator would trust it.

**Deterministic scan** (`detect.mjs`): 1 advisory finding.
- `SOProductPicker.vue:753` — `#b45309` (amber-700) used in `.app-dark .so-option-warn` border. Outside DESIGN.md's named `status-warn: #f59e0b`. It is one step darker on the same hue ramp, used only in dark mode to keep the warm border visible — a legitimate tonal extension, but should be documented in DESIGN.md as the dark-mode warn-border token rather than left as drift. **Not a false positive; low severity.**

**Visual overlays**: not available — no dev server running, browser injection skipped. Findings are from source inspection.

## Overall Impression

The architecture is the strongest part of this page. The live stock ledger (`getRemainingBase` / `getRemainingBaseExcludingLine`) shared between picker and table is a real instrument-grade design — oversell is caught before submit, and a line's own Available ceiling stays stable while editing. The picker is genuinely well-built. Where it falls down is the **feedback layer**: the operator clicks Save and the system goes quiet. Validation failures inside `submit()` set a ref and `return` with no toast, no scroll, no focus. The payments mismatch is shown as two amber numbers with no explanation. Error text uses `text-red-400` which fails the 4.5:1 contrast the design system explicitly requires for tablet use under retail lighting. The single biggest opportunity: close the feedback gap and the page lifts from "acceptable" to "good" immediately.

## What's Working

1. **The stock ledger pattern.** `useStockLedger(lineItems)` shared between `SOProductPicker` and `SOLineItemsTable`, with `getRemainingBaseExcludingLine` so a line's own Available Tag doesn't flap as the operator edits its qty. This is the rare case where a UX decision (stable ceiling while typing) is implemented as a real data invariant, not a visual hack. It directly serves the "stock discrepancy under 2%" product goal.

2. **`SOProductPicker` accessibility.** Real combobox semantics (`role="combobox"`, `aria-expanded`, `aria-activedescendant`, `role="listbox"`/`role="option"`, `aria-selected`, `aria-disabled`), keyboard nav with `scrollIntoView`, outside-click close, request-id race protection on async search, 300ms debounce, and a `prefers-reduced-motion` block that kills the panel transition. This is the bar for the rest of the page.

3. **Status-pairs discipline.** Every stock state pairs color with a label (`getStockLabel` → "Available: N" / "Out of stock"). The picker's disabled-out-of-stock rows use `so-option-danger` (full bottom border, not a side-stripe) plus opacity plus the "Out of stock" tag — three carriers, color is the weakest. This is exactly what the design system demands.

## Priority Issues

### [P1] Silent in-form validation — Save click goes quiet
**Why it matters**: The `submit()` handler sets `storeError` / `itemsError` / `paymentsError` refs and `return`s with no toast, no scroll, no focus. The operator clicks Save, nothing visibly happens for a beat, then maybe a small red caption appears somewhere on the page. Under the 60-second-sale target this is the worst failure mode: the system goes silent at the exact moment the user expects confirmation. Heuristic 1 (Visibility) and 9 (Error Recovery) both fail here.
**Fix**: On every internal validation `return`, emit a toast (`severity: "warn"`, summary `t("Cannot save")`, detail = the specific reason) AND `nextTick(() => document.querySelector('.text-red-400, .p-invalid')?.scrollIntoView({ block: 'center' })`. Mirror the `onError` pattern already used for server errors. The contrast fix (below) makes the visible caption readable too.
**Suggested command**: `/impeccable harden`

### [P1] Error caption contrast fails 4.5:1 on light surface
**Why it matters**: `text-red-400 dark:text-red-300` is used on every error caption (`storeError`, `itemsError`, `paymentsError`, `errors.notes`, `searchError`). On the `surface-card-light` (`#ffffff`), `text-red-400` (`#f87171`) hits ~3.5:1 — below the 4.5:1 body-text floor DESIGN.md commits to for tablet use under bright retail light. The same pattern repeats with `text-amber-600` (`#d97706`, ~3.9:1) on the payments discrepancy line and `text-surface-400` (`#94a3b8`, ~3.7:1) on the "No customer (Walk-in)" hint and both empty states. This is the "muted gray for elegance" move the design system explicitly forbids.
**Fix**: Standardize on `text-red-500 dark:text-red-400` for error captions (red-500 `#ef4444` is the DESIGN.md `status-danger`, ~4.6:1 on white). For amber, `text-amber-700 dark:text-amber-400`. For muted hints, `text-surface-500 dark:text-surface-400` (the DESIGN.md Muted token `#64748b`, ~4.6:1). Sweep all four files.
**Suggested command**: `/impeccable polish`

### [P1] Payments discrepancy is shown but not explained
**Why it matters**: `SOPaymentsPanel` renders "Payment Total" vs "Order Total" in amber when they differ by >0.01, but never says *how much* they're off by. The operator sees `BOB 80` and `BOB 100` and has to do the math mid-transaction. The actual `paymentsError` ("Payments must equal order total") only fires at submit. For a sales rep with a customer waiting, this is friction; for an admin it's a small annoyance that compounds.
**Fix**: While `paymentsDifference > 0.01`, show a single inline callout: `⚠ Short BOB 20.00` (or `Over BOB X` when payments exceed total) with an amber icon + label, not amber text alone (Status-Pairs Rule). Replace the current two-line amber block. Optionally a one-tap "Apply remaining" button on each payment row that sets `amount = totalAmount - (sum of other rows)` — directly serves the 60-second target.
**Suggested command**: `/impeccable clarify`

### [P1] CustomerSelect has two redundant clear affordances and hides walk-in
**Why it matters**: When a customer is selected, the chip has an `fa-times` "Remove customer" button (line 232), AND below the chip a separate full "Walk-in" button appears (line 266, `v-if="modelValue"`) also with `fa-times`. Two buttons, same icon, same action, different labels — a consistency violation. Worse, *before* any customer is chosen, the only signal that walk-in is allowed is a `text-surface-400` line "No customer (Walk-in)" — and the operator can submit with `customer_id: null` anyway, so walk-in is both a hidden affordance and the default. The mental model is broken.
**Fix**: Pick one clear affordance. Drop the standalone "Walk-in" button; the chip's ✕ already clears. Before selection, render a single explicit `SelectButton` or secondary button labeled "Continue as Walk-in" next to the search row so the choice is visible and deliberate, not a side-effect of doing nothing. Make the walk-in state a named state (e.g. a small "Walk-in" chip replacing the customer chip), not the absence of one.
**Suggested command**: `/impeccable shape`

### [P2] Save button has no loading or disable-on-submit state
**Why it matters**: `router.post` is async; the user can double-click and fire two creates. Under the 60-second target the operator wants to *see* that the click registered. Heuristic 1.
**Fix**: Track a `submitting` ref, set it true before `router.post`, false in `onSuccess`/`onError`/finally. Bind `:loading="submitting"` and `:disabled="submitting"` on the Save button. PrimeVue `Button` supports both natively.
**Suggested command**: `/impeccable harden`

### [P2] `SOFinancialSummary` shows the discount twice
**Why it matters**: When `discountValue > 0`, the card renders both the editable "Discount" row (with the SelectButton + InputNumber) AND a separate read-only "Discount Applied" row showing `-BOB X (Y%)`. Two rows for the same fact is visual noise; the operator's eye has to reconcile them. Heuristic 8.
**Fix**: Collapse into one row. Keep the editable row; show the percentage-equivalent as a small inline muted hint *next to* the input when in amount mode (and the amount-equivalent when in percentage mode), not as a second row below.
**Suggested command**: `/impeccable distill`

### [P2] Payment rows don't wrap on narrow viewports
**Why it matters**: `SOPaymentsPanel` uses `flex items-end gap-3` for each row with three `flex-1` inputs + a delete button. Below ~640px (col-span-12 on mobile) this gets cramped and the labels collapse. The admin surface isn't the POS surface, but the same operator may use a tablet. Heuristic 7 (flexibility) and the responsive principle.
**Fix**: Wrap with `flex-wrap` and give the inputs `min-w-0` + basis widths (`basis-full sm:basis-auto`), or stack vertically under `sm:`. Ensure each label stays associated with its control.
**Suggested command**: `/impeccable adapt`

### [P2] No "Apply remaining" / quick-fill on payments
**Why it matters**: For a BOB 100 order with a BOB 60 cash payment, the operator must manually type `40` into the next row. The 60-second sale target rewards one-tap completion. Heuristic 7.
**Fix**: When `paymentsDifference > 0`, render a small "Apply remaining →" link/button on the last payment row that sets its `amount = totalAmount - paymentsTotal + currentAmount`. Single tap closes the loop.
**Suggested command**: `/impeccable delight`

### [P3] Page title `text-2xl` is below DESIGN.md Display spec
**Why it matters**: DESIGN.md commits Display at 2.5rem/40px (`text-4xl`-ish) for page titles, Headline at 2rem/32px (`text-3xl`) for section headings. `Create Sales Order` uses `text-2xl` (1.5rem/24px = the Title spec, reserved for card titles). This is consistent across the SalesOrders module (`Index.vue`, `Edit/Index.vue` all use `text-2xl`), so it's a module-wide drift, not a one-off bug. Low impact but worth a decision: either bump the module to spec or amend DESIGN.md to document the admin-page-title exception.
**Fix**: Decide once. Recommended: bump the three SalesOrders page titles to `text-3xl font-bold` to match Headline, leave Display for the dashboard only. Document in DESIGN.md.
**Suggested command**: `/impeccable typeset`

### [P3] `ConfirmDialog` mounted in parent, used in child
**Why it matters**: `Create/Index.vue` mounts `<ConfirmDialog />` but only `SOLineItemsTable` calls `confirm.require()`. Works because `ConfirmationService` is global, but the ownership is misleading — a future editor removing the "unused" dialog from the parent breaks the child. Minor maintenance hazard.
**Fix**: Move `<ConfirmDialog />` into `SOLineItemsTable.vue` (or into `AppLayout` once, project-wide).
**Suggested command**: `/impeccable polish`

### [P3] `SOProductPicker` listbox options use `tabindex="0"`
**Why it matters**: ARIA listbox pattern expects `tabindex="-1"` on options with roving tabindex managed by the combobox input; `tabindex="0"` on every option makes them all tab-stops, which breaks the expected single-tab-stop combobox model. The keyboard nav itself works (you handle Arrow keys), but a screen reader user tabbing through will hit every option linearly.
**Fix**: Set `:tabindex="rIndex === activeIndex ? 0 : -1"` (roving) or just `-1` and rely on the combobox's `aria-activedescendant` to communicate the active option.
**Suggested command**: `/impeccable audit`

### [P3] Discount percentage-equivalent is asymmetric
**Why it matters**: In amount mode you see `(Y%)` next to the applied discount; in percentage mode you don't see the equivalent amount. Minor symmetry issue.
**Fix**: Show the equivalent in both modes — `(BOB X)` in percentage mode, `(Y%)` in amount mode — as a muted inline hint on the same row.
**Suggested command**: `/impeccable polish`

## Persona Red Flags

**Alex (Power User / Admin)**: No keyboard shortcut for Save — must mouse to the top-right button every time. No "Apply remaining" on payments. No bulk line-item edit (can't multi-select rows to change price/qty). The picker's Enter-to-add is great; the rest of the page doesn't match that keyboard fluency. Alex will build the order fast then lose seconds on the payments math and the mouse-driven save.

**Sam (Accessibility-Dependent)**: Error captions fail 4.5:1 contrast in light mode (`text-red-400`, `text-amber-600`, `text-surface-400`). The combobox is solid, but the silent validation returns mean Sam's screen reader gets no announcement when `submit()` bails early — no `aria-live` region for the error refs. Tabbing through payment rows hits every picker listbox option because of `tabindex="0"`. Will struggle to confirm a save failure happened.

**Sales Rep (project persona, tablet at the counter)**: The 60-second target is the design constraint. The picker is fast (search → Enter → done). The friction is *after* the cart: typing the payment amount manually, no quick-fill, no "Apply remaining", and the discrepancy shown as two numbers without the diff. A rep who mis-types the amount has to read the amber line, do mental math, and re-type. Under queue pressure this is where the sale slips past 60s.

## Minor Observations

- `SOLineItemsTable` uses `border-t-2 border-surface-200` on the DataTable — a 2px top border where DESIGN.md's border vocabulary is 1px only. Not a side-stripe (it's a top divider, not a colored accent) but heavier than spec.
- `CustomerSelect.goToCustomerEdit` opens `window.open(url, "_blank")` — opens a new tab mid-flow; an admin editing the customer loses the order context. Consider `router.visit` instead.
- `SOProductPicker` duplicates the entire row markup for desktop/mobile (`so-row-desktop` / `so-row-mobile`). Maintenance cost, not a UX issue.
- `SOPaymentsPanel` has no upper bound on number of payment rows — an operator could add 20 and clutter the panel. A soft cap (e.g. 6) with a muted "max reached" hint would match the instrument tone.
- The `paymentsTotal` and `paymentsDifference` computeds in `SOPaymentsPanel` duplicate logic that also lives in `Create/Index.vue` `submit()`. Single-source this in the ledger/composable to avoid drift.

## Questions to Consider

- What if the Save button lived in a sticky footer bar with the live total always visible, instead of the top-right corner? The operator's eye stays on the number, the thumb stays on the button.
- Does walk-in need to be an *explicit* choice (a named state) rather than the default when you do nothing? What does the audit trail say a walk-in sale *is* today?
- The picker is keyboard-fluent; the rest of the page isn't. What if Tab/Enter through the whole order (store → customer → add item → qty → price → payment → save) were a documented fast path?
- The payment-equals-total rule is enforced but never taught. Could the panel show a one-line explainer the first time a mismatch appears, instead of just an error?
