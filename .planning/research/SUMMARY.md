# Project Research Summary

**Project:** Sales Management — Refactor & Completion (POS + Dashboard + Reports)
**Domain:** Retail sales management (Laravel 12 + Inertia.js + Vue 3 monolith)
**Researched:** 2026-06-21
**Confidence:** HIGH

## Executive Summary

This milestone adds three new surfaces — a POS interface, a manager dashboard, and a standard reports suite — onto an existing Laravel 12 + Inertia.js v1/v2 + Vue 3 + PrimeVue 4 + Tailwind 3 sales-management application. The existing app already has the data model (`SalesOrder`, `SalesOrderItem`, `SalesOrderPayment`, `CashRegisterShift`, `Batch` FIFO, `ProductVariant`, `Customer`, `PurchaseOrder`, `ReceptionOrder`) and the core services (`SalesOrderService`, `CashRegisterShiftService`, `FifoStockDeductionService`, `StockAlertService`). The research confirms that the right approach is **not greenfield**: the POS is a thin orchestrator over `SalesOrderService::create()`, the dashboard reuses existing aggregate-able models via a new `DashboardService`, and reports are read-only aggregations in a new `ReportService`. Experts build retail POS/dashboards/reports as a server-side-owns-truth layer over an existing financial core — the cart is ephemeral client state, totals are recomputed server-side, exports are streamed, and reports never write.

The recommended stack additions are minimal and high-confidence: `barryvdh/laravel-dompdf ^3.1` for PDF exports, `spatie/simple-excel ^3.10` for streamed CSV, and the already-installed PrimeVue `<Chart>` (Chart.js 4.5.1) for dashboard charts. Barcode scanning needs no library (hardware scanners are HID keyboard wedges → a focused PrimeVue `InputText` captures the scan). Receipt printing is browser `window.print()` + `@media print` CSS for the MVP, with `mike42/escpos-php ^4.0` as a gated follow-up for networked thermal printers. No new npm packages are required.

The dominant risk is **building new features on top of broken existing code**. The codebase carries a frontend/backend tax-calculation drift, a `Cache::tags()` call that crashes on the default `file` driver, two divergent FIFO implementations, a broken `CashRegisterShiftResource` that silently drops movements, a missing `TRANSITION_MAP` on shifts, mass-assignment + missing-authorization gaps in 7 API controllers, and 616 PHPStan errors with no baseline. Every one of these is a prerequisite blocker for at least one new feature. The research is unambiguous: a **critical-fixes-and-refactor phase must precede feature work**, and the fixes must be verified with tests before POS/Dashboard/Reports are built on top of them.

## Key Findings

### Recommended Stack

The existing stack (Laravel 12, Inertia v1/v2, Vue 3.5, PrimeVue 4.5.5, Tailwind 3.4, Pest 3, Spatie permission/activitylog/medialibrary, Chart.js 4.5.1, Pinia 3, axios) is sufficient for all three new surfaces. Only two composer packages are added (plus one gated optional). The frontend bundle gains **zero** new dependencies. See `STACK.md` for the full alternatives-rejected analysis.

**Core technologies (additions):**
- `barryvdh/laravel-dompdf ^3.1` — PDF report export — Laravel-native `Pdf` facade, pure-PHP (no system binary on a PHP-only server), adequate CSS for tabular reports.
- `spatie/simple-excel ^3.10` — streamed CSV export — memory-efficient row-by-row writer, Spatie-ecosystem consistency, one API for future xlsx.
- `mike42/escpos-php ^4.0` (gated) — networked thermal receipt printing + cash-drawer kick — only when deployment confirms a networked thermal printer.
- PrimeVue `<Chart>` + Chart.js 4.5.1 (already installed) — dashboard line/doughnut/bar charts — already used in two existing pages; no bundle cost.
- Native focused `<InputText>` + keyboard-wedge handler (`useBarcodeScan` composable) — barcode scan entry — hardware scanners are HID keyboards; zero dependencies.
- `App\Support\MoneyFormatter` + PHP `NumberFormatter` (intl) — backend currency formatting for PDFs — matches the existing frontend `useCurrencyFormatter` without adopting a money-math library.

### Expected Features

The milestone's Core Value is *"A complete sale (POS → shift close → dashboard KPI → sales report) works end-to-end with no manual workaround."* All P1 features below must ship together to deliver that loop. See `FEATURES.md` for the full 19+10+18 feature breakdown and the prioritization matrix.

**Must have (table stakes — P1, this milestone):**
- POS: register/shift selection, shift open/close/force-close, cash movements, product search + barcode scan, cart with per-line + order-level discounts, tax from settings, split-tender payment, change calc, customer attach, hold/recall, receipt print, **checkout → SalesOrder + FIFO deduction (keystone)**, returns/refunds, quick keys.
- Dashboard: today's sales / tx count / avg basket / low-stock / cash-on-hand KPI cards, sales-trend (7/30d) line chart, top-products bar chart, recent activity feed, time-range + store filters.
- Reports: sales by period/user/store/customer, inventory levels/valuation/expiry, cash-register Z-report, purchases & receptions, top products, profit margin, tax summary, returns, stock-movement log, shared filters, CSV + PDF + Excel export.
- New `PermissionsEnum` cases: `POS_SALE`, `POS_RETURNS`, `DASHBOARD_VIEW`, `REPORTS_VIEW`, `REPORTS_SALES`, `REPORTS_INVENTORY`, `REPORTS_CASH`, `REPORTS_PURCHASES` (+ `PermissionSeeder` re-run).

**Should have (competitive — P2, after validation):** partial payment, on-screen keypad, dashboard live refresh (polling), cashier comparison chart, receipt customization, email/SMS receipt, dashboard PDF snapshot.

**Defer (v2+ — P3):** offline PWA mode, loyalty program, gift cards, ABC analysis, slow-mover detection, predictive reorder.

**Anti-features (explicitly out of scope):** table service / kitchen tickets, payroll/scheduling, full accounting/ledger, CRM/marketing campaigns, e-commerce sync, native mobile app, real-time-everything WebSockets, custom report builder, multi-currency/FX.

### Architecture Approach

The architecture reuses the existing module pattern (Web Controller → Service → Model, Inertia render, `PermissionsEnum` authorization, Form Request validation, Eloquent Resources) and adds three new services as thin orchestration/read-only aggregation layers. POS cart state lives in the existing Pinia `usePosStore` (ephemeral, persisted to sessionStorage); the server is the sole source of truth for totals via `SalesOrderService::calculateTotals()`. Dashboard and reports use **Inertia lazy props + `router.reload({ only: [...] })`** for data — no new API endpoints (the project is actively removing its API layer). Exports are server-side streamed responses (`response()->streamDownload()` for CSV, `Pdf::loadView()->download()` for PDF), triggered by plain browser navigation, never Inertia `router.visit()`. See `ARCHITECTURE.md` for the full data-flow diagrams, the API-layer safe-deletion order, and the build-order dependency graph.

**Major components:**
1. `PosController` (expand) + `PosService` (new) — thin orchestrator: validates shift open → builds payload → calls `SalesOrderService::create(status: paid)` → records cash movement. No totals/stock logic duplication.
2. `HomeController` (expand) + `DashboardService` (new) — KPI aggregation via single `->sum()`/`->count()`/`->selectRaw()` queries + short-TTL cache; reuses `StockAlertService`.
3. `ReportController` (new) + `ReportService` (new) — read-only aggregate SQL by dimension (period/user/store/customer/category); streamed CSV/PDF export generators.
4. `usePosStore` (expand) — cart, discounts, payments in Pinia; `usePosClient` refactored from non-existent `api.v1.pos.*` routes to web routes via Inertia `router`.
5. `useReportClient` (new) — `window.location.href` export trigger (not Inertia).
6. `useBarcodeScan` (new composable) — focused-input + Enter-handler for hardware scanners.
7. `App\Support\MoneyFormatter` (new) — backend `NumberFormatter` for PDF currency strings.

### Critical Pitfalls

The top pitfalls (see `PITFALLS.md` for all 18 + recovery strategies + phase mapping). Every critical pitfall is a **prerequisite fix**, not a feature-phase task:

1. **Tax frontend/backend drift** — the existing sales-order create page hardcodes `taxRate = 0` and doesn't divide by 100; POS will copy this bug. **Avoid:** fix in both existing pages + add a shared `useOrderTotals` composable + Pest test with `tax_rate=13`, all in the critical-fix phase before POS.
2. **`Cache::tags()` on the file driver** — `Setting::get()` crashes `BadMethodCallException` on the default `CACHE_DRIVER=file`; POS reads `tax_rate` on every sale. **Avoid:** replace with `Cache::rememberForever("settings.{$key}")` + per-key `Cache::forget` + versioned keys; `php artisan cache:clear` once; test with `file` driver.
3. **Duplicate FIFO logic divergence** — `FifoStockDeductionService` (sales) doesn't auto-close batches; `BatchService::deductFIFO*` (transfers) does, and throws `RuntimeException` instead of `InvalidArgumentException`. **Avoid:** consolidate to one canonical path, standardize on `InvalidArgumentException`, auto-close at `remaining_quantity=0`; unit-test both sell-to-zero and transfer-to-zero.
4. **`CashRegisterShiftResource` silently drops movements** — `relationLoaded()` called on a `JsonResource` (not the model); movements always serialize as `[]`. **Avoid:** fix to `$this->whenLoaded('movements', ...)` before any POS shift UI or cash-register report.
5. **Missing `TRANSITION_MAP` on `CashRegisterShiftService`** — inline status checks scattered; adding the map while POS is live is "changing the engine while driving." **Avoid:** add `TRANSITION_MAP` matching existing inline rules exactly (`open → closed/forced_close`), with per-transition Pest tests, before POS wiring.
6. **API-layer deletion orphans shared Resources / Ziggy routes** — 24 Resource directories, many used by both web and API controllers; deleting by directory name breaks Inertia renders. **Avoid:** delete bottom-up (routes → API controllers → API requests → API-only Resources → composables → types), grepping `app/Http/Controllers/` (non-Api) at each step; regenerate Ziggy + `npm run type-check` after.
7. **CSV formula injection + memory exhaustion on large exports** — `fputcsv` doesn't escape `=`/`+`/`-`/`@` prefixes; `->get()` on a 12-month range exhausts memory. **Avoid:** route every CSV cell through a sanitizing helper; use `->chunk(500)` + `response()->streamDownload()` from day one of the reports phase.
8. **Dashboard N+1 + timezone drift** — naive `->get()->sum()` fires 100+ queries; `today()` uses server UTC while the store is in La Paz. **Avoid:** build `DashboardService` with single aggregate queries + `Cache::remember(..., 60)`; introduce a TZ-aware date helper (`Carbon::parse($date, $tz)->startOfDay()->utc()`) reused by reports.
9. **Missing permissions → silent 403 or unguarded pages** — read-only pages are easy to forget authorizing; `salesman` could see all-store financials. **Avoid:** add `PermissionsEnum` cases first, re-seed, authorize every route, Pest-test 403 for `salesman` + 200 for admin.
10. **616 PHPStan errors with no baseline** — new violations are invisible during feature work. **Avoid:** generate `phpstan-baseline.neon` as the first task of the critical-fix phase; shrink it manually as fixes land.

## Implications for Roadmap

Based on the combined research, the milestone should be structured as **7 phases** following a strict fixes-before-features dependency graph (validated in `ARCHITECTURE.md` §Build Order and `PITFALLS.md` §Pitfall-to-Phase Mapping).

### Phase 1: Critical Fixes & Refactor
**Rationale:** Every new feature depends on the correctness of existing services. Tax drift makes POS unusable; `Cache::tags` crashes on the default driver; broken resources drop data; divergent FIFO corrupts reports; missing authorization is a security hole. Building on top of these guarantees compound bugs. This phase must complete and be test-verified before any feature work.
**Delivers:** A correct, convention-aligned, lint-clean codebase with a PHPStan baseline, a trimmed API layer, consolidated FIFO, fixed tax/cache/resources/shift-transitions/CSRF, `SORT_COLUMN_MAP` whitelists, and `LogsActivity` on all models.
**Addresses:** All CONCERNS.md blockers (tax, Cache::tags, FIFO, TRANSITION_MAP, resources, mass-assignment, missing-authorize, orderBy, CSRF, casts, LogsActivity).
**Avoids:** Pitfalls 1–5, 11–13, 18 (and the security mistakes table).
**Uses:** No new stack elements — pure refactoring of existing code + PHPStan baseline generation.

### Phase 2: API Layer Removal
**Rationale:** Removes the attack surface (mass-assignment, missing `authorize()` in 5 controllers) by deletion rather than patching, reduces convention-alignment workload, and must happen before new features so no feature is built on dead code. Depends on Phase 1 (CSRF fix, `SORT_COLUMN_MAP`).
**Delivers:** `routes/api.php` trimmed to the ~10 endpoints actually used by composables; 8 API controllers + their Form Requests deleted; `Api/VendorsController` trimmed to `getProductVariants`; orphaned composables removed; Ziggy regenerated.
**Addresses:** Anti-pattern 3 (no new API endpoints); sets up the "Inertia partial-reload over new API" constraint for Dashboard/Reports.
**Avoids:** Pitfalls 11 (orphaned Resource → 500), 12 (orphaned Ziggy route → silent 404), 13 (CSRF mis-fix → 419).

### Phase 3: Convention Alignment
**Rationale:** Brings the codebase to a consistent state (Pint/PHPStan-clean above baseline, TS errors resolved, `casts()` method, `LogsActivity` trait, narrowed catches) so new modules don't inherit inconsistency. Can partially overlap Phase 1/2 but is safest as its own gate before feature phases.
**Delivers:** All 28+ models on `casts()` + `LogsActivity`; 12 controllers with narrowed `catch (InvalidArgumentException)`; 9 TS errors fixed; `AuthServiceProvider` cleaned.
**Avoids:** Convention-violation pitfalls (14 — VeeValidate mandate, 15 — permissions process) being replicated in new code.

### Phase 4: POS Module
**Rationale:** POS-16 (checkout) is the **keystone** — Dashboard and Reports both depend on completed `SalesOrder` rows existing. POS must come before the surfaces that consume its output. Depends on Phase 1 (tax, shifts, FIFO, cache) and Phase 2 (`usePosClient` refactored to web routes).
**Delivers:** Full POS UI (PosLayout): register/shift selection, product search + barcode scan, cart with per-line + order discounts, split-tender payment, change calc, customer attach, hold/recall, quick keys, receipt print (browser), returns/refunds, checkout → `SalesOrder` + FIFO deduction + cash movement.
**Addresses:** POS-1 through POS-19 (P1 subset), new `POS_SALE`/`POS_RETURNS` permissions.
**Avoids:** Pitfalls 1 (tax — already fixed, but POS must use the shared composable), 14 (VeeValidate not Inertia `useForm` for cart), 15 (permissions seeded first), 16 (receipt: HTML + `@media print` 80mm, no silent print), 17 (hold/recall: availability pre-check, no stock reservation).
**Uses:** Existing `usePosStore` (Pinia) expanded; new `useBarcodeScan` composable; `PosService` thin orchestrator; `StoreSaleRequest` Form Request; `PosSaleResource`; no new npm deps.

### Phase 5: Manager Dashboard
**Rationale:** Depends on Phase 4 (POS producing completed sales) and Phase 1 (correct totals, cache). KPIs are aggregations over `SalesOrder` — meaningless without real sales data. Introduces the TZ-aware date helper that Reports will reuse.
**Delivers:** `DashboardService` + expanded `HomeController` with Inertia lazy props; KPI cards (today's sales, tx count, avg basket, low-stock, cash on hand); PrimeVue `<Chart>` sales-trend line + top-products bar; recent activity feed; time-range + store filters; short-TTL cache; `router.reload` polling.
**Addresses:** DASH-1 through DASH-10, new `DASHBOARD_VIEW` permission.
**Avoids:** Pitfalls 6 (N+1 — single aggregate queries + cache), 7 (timezone drift — TZ-aware helper), 15 (permissions).
**Uses:** PrimeVue `<Chart>` + Chart.js 4.5.1 (installed); Inertia lazy props + `router.reload`.

### Phase 6: Reports Module
**Rationale:** Depends on Phase 1 (`SORT_COLUMN_MAP`, fixed resources, FIFO-consistent batch status), Phase 4 (sales + returns data), and Phase 5 (TZ-aware date helper). Reports are read-only — the safest phase, but the one with the most surface area (18 reports + 3 export formats).
**Delivers:** `ReportController` + `ReportService` + 4 Form Requests; report landing + sales/inventory/cash-register/purchases pages; shared `ReportFilters` + `ExportButtons` Vue components; streamed CSV (`spatie/simple-excel`) + PDF (`barryvdh/laravel-dompdf`) + Excel export; CSV sanitizing helper; `useReportClient` composable; `App\Support\MoneyFormatter`.
**Addresses:** REP-1 through REP-18, new `REPORTS_*` permissions.
**Avoids:** Pitfalls 8 (CSV injection — sanitizing helper), 9 (memory exhaustion — `->chunk` + streamed response), 10 (in-flight sale reads — read-only transaction or `as_of` stamp + documented COGS basis), 15 (permissions — 403 for salesman).
**Uses:** `barryvdh/laravel-dompdf ^3.1`, `spatie/simple-excel ^3.10` (new composer); Blade report views; `response()->streamDownload()`.

### Phase 7: Test Coverage
**Rationale:** Validates the financial core after all fixes and features are in place. Pest 3 feature tests for `SalesOrderService`, `FifoStockDeductionService`, `CashRegisterShiftService`, `PosService`, `DashboardService`, `ReportService`; query-count tests for dashboard; export memory tests; authorization 403/200 tests; TZ conversion tests.
**Delivers:** Confidence that the Core Value loop works end-to-end with no manual workaround.
**Avoids:** All "looks done but isn't" checklist items in `PITFALLS.md`.

### Phase Ordering Rationale

- **Fixes before features:** The dependency graph in `ARCHITECTURE.md` §Build Order proves every feature reads from a service that is currently broken. Tax/CACHE/FIFO/resources/TRANSITION_MAP are non-negotiable prerequisites.
- **API removal before features:** No feature should be built on the API layer that's being deleted; `usePosClient` must be refactored to web routes before POS expands it.
- **POS before Dashboard/Reports:** POS-16 checkout is the keystone — Dashboard KPIs and Reports aggregations are meaningless without completed `SalesOrder` rows. Reports additionally depend on returns data (POS-17).
- **Dashboard before Reports:** Dashboard introduces the TZ-aware date helper that Reports reuses; Dashboard is also smaller and validates the `DashboardService` aggregation pattern that `ReportService` mirrors.
- **Tests last (but per-phase verification throughout):** Each phase must ship its own verification tests for the pitfalls it prevents; Phase 7 is the cross-cutting financial-core validation.

### Research Flags

Phases likely needing deeper research during planning (`/gsd-plan-phase --research-phase`):
- **Phase 4 (POS):** Complex — cart state semantics, hold/recall stock-availability policy, receipt print CSS for 80mm thermal, returns/refund flow design (new `SalesOrderReturnType` or invert existing), `usePosClient` web-route refactor. Highest uncertainty in the milestone.
- **Phase 6 (Reports):** Wide surface — 18 reports, profit-margin COGS basis decision (FIFO batch cost vs `purchase_price`), CSV sanitization helper design, large-export streaming strategy, read-only transaction strategy for in-flight-sale consistency.
- **Phase 1 (Critical Fixes):** FIFO consolidation has a real design decision (which service owns the canonical path, whether `BatchService::deductFIFO*` delegates or is deleted); `Cache::tags` replacement has a versioned-key vs prefix-flush choice.

Phases with standard patterns (skip research-phase):
- **Phase 2 (API Removal):** Mechanical deletion with grep verification — well-documented in `ARCHITECTURE.md` §Safe Deletion Order.
- **Phase 3 (Convention Alignment):** Direct application of `.claude/rules/*` — no novel decisions.
- **Phase 5 (Dashboard):** Standard aggregate-query + PrimeVue Chart pattern; two existing chart pages to mirror.
- **Phase 7 (Tests):** Pest 3 conventions are fully documented in `.claude/rules/testing.md`.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All recommendations verified against installed versions + packagist/npm + official PrimeVue/dompdf/escpos docs. Two composer adds + one gated; zero npm adds. Alternatives-rejected table grounded in bundle-size + deployment constraints. |
| Features | HIGH | Grounded in the verified existing data model (`SalesOrder`, `SalesOrderItem`, `CashRegisterShift`, `Batch`, `ProductVariant`) + retail POS category norms (Lightspeed/Shopify/Square/Loyverse). Schema gap (no per-line discount column) explicitly flagged. |
| Architecture | HIGH | Grounded in direct reads of existing controllers/services/composables + Inertia v1 docs + Laravel 12 docs. Build-order dependency graph validated against CONCERNS.md. API safe-deletion order derived from grep analysis of actual Resource usage. |
| Pitfalls | HIGH | All 18 pitfalls grounded in direct file reads (line numbers cited) + CONCERNS.md audit + OWASP CSV-injection guidance. Recovery strategies + phase mapping provided for each. |

**Overall confidence:** HIGH

### Gaps to Address

- **POS-7 per-line discount schema:** `sales_order_items` has no `discount_type`/`discount_value`/`discount` columns — a new migration is justified (genuinely new column). Phase 4 planning must specify the migration + fillable + resource + frontend cart updates together.
- **REP-11 profit-margin COGS basis:** `SalesOrderItem` has no cost column. Decision needed: snapshot `cost_amount` at sale time (new column, most accurate) vs look up `ProductVariant` current cost at report time (simpler, drifts) vs join `batches`→`reception_order_products` (FIFO-accurate, complex query). Phase 6 planning must lock this down.
- **POS-17 returns/refunds design:** Whether returns are a new `SalesOrderReturnType` enum + inverse FIFO, or a refund flow on the existing order. Phase 4 planning must specify the data model + stock-reversal path.
- **`usePosClient` `api.v1.pos.*` routes:** These 5 routes are referenced by the composable but don't exist in `routes/api.php`. Phase 2 must refactor `usePosClient` to web routes (recommended) or add the API routes (not recommended) — decision is clear but the refactor scope needs planning.
- **Receipt print deployment target:** Browser print is the MVP, but the actual thermal printer model (58mm vs 80mm) and whether a networked thermal printer exists in deployment determines whether `mike42/escpos-php` is added in Phase 4 or deferred. Validate with the deployment context during Phase 4 planning.
- **Dashboard permission scope for `salesman`:** Should a salesman see only their own sales KPIs or none? `FEATURES.md` flags this; Phase 5 planning must decide and document.

## Sources

### Primary (HIGH confidence)
- Codebase inspection (authoritative for this project): `package.json`, `composer.json`, `node_modules/primevue/package.json`, `node_modules/chart.js/package.json`, `app/Models/*` (SalesOrder, SalesOrderItem, SalesOrderPayment, CashRegisterShift, CashRegisterMovement, Batch, ProductVariant), `app/Services/*` (SalesOrderService, FifoStockDeductionService, BatchService, CashRegisterShiftService, StockAlertService, StockTransferService), `app/Models/Setting.php`, `app/Http/Resources/CashRegisterShift/*` + `StockTransfer/*` (broken lines), `resources/js/Pages/SalesOrders/Create/Index.vue` (tax bug), `resources/js/Composables/useApi.ts` (CSRF bug), `resources/js/Composables/usePosStore.ts`, `resources/js/Composables/usePosClient.ts`, `resources/js/Composables/useCurrencyFormatter.ts`, `app/Http/Controllers/Pos/PosController.php`, `app/Http/Controllers/HomeController.php`, `app/Enums/PermissionsEnum.php`, `routes/web.php`, `routes/api.php`.
- `.planning/codebase/STACK.md`, `STRUCTURE.md`, `ARCHITECTURE.md`, `CONVENTIONS.md`, `CONCERNS.md` — full codebase audit.
- `.planning/PROJECT.md` — Core Value, constraints (read-only reports, ~10 API endpoints, no Docker/CI).
- `.claude/rules/*.md` — all project conventions (laravel-backend, vue-frontend, routes-and-api, authorization, testing, commands).
- PrimeVue 4.5.5 Chart component docs (primevue.org/chart/) — Chart.js 4.x wrapper confirmation.
- Inertia.js v1 documentation — partial reloads, lazy props, `router.reload({ only: [...] })`.
- Laravel 12 documentation — `response()->streamDownload()`.

### Secondary (MEDIUM confidence)
- packagist (authoritative registry): `barryvdh/laravel-dompdf` v3.1.2, `spatie/simple-excel` 3.10.0, `mike42/escpos-php` v4.0, `brick/money` 0.13.0, `league/csv` 9.28.0 — version + PHP dependency verification.
- npm registry: `chart.js` 4.5.1, `vue3-apexcharts` 1.11.1, `vue-chartjs` 5.3.3, `echarts` 6.1.0 — alternatives-rejected analysis.
- mike42/escpos-php GitHub repo — PrintConnector types, `pulse()`/`cut()`/`barcode()` methods, PHP 8.3 compatibility.
- Retail POS feature norms (Lightspeed Retail, Shopify POS, Square Retail, Loyverse POS) — category table-stakes validation.
- OWASP CSV Injection guidance — formula-injection via `=`/`+`/`-`/`@` prefixes.

### Tertiary (LOW confidence)
- Competitor web fetches (Clover/Lightspeed) returned JS-gated/404 pages — categorization relies on documented retail POS standards rather than live site scrapes.

---
*Research completed: 2026-06-21*
*Ready for roadmap: yes*