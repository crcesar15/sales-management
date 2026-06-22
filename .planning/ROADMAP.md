# Roadmap: Sales Management — Refactor & Completion

## Overview

This milestone delivers the Core Value loop — *a salesman or manager can complete an end-to-end sale through the POS and immediately see the result on the dashboard and reports* — by first repairing the existing financial core (tax, FIFO, cache, shift transitions, broken resources, missing authorization) and trimming the dead API layer, then layering three new surfaces on top: the POS interface (keystone — produces the `SalesOrder` rows the other surfaces consume), the manager dashboard (introduces the timezone-aware date helper reports reuse), and the reports suite (read-only aggregations + streamed CSV/PDF export). A final cross-cutting test phase locks confidence in the financial core end-to-end. Every phase is a delivery boundary: Phase 1 makes the existing code correct; Phases 2–3 make it consistent and small; Phases 4–6 deliver user-visible capability; Phase 7 verifies the loop closes with no manual workaround.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Critical Fixes & Refactor** - Repair the financial core (tax, cache, FIFO, shifts, resources, auth gaps, orderBy) before any feature lands
- [ ] **Phase 2: API Layer Removal** - Delete the unused API surface bottom-up, eliminating mass-assignment + missing-authorize bugs
- [ ] **Phase 3: Convention Alignment** - Bring the codebase to a consistent, lint-clean-above-baseline state before new modules
- [ ] **Phase 4: POS Module** - Full POS interface (keystone): cart, payments, shifts, hold/recall, receipt, checkout → SalesOrder + FIFO
- [ ] **Phase 5: Manager Dashboard** - KPI cards + charts + activity feed with a TZ-aware date helper reused by Reports
- [ ] **Phase 6: Reports Module** - Read-only sales/inventory/cash/purchases/profit-margin reports + streamed CSV/PDF export
- [ ] **Phase 7: Test Coverage** - Cross-cutting Pest validation of the financial core (SalesOrderService, FIFO, Batch, Pos, Dashboard, Report)

## Phase Details

### Phase 1: Critical Fixes & Refactor
**Goal**: The existing financial core behaves correctly — tax matches frontend and backend, settings cache works on the default driver, FIFO is consolidated, shifts have a state machine, resources serialize real data, and security gaps are closed — so every later feature is built on trustworthy services.
**Depends on**: Nothing (first phase)
**Requirements**: FIX-01, FIX-02, FIX-03, FIX-04, FIX-05, FIX-06, FIX-07, FIX-08, FIX-09, FIX-10, FIX-11, FIX-12, FIX-13, FIX-14, FIX-15, FIX-16, FIX-17, FIX-18, FIX-19, FIX-20, FIX-21, FIX-22
**Success Criteria** (what must be TRUE):
  1. A developer can create a sales order with `tax_rate=13` and the saved `tax_amount` matches `(subTotal - discount) * 13/100` exactly — frontend preview, backend, and a Pest test all agree
  2. `Setting::get('tax_rate')` returns the configured value under `CACHE_DRIVER=file` without throwing `BadMethodCallException`, and `Setting::set('tax_rate', 13)` followed by `get` returns `13.0` (no stale cache)
  3. Selling a `ProductVariant` to zero via POS-style deduction **and** transferring it to zero via `StockTransfer` both leave the batch `status='closed'`, and both throw `InvalidArgumentException` (not `RuntimeException`) on insufficient stock
  4. `CashRegisterShiftService::open()` / `close()` / `forceClose()` / `addMovement()` route every status change through a `TRANSITION_MAP` + `validateTransition()`, and a Pest test asserts each valid transition passes and each invalid one throws `InvalidArgumentException`
  5. `CashRegisterShiftResource` returns non-empty `movements` for a shift that has movements, `StockTransferResource` serializes all four date fields, and a product search via `useApi()` returns 200 (not 419) — the CSRF header bug is gone
**Plans**: 1 plan

Plans:
- [ ] 01-01-PLAN.md — Repair the financial core (FIX-01..FIX-22) across six waves: FIFO consolidation, shift state machine, settings cache, API auth/mass-assignment, frontend tax parity, and Pest verification

### Phase 2: API Layer Removal
**Goal**: The API surface is trimmed to only the ~10 endpoints actually called by Inertia composables, eliminating the mass-assignment and missing-`authorize()` bugs by deletion rather than patching, so no new feature is built on dead code.
**Depends on**: Phase 1
**Requirements**: API-01, API-02, API-03, API-04, API-05, API-06, API-07, API-08
**Success Criteria** (what must be TRUE):
  1. `routes/api.php` contains only the kept endpoints (variants.*, customers.search/find-by-tax-id/store, batches.available, activity-logs, settings, settings.update, permissions, vendors.variants) and `php artisan route:list --path=api` shows no orphaned names
  2. Every Inertia page renders without a `Class not found` error after API-only Resources/Form Requests/composables/types are deleted (verified by a smoke test hitting each page)
  3. Every remaining dynamic fetch (product search, customer search, batches available, POS session, activity logs, settings) returns 200 — no `route() is not defined` runtime error after `php artisan ziggy:generate` + `npm run type-check`
  4. `usePosClient` no longer references any `api.v1.pos.*` route — it uses web routes via Inertia `router` for shift open/close/session operations
**Plans**: TBD

### Phase 3: Convention Alignment
**Goal**: The codebase is consistent with `.claude/rules/*` — all models use `casts()` + `LogsActivity`, controllers narrow catches to `InvalidArgumentException`, TS errors are resolved, and a PHPStan baseline isolates new violations — so new modules inherit clean conventions.
**Depends on**: Phase 1, Phase 2
**Requirements**: CONV-01, CONV-02, CONV-03, CONV-04, CONV-05, CONV-06, CONV-07
**Success Criteria** (what must be TRUE):
  1. `ProductVariant` and `ProductVariantUnit` use a `casts()` method (not the `$casts` property), and the 7 previously-missing models expose `LogsActivity` with the standard `getActivitylogOptions()` (or a documented exclusion)
  2. `npm run type-check` passes clean (the 9 application TypeScript errors are fixed) and `npm run lint` passes
  3. Pest tests access shared properties via `uses()->property()` or `@property` PHPDoc (no dynamic `$this->service`/`$this->store`), removing ~464 of the 616 PHPStan errors
  4. `phpstan-baseline.neon` is committed and `phpstan.neon` includes it; `composer lint` reports only NEW violations above the baseline
  5. Forbidden-assertion tests in BrandTest, CategoryTest, MeasurementUnitTest, CatalogTest use `*Json()` variants and no longer write to `storage/logs/laravel.log`
**Plans**: TBD

### Phase 4: POS Module
**Goal**: A salesman can complete a full sale end-to-end through the POS — register/shift selection, product search + barcode scan, cart with discounts, split-tender payment, hold/recall, customer attach, receipt print, and checkout that produces a `SalesOrder` with FIFO stock deduction — using the fixed backend services as the single source of truth.
**Depends on**: Phase 1, Phase 2, Phase 3
**Requirements**: POS-01, POS-02, POS-03, POS-04, POS-05, POS-06, POS-07, POS-08, POS-09, POS-10, POS-11, POS-12, POS-13, POS-14, POS-15, POS-16, POS-17
**Success Criteria** (what must be TRUE):
  1. A salesman can complete a cash sale end-to-end (scan product → set qty → apply discount → enter cash payment) and the printed receipt's total matches the saved `SalesOrder.total` exactly, including tax from the `sales.tax_rate` setting
  2. Checkout creates exactly one `SalesOrder` (status `paid`) via `PosService` → `SalesOrderService::create()`; FIFO batches are deducted, sold-to-zero batches are `closed`, and a cash movement is recorded on the open shift for the cash portion
  3. A salesman can hold an order, complete a different sale that exhausts the held items' stock, recall the held order, and the system shows a clear "insufficient stock" error (not a 500) when attempting to pay — held orders do NOT reserve stock
  4. Split-tender (cash + card) payment is accepted when the sum matches the **taxed** total; payment-difference validation rejects submissions that don't cover tax, and the backend recomputes totals (client `total` is not trusted)
  5. `v-can` gates every POS action; a user without `POS_SALE` cannot reach the checkout route (403), and all POS user-visible text is translated in `en.json` + `es.json`
**Plans**: TBD
**UI hint**: yes

### Phase 5: Manager Dashboard
**Goal**: A manager opens the dashboard and sees today's KPIs (sales, transaction count, cash on hand, low-stock), a 7/30-day sales trend chart, a top-products chart, and recent activity — computed server-side in a single aggregation pass using a timezone-aware "today" that reports reuse.
**Depends on**: Phase 4
**Requirements**: DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DASH-06, DASH-07, DASH-08, DASH-09, DASH-10
**Success Criteria** (what must be TRUE):
  1. A manager viewing the dashboard sees KPI cards for today's sales total, today's transaction count, low-stock alert count, and cash on hand (open shifts sum) — each computed by a single `DashboardService` aggregate query (no `->get()->sum()`), with a short-TTL cache
  2. The "today's sales" KPI matches the cashier's shift-closing balance for a store in a non-UTC timezone (e.g. `America/La_Paz`) — a sale at 23:30 local appears in today's KPI, proving the TZ-aware date helper works
  3. PrimeVue `<Chart>` renders a sales-trend line (last 7/30 days) and a top-products bar (top N by revenue), sourced from `DashboardService` via Inertia lazy props and refreshable via `router.reload({ only: [...] })`
  4. A salesman without `DASHBOARD_VIEW` (or with a restricted scope decision) cannot reach the dashboard by direct URL (403); a manager with the permission sees all-store KPIs
  5. A sale completed in POS is visible on the dashboard after a manual refresh (Inertia partial reload) — no stale-forever cache
**Plans**: TBD
**UI hint**: yes

### Phase 6: Reports Module
**Goal**: A manager can run read-only reports — sales (by period/user/store/customer), inventory (levels/valuation/expiry), cash register (shifts + movements), purchases & receptions, top products, and profit margin (FIFO-batch-cost COGS) — with shared filters and streamed CSV/PDF export that doesn't exhaust memory or allow formula injection.
**Depends on**: Phase 5
**Requirements**: REP-01, REP-02, REP-03, REP-04, REP-05, REP-06, REP-07, REP-08, REP-09, REP-10, REP-11, REP-12, REP-13, REP-14, REP-15, REP-16, REP-17, REP-18
**Success Criteria** (what must be TRUE):
  1. A manager can open the sales report, filter by date range + store + user + customer, and the on-screen table matches the `SalesOrder` totals for that filter (TZ-aware inclusive end-date using the DASH-09 helper)
  2. Exporting the sales report as CSV streams rows via `->chunk()` + `response()->streamDownload()` — a 12-month / 50k-row export returns 200 without memory exhaustion, and a cell starting with `=`, `+`, `-`, or `@` is prefixed with `'` (formula-injection guard)
  3. The profit-margin report computes COGS from FIFO batch cost (joined `batches` → `reception_order_products`), documents the basis in the report footer, and the aggregate margin matches the dashboard total for the same period
  4. A salesman without the relevant `REPORTS_*` permission gets 403 on each report route; a manager with `REPORTS_VIEW` can access all report pages, and PDF export via `barryvdh/laravel-dompdf` renders without mid-row pagination breaks on a sample dataset
  5. `composer lint` and `npm run lint` / `npm run type-check` pass clean (modulo the Phase 3 PHPStan baseline) after the reports module lands
**Plans**: TBD
**UI hint**: yes

### Phase 7: Test Coverage
**Goal**: The financial core is covered by isolated Pest 3 tests — `SalesOrderService`, `FifoStockDeductionService`, `BatchService`, `PosService`, `DashboardService`, `ReportService` — closing the gaps left by per-phase verification and proving the Core Value loop works end-to-end with no manual workaround.
**Depends on**: Phase 6
**Requirements**: (no new requirements — this phase verifies FIX-01..22, POS-01..17, DASH-01..10, REP-01..18 end-to-end)
**Success Criteria** (what must be TRUE):
  1. `SalesOrderService::create()` with `status='paid'` produces a `SalesOrder` whose `total`, `tax_amount`, and `discount_amount` match the backend formula across a dataset of (no-discount, flat-discount, percentage-discount, tax=0, tax=13) cases
  2. `FifoStockDeductionService` deducts from oldest batch first, auto-closes a batch at `remaining_quantity=0`, and throws `InvalidArgumentException` on insufficient stock — for both a sale and a transfer (proving the Phase 1 consolidation held)
  3. A query-count test asserts the dashboard route fires ≤ N queries (single aggregate per KPI, no N+1) with 100 sales orders in the DB
  4. An export memory test creates 5,000 sales orders via the factory and asserts the sales CSV export route returns 200 without hitting the memory limit
  5. An end-to-end test exercises the Core Value loop: shift open → POS sale (cash) → shift close → dashboard KPI reflects the sale → sales report for the day matches — with no manual workaround
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Critical Fixes & Refactor | 0/TBD | Not started | - |
| 2. API Layer Removal | 0/TBD | Not started | - |
| 3. Convention Alignment | 0/TBD | Not started | - |
| 4. POS Module | 0/TBD | Not started | - |
| 5. Manager Dashboard | 0/TBD | Not started | - |
| 6. Reports Module | 0/TBD | Not started | - |
| 7. Test Coverage | 0/TBD | Not started | - |