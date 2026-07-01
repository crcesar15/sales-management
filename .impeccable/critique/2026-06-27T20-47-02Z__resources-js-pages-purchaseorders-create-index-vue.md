---
target: resources/js/Pages/PurchaseOrders/Create/Index.vue
total_score: 23
p0_count: 0
p1_count: 3
timestamp: 2026-06-27T20-47-02Z
slug: resources-js-pages-purchaseorders-create-index-vue
---
# Critique: PurchaseOrders/Create/Index.vue

## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | No loading state on Save (hardcoded `:loading="false"`); no submit-in-progress feedback; line-item table has no skeleton |
| 2 | Match System / Real World | 3 | Labels mostly natural; "Expected Arrival Date" reads well; "POFinancialSummary" section title "Summary" is generic |
| 3 | User Control and Freedom | 3 | Back button present; no Cancel/reset on the form; no undo after save; Esc doesn't cancel |
| 4 | Consistency and Standards | 3 | Mirrors sibling pages (Index.vue header pattern, Card sections); but Save button placement diverges from typical form-save-in-card pattern |
| 5 | Error Prevention | 2 | Discount max is enforced in POFinancialSummary but not validated client-side against subtotal in the schema; switching vendor silently wipes line items with no confirmation |
| 6 | Recognition Rather Than Recall | 3 | Vendor info popover is good; but selected vendor name is not visible once the Select closes — relies on memory; line-item expandable details are collapsed by default |
| 7 | Flexibility and Efficiency of Use | 1 | No keyboard shortcuts; no "Save & New"; no batch quantity edit; no tab-to-next-field flow verification; no Enter-to-submit |
| 8 | Aesthetic and Minimalist Design | 3 | Clean two-column layout; cards are the lazy answer per design system but justified for form sections; header bar is slightly cramped |
| 9 | Error Recovery | 2 | Field errors only show after `submitCount > 0`; line-items error is a plain `<small>` with no recovery guidance; discount over-max not prevented clearly |
| 10 | Help and Documentation | 1 | No tooltips on fields; no inline help for discount mode; no guidance for the vendor-switch-wipes-items behavior |
| **Total** | | **23/40** | **Acceptable — significant improvements needed** |

## Anti-Patterns Verdict

**Does this look AI-generated?** No — this reads as a competent hand-built PrimeVue form that follows the project's DESIGN.md conventions (Lato, Operational Navy restraint, flat cards, 1px borders, sentence-case labels). It avoids the gradient-text, hero-metric, eyebrow-kicker, and glassmorphism tells. The two-column form-with-summary layout is standard ERP/admin territory, not AI slop.

**Deterministic scan:** `detect.mjs` returned exit 0 with an empty findings array across the three component files. No banned patterns (side-stripe borders, gradient text, glassmorphism, hero-metric template, eyebrow kickers, numbered section markers) were detected. This is a clean bill on the absolute bans — the issues are in UX behavior, not visual slop.

**Visual overlays:** Dev server is not running, so no browser injection was performed. No user-visible overlay is available for this run. Findings are from source review.

## Overall Impression

A solid, conventionally-structured PO create form that gets the visual system right but stops short on interaction craft. The biggest opportunity: this is a high-frequency data-entry surface for an operator who wants to finish fast, and it currently offers zero accelerators — no keyboard submit, no loading state on Save, no "Save & New", and silent destructive behavior when switching vendors wipes the line-item list. The visual layer is restrained and on-brand; the behavioral layer is where it loses points.

## What's Working

1. **Vendor info popover** (Index.vue:170-201) — co-locating contact details behind an eye icon next to the Select is exactly the right recognition-over-recall move. The icon+label rows inside are clean and the `border-t` divider for additional contacts is well-judged.
2. **Discount amount/percentage toggle** (POFinancialSummary.vue:78-99) — the `SelectButton` switching between currency and `%` with a computed `discountMax` is a genuinely good control. It solves a real ambiguity (discounts are often thought of as percentages but stored as amounts) without extra fields.
3. **Two-column layout with sticky-ish financial summary** (Index.vue:134-251) — the 8/4 grid keeps the summary visible alongside the line items. The summary updating live as quantities/prices change is the right feedback loop.

## Priority Issues

- **[P1] Save button has no loading state**: `:loading="false"` is hardcoded at Index.vue:129. When the user clicks Save and `router.post` is in flight, there is no visual indication the save is processing — the button stays clickable and a second click can fire a duplicate request. This is the single most impactful bug on the page.
  - *Why it matters:* duplicate POs in an inventory system create real reconciliation work; the operator loses trust in the "did it save?" moment.
  - *Fix:* introduce a `submitting` ref, set it true at the start of `submit`, clear it in both `onSuccess` and `onError`/`finally`, and bind `:loading="submitting"`. Also disable the back button while submitting.
  - *Suggested command:* `/impeccable harden`

- **[P1] Switching vendor silently wipes all line items**: POLineItemsTable.vue:205-215 watches `vendorId` and emits an empty array whenever it changes and items exist. No confirmation, no toast, no undo. A user who picks the wrong vendor and corrects it loses an entire built cart.
  - *Why it matters:* building a 15-line PO takes minutes; silently destroying it on a misclick is a trust-breaking moment at exactly the wrong time.
  - *Fix:* before clearing, `confirm.require(...)` with a message like "Changing the vendor will remove all added items. Continue?" Only clear on accept. Also consider preserving items that exist in both vendors' catalogs.
  - *Suggested command:* `/impeccable harden`

- **[P1] No keyboard path for the primary action**: There's no `type="submit"` form wrapper, no `@keyup.enter` handler, and no global save shortcut. The only way to save is to mouse-click the header button. On a desktop admin surface this is a significant efficiency gap for the Admin persona.
  - *Why it matters:* the DESIGN.md "Speed is a feature" principle and the product's own success criteria reward keyboard-first flows; this form demands the mouse for its most important action.
  - *Fix:* wrap the form in a `<form @submit.prevent="submit">` and make the Save button `type="submit"`, or bind a `@keyup.enter` on the root. Consider a `Cmd/Ctrl+S` global shortcut for power users.
  - *Suggested command:* `/impeccable harden`

- **[P2] Field errors hidden until after first submit**: every validation error is gated behind `submitCount > 0` (Index.vue:154, 159, 214, 216; POFinancialSummary.vue:116, 119). A user who tabs through leaving the vendor empty sees no feedback until they click Save and the whole form lights up at once.
  - *Why it matters:* error prevention should be progressive, not punitive. Showing validation as fields lose focus catches mistakes earlier and reduces the "wall of red errors" moment.
  - *Fix:* use vee-validate's per-field `meta.touched`/`errors` instead of gating on `submitCount`. Show errors when a field is touched AND invalid. Keep `submitCount` only for the toast + focus-first-invalid fallback.
  - *Suggested command:* `/impeccable polish`

- **[P2] Empty line-items state doesn't teach**: POLineItemsTable.vue:342-349 shows an icon + "No items added yet" + "Use the search above to add products". It's adequate but doesn't teach the interface — no affordance hint that the search is vendor-gated, no example, no "recently used" shortcut.
  - *Why it matters:* DESIGN.md "Empty states that teach the interface, not 'nothing here'" — this is borderline "nothing here" with one extra line.
  - *Fix:* when a vendor IS selected, show a more actionable empty state: a subtle callout pointing at the search field with a sample query, or surface the vendor's most-ordered variants as one-click add chips. When no vendor is selected, the current "select a vendor first" message is correct.
  - *Suggested command:* `/impeccable onboard`

## Persona Red Flags

**Alex (Power User / Admin persona)**: This is the primary user. Red flags:
- No keyboard shortcut for Save — the single most-used action requires the mouse every time.
- No "Save & New" for users entering multiple POs in a session — each save bounces to the list page, forcing a re-navigate to create the next one.
- Quantity column uses `InputNumber` with `show-buttons` — the +/- steppers are slow for power users who want to type and Tab. The `step="1"` default also fights decimal quantities.
- No way to duplicate a line item or set a quantity across selected rows.
- Discount field has no keyboard-accessible mode toggle hint beyond the SelectButton.

**Jordan (First-Timer)**: Red flags:
- The vendor Select shows only vendor names in the dropdown — no secondary info (email/city) to disambiguate two similar names. The popover helps only AFTER selection.
- "Discount" with the amount/percentage toggle has no tooltip explaining the difference or which the system will store.
- Switching vendors wipes items silently — a first-timer will not expect this and will assume a bug.
- No confirmation toast or visible "Saved" state after a successful create — only a toast that disappears in 3s; if they look away they won't know it worked.

**Sam (Accessibility-dependent)**: Red flags:
- The vendor "eye" button next to the Select has `v-tooltip.top` but no `aria-label`; screen readers get an unlabeled icon button.
- The `itemsError` message at Index.vue:231 is a plain `<small>` with no `role="alert"` or `aria-live` — it won't be announced when it appears.
- Line items table action buttons (POLineItemsTable.vue:418-425) rely on `v-tooltip` for labels; the buttons themselves are icon-only with no accessible name.
- The discount `SelectButton` mode toggle doesn't expose its current mode via `aria-pressed` semantics to AT (PrimeVue may handle this, but worth verifying).
- Focus management after submit error: `nextTick(() => document.querySelector('.p-invalid')?.focus())` is fragile — if the first invalid field is inside the line-items table it may not be the right target.

## Minor Observations

- **Header Save button** is in the top-right (Index.vue:129) while the financial summary — the visual "totals" — is bottom-right. The action and its consequence are spatially separated. Consider a second Save button in/under the summary card for long line-item lists.
- **`:loading="false"`** is the only obviously-wrong hardcoded prop; scan for others.
- **`goBack()`** uses `router.visit(route("purchase-orders"))` rather than `router.back()` — if the user navigated here from a filtered list, they lose their filter context. Minor, but breaks the "back to safety" expectation.
- **Popover width** `w-72` (Index.vue:171) is fixed; a vendor with a long address or several contacts may overflow or truncate. Consider `max-w` with wrapping.
- **`discountAttrs`** are passed through but the discount `InputNumber` in POFinancialSummary doesn't bind `p-invalid` based on errors — discount validation errors won't show on the field itself, only in the (absent) error slot since there's no `<small>` for discount errors in the summary.
- **`formatCurrency(String(...))`** is called repeatedly with stringified numbers — a minor type smell; the formatter should accept numbers.
- **`ConfirmDialog`** is rendered (Index.vue:132) but no `confirm.require` is called from this page — it's only used by the child table. If unused here, remove it; if intended, wire it up.
- **No `v-can` on the Save button** — authorization is assumed from the route. If a user without `purchase_order.create` somehow lands here, the form is fully interactive but the submit will 403. Consider hiding/disabling Save via `v-can`.
- **`border-t-2`** on the line items DataTable (POLineItemsTable.vue:336) is a 2px top border — the design system specifies 1px borders only. Minor but off-spec.

## Questions to Consider

- This is a create form for a high-frequency admin task. Should it optimize for "enter many POs fast" (Save & New, keyboard flow, recent-vendor shortcut) or for "enter one PO carefully" (validation, confirmation)? The current design is neither fully.
- The vendor switch silently wipes items. Is that a safety feature (preventing cross-vendor items on one PO) or a footgun? If safety, it deserves a confirmation; if footgun, it deserves preservation.
- The financial summary is in a separate card. What if it were a sticky footer bar on long lists, so the total is always visible while scrolling 30 line items?
- Could the "Add Product" search surface the vendor's most-ordered variants as zero-type chips, turning a search task into a one-click task for repeat orders?
- The discount toggle is good. What other fields would benefit from dual-mode input (e.g., quantity as "units" vs "cases" using the purchase unit conversion factor)?

---

> **Trend for `resources-js-pages-purchaseorders-create-index-vue` (last 5 runs): 23**
> First run for this target, no trend yet.
