# Sales Management — Refactor & Completion

## What This Is

A Laravel 12 + Inertia.js + Vue 3 + PrimeVue sales-management application covering products, inventory, purchase orders, reception orders, sales orders, customers, vendors, cash registers, and stores. The backend and most admin modules are built and functional, but three user-facing surfaces are missing or stubbed: the **POS interface**, the **main dashboard**, and **reports**. Additionally, the existing code has accumulated critical issues (security gaps, broken resources, known bugs, convention drift) and a large unused API layer that must be addressed before new feature work.

## Core Value

A salesman or manager can complete an end-to-end sale through the POS and immediately see the result on the dashboard and reports — while every existing module behaves consistently with the project's documented conventions and is free of the critical defects listed in `.planning/codebase/CONCERNS.md`.

## Business Context

- **Customer**: Small-to-mid retail businesses managing multi-store inventory and POS sales.
- **Revenue model**: SaaS / licensed sales-management software (internal product).
- **Success metric**: A complete sale (POS → shift close → dashboard KPI → sales report) works end-to-end with no manual workaround.
- **Strategy notes**: This is the next milestone on an existing app — not a greenfield build.

## Requirements

### Validated

Existing capabilities inferred from the codebase map (`.planning/codebase/`):

- ✓ Product catalog with variants, options, option values, measurement units, brands, categories — existing
- ✓ Inventory management with batches, FIFO stock deduction, stock transfers, stock adjustments, stock alerts — existing
- ✓ Purchase orders (vendor → reception order → stock) with catalog and vendor-variant pricing — existing
- ✓ Sales orders with payments, tax, discounts, status transitions, customer linkage — existing
- ✓ Cash registers with shifts, movements, open/close/force-close — existing
- ✓ Stores, users, roles, permissions, activity log, settings — existing
- ✓ Authentication (username-based login, password reset, profile) — existing
- ✓ Inertia.js + Vue 3 + PrimeVue + Tailwind admin layout with sidebar, v-can directive, i18n — existing
- ✓ Media management (product images via spatie/laravel-medialibrary two-phase upload) — existing

### Active

**Critical fixes & refactor (from CONCERNS.md):**

- [ ] Fix API mass-assignment in `Api/VendorsController` and `Api/PurchaseOrdersController` (`$request->all()`)
- [ ] Add missing `authorize()` calls to 5 API controllers (ActivityLog, Batches, Permissions, PurchaseOrders, Settings)
- [ ] Fix `CashRegisterShiftResource` calling nonexistent `relationLoaded()` / `$movements` on the resource wrapper
- [ ] Fix `StockTransferResource` calling `toISOString()` on string-cast dates
- [ ] Fix `ProductVariant::$sku` referenced in resource but missing from model
- [ ] Standardize status comparison (enum vs string) across `SalesOrderService`, `CashRegisterShiftService`, and resources
- [ ] Fix hardcoded `taxRate = 0` and units mismatch in sales order frontend (Create/Edit)
- [ ] Fix `useApi.ts` setting `X-XSRF-TOKEN` to a DOM element instead of a string
- [ ] Fix `Api/PurchaseOrdersController` returning raw Eloquent models instead of a Resource
- [ ] Fix `ApiCollection` dropping pagination metadata
- [ ] Remove unused API layer (controllers, routes, resources, requests, axios composables) — keep only endpoints actually used by Inertia pages
- [ ] Convert `ProductVariant` and `ProductVariantUnit` from `$casts` property to `casts()` method
- [ ] Add `LogsActivity` trait + `getActivitylogOptions()` to 7 models missing it
- [ ] Add `TRANSITION_MAP` + `validateTransition()` to `CashRegisterShiftService`
- [ ] Consolidate duplicate FIFO deduction logic (`FifoStockDeductionService` vs `BatchService::deductFIFO*`)
- [ ] Add `SORT_COLUMN_MAP` whitelist to the 11 services passing user input to `->orderBy()`
- [ ] Narrow `catch (Exception $e)` to `InvalidArgumentException` in 12 web controllers
- [ ] Fix `AuthServiceProvider` missing imports / dead policy mapping
- [ ] Extract `ReceptionOrderController` claimed-quantities logic into the service
- [ ] Remove dead `$request->validated()` calls in `Api/RoleController`, `Api/MeasurementUnitController`

**Convention alignment (TypeScript & tests):**

- [ ] Fix 9 application TypeScript errors (useRoleClient import, axios response typing, Inertia RequestPayload casts, PurchaseOrderLineItem.catalog, DatePicker array value)
- [ ] Wire Pest test property access via `uses()->property()` or `@property` PHPDoc
- [ ] Convert non-JSON forbidden assertions to `*Json()` variants

**Performance & infrastructure:**

- [ ] Fix N+1 in `ReceptionOrderResource` (eager load `lineItems.productVariant.product.measurementUnit`, `lineItems.catalogEntry.unit`)
- [ ] Fix N+1 in `SalesOrderResource` (eager load `items` and `items.productVariant.product`)
- [ ] Replace `Cache::tags()` in `Setting` with key-based invalidation (default `file` driver doesn't support tags)
- [ ] Pass loaded `ProductVariant` into `recalculateStock()` instead of re-querying via `firstOrFail()`
- [ ] Configure log rotation; investigate 310MB `browser.log`

**New features — POS interface (full):**

- [ ] POS landing with cash register / shift selection (replace stub `Pos/Index.vue`)
- [ ] Product search and barcode/scan entry into cart
- [ ] Cart management: line items, quantities, per-line discounts, order-level discounts
- [ ] Tax computed from `sales.tax_rate` setting (frontend must match backend `/100` formula)
- [ ] Payment collection: cash, card, mixed payments; payment-difference validation against taxed total
- [ ] Cash register shift open / close / force-close with movement tracking (cash in/out)
- [ ] Print receipt
- [ ] Hold and recall order

**New features — Main dashboard:**

- [ ] KPI cards: today's sales, transaction count, low-stock alert count, cash on hand
- [ ] Sales trend chart (last 7/30 days)
- [ ] Top products chart
- [ ] Recent activity feed
- [ ] Stock alert summary (replace stub `Dashboard/Index.vue`)

**New features — Reports (standard sales suite):**

- [ ] Sales report: by period, by user, by store, by customer
- [ ] Inventory report: stock levels, stock valuation, expiry tracking
- [ ] Cash register report: shifts and movements
- [ ] Purchases & receptions report
- [ ] Top products report
- [ ] Profit margin report
- [ ] Report filters (date range, store, user, category) and CSV/PDF export

### Out of Scope

- CI/CD pipeline setup — out of scope this milestone; CONCERNS.md notes the gap but adding CI is a separate infrastructure effort
- Replacing `moment-timezone` with `date-fns` — working library; migration is cosmetic and can be done later
- Multi-server horizontal scaling (Redis sessions/cache) — single-server deployment is fine for current scale
- Mobile app / responsive POS — web-first; mobile-friendly POS is a future milestone
- Real-time chat / collaborative features — not relevant to sales management
- New modules beyond POS/Dashboard/Reports — scope is refactor + complete the three missing surfaces

## Context

**Existing codebase state (from `.planning/codebase/`):**

- 25 page modules already built following the full-stack Inertia pattern (Web Controller, API Controller, Service, Form Requests, Resources, Vue Pages, Composables, Types).
- Stack: Laravel 12 (Laravel 10 directory structure), Inertia.js v1 server + v2 client, Vue 3 + TypeScript, PrimeVue 4, Tailwind CSS 3, Pest 3, spatie/laravel-permission + activitylog + medialibrary, tightenco/ziggy, laravel/sanctum.
- Two layouts: `AppLayout` (admin sidebar) and `PosLayout` (POS with shift bar).
- Pinia only for POS module (`usePosStore`); admin pages use Inertia props + composables.
- Two Vite entry points: main app (Aura theme) and login app (Noir theme, Options API).
- 616 pre-existing PHPStan errors (mostly Pest test property access), 9 real TypeScript errors, 8 PrimeVue module declaration errors.
- 23 test files for 25 modules; service-layer unit tests minimal. Financial core (SalesOrderService, FifoStockDeductionService, BatchService) has no isolated tests.

**POS current state:** `Pos/Index.vue` is a 65-line stub showing "POS Interface — content will be added in Task 02". `PosController` only authorizes `POS_ACCESS` and renders the page. `RegisterSelectDialog.vue` exists and calls `usePosClient` for session/register selection. `usePosStore` Pinia store exists. The shift/cash-register backend (`CashRegisterShiftService`, `CashRegisterService`) is fully built but has the `TRANSITION_MAP` gap noted above.

**Dashboard current state:** `Dashboard/Index.vue` is a 15-line stub rendering `<h1>This is the dashboard</h1>`. `HomeController` injects only `alertSummary` from `StockAlertService::getSummary()`. No KPIs, no charts, no recent activity.

**Reports current state:** No reports module exists. No `Reports` Vue page, no `ReportController`, no report service. The data sources (sales orders, inventory, cash register shifts, purchase orders) all exist and are queryable.

**API layer state:** 13 API controllers, 7 API Form Request directories, 24 Resource directories. Only ~10 endpoints are actually called by Inertia pages (product/variant search, customer search, batches available, variant purchase units + price history, POS session, PO variant vendors, activity logs, settings). The rest are leftover from when the app was API-first and are now dead code. Removing them shrinks the attack surface and the convention-alignment workload.

## Constraints

- **Tech stack**: Keep Laravel 12 (Laravel 10 directory structure) + Inertia + Vue 3 + PrimeVue 4 + Tailwind 3. Do not migrate to Laravel 12 streamlined structure.
- **Conventions**: All new/changed code must follow `.claude/rules/*.md` (final classes, Form Requests, PermissionsEnum authorization, casts() method, LogsActivity trait, VeeValidate + Yup, PrimeVue direct imports, Ziggy `route()`).
- **No new migrations for modifications**: Modify existing migrations during development and use `migrate:fresh`. New migrations only for genuinely new tables (e.g. report snapshots if needed).
- **API surface**: Only the ~10 endpoints actually used by Inertia pages may remain after refactor. New dynamic fetches should prefer Inertia partial-reload / deferred props over new API endpoints where feasible.
- **POS UX**: Must use `PosLayout`, `usePosStore` (Pinia), and integrate with the existing `CashRegisterShiftService` / `CashRegisterService` backend — not reimplement them.
- **Reports**: Read-only — no write operations. Reuse existing services for aggregation; do not duplicate business logic.
- **Backend rules**: Throw `InvalidArgumentException` for business rule violations (not custom exceptions). Wrap critical operations in `DB::transaction()`. Use `lockForUpdate()` for stock/concurrency-sensitive operations.
- **Frontend rules**: VeeValidate + Yup for forms (not Inertia `useForm` except delete/restore). `v-can` directive for permission gating. `t()` from vue-i18n for all user-visible text.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Refactor depth = critical + conventions (not full alignment) | Fixing security/bugs + convention drift unblocks confident feature work; full alignment (CI, perf, moment migration) is deferred to keep the milestone focused. | — Pending |
| Remove unused API layer, keep only used endpoints | App moved from API-first to Inertia; dead API code adds attack surface, convention-alignment workload, and confusion. Keeping the ~10 used endpoints preserves the dynamic-fetch UX without a rewrite. | — Pending |
| Build full POS (not MVP) | POS is the core revenue flow; an MVP without receipts/holds/mixed payments would need immediate rework. Backend (shifts, cash registers, sales orders) already supports full scope. | — Pending |
| Build manager dashboard with charts | Dashboard is the manager landing page; KPIs alone don't justify the slot in the sidebar. Charts (sales trend, top products) are table-stakes for sales-management dashboards. | — Pending |
| Build standard sales report suite | Matches competitor feature sets and the existing data model (sales orders, inventory, cash registers, purchase orders all queryable). | — Pending |
| No CI/CD this milestone | CI is infrastructure work that doesn't deliver user value directly; CONCERNS.md notes the gap for a future milestone. | — Pending |
| Model profile = inherit (glm-5.2 session) | User wants glm-5.2 for reasoning/research/roadmap and kimi-2.7 for execution. `inherit` runs all spawned agents on the current session model; switching `/model` between phases achieves the split. | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-06-21 after initialization*