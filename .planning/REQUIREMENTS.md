# Requirements: Sales Management — Refactor & Completion

**Defined:** 2026-06-21
**Core Value:** A salesman or manager can complete an end-to-end sale through the POS and immediately see the result on the dashboard and reports — while every existing module behaves consistently with the project's documented conventions and is free of the critical defects listed in `.planning/codebase/CONCERNS.md`.

## v1 Requirements

Requirements for this milestone. Each maps to a roadmap phase.

### Critical Fixes

- [ ] **FIX-01**: `Api/VendorsController` and `Api/PurchaseOrdersController` no longer pass `$request->all()` to `create()`/`update()` — replaced with `$request->validated()` from new API Form Requests
- [ ] **FIX-02**: 5 API controllers (ActivityLog, Batches, Permissions, PurchaseOrders, Settings) have `authorize()` calls on every action using `PermissionsEnum::X->value, auth()->user()`
- [ ] **FIX-03**: `CashRegisterShiftResource` uses `$this->resource->relationLoaded()` / `$this->whenLoaded()` — movements no longer silently dropped from responses
- [ ] **FIX-04**: `StockTransferResource` no longer calls `toISOString()` on string-cast dates — all four date fields serialize correctly
- [ ] **FIX-05**: `ProductVariant` exposes the `sku`/identifier the `StockTransferResource` references (or resource uses the actual field) — no more undefined property access
- [ ] **FIX-06**: Status comparisons standardized — no more `$model->status === SalesOrderStatus::DRAFT->value` (string-vs-enum); always enum-to-enum on the model side, `->value` only for DB writes / raw-input comparison
- [ ] **FIX-07**: Sales order frontend (Create/Edit) computes tax from `useAuth().getSetting('sales', 'tax_rate', 0)` with the same `/100` formula as `SalesOrderService` — live preview and payment-difference validation match the backend
- [ ] **FIX-08**: `useApi.ts` no longer sets `X-XSRF-TOKEN` to a DOM element — relies on `withXSRFToken: true` + `withCredentials: true`, or uses `X-CSRF-TOKEN` with `?.content` if a manual header is needed
- [ ] **FIX-09**: `Api/PurchaseOrdersController` returns `PurchaseOrderResource` (and Collection) instead of raw Eloquent models — consistent API contract
- [ ] **FIX-10**: `ApiCollection` includes pagination `meta` block matching `UserCollection` pattern — `{data, meta:{current_page,last_page,per_page,total}}`
- [ ] **FIX-11**: Duplicate FIFO deduction logic consolidated — `BatchService::deductFIFO*` delegates to `FifoStockDeductionService` (or shared helper); standardized on `InvalidArgumentException`, consistent "closed batch at zero" behavior
- [ ] **FIX-12**: `CashRegisterShiftService` defines a `TRANSITION_MAP` constant + `validateTransition()` method — inline status checks in `open()`/`close()`/`forceClose()`/`addMovement()` replaced with centralized validation
- [ ] **FIX-13**: 11 services that pass user input to `->orderBy()` use a `SORT_COLUMN_MAP` whitelist (or `match()`) — unknown columns default to a safe column; `orderDirection` validated to `asc`/`desc`
- [ ] **FIX-14**: 12 web controllers catch `InvalidArgumentException` specifically (not base `Exception`) — non-business exceptions propagate to the global handler; no raw `$e->getMessage()` exposed for non-business errors
- [ ] **FIX-15**: `AuthServiceProvider` no longer has the dead `StockAdjustment::class => StockAdjustmentPolicy::class` mapping (or has correct imports) — passes PHPStan
- [ ] **FIX-16**: `ReceptionOrderController` claimed-quantities aggregation extracted into `ReceptionOrderService` — single source of truth called from both controller methods
- [ ] **FIX-17**: Dead `$request->validated()` calls removed from `Api/RoleController` and `Api/MeasurementUnitController` (or replaced with `$validated = $request->validated();` and used)
- [ ] **FIX-18**: `Setting` model no longer uses `Cache::tags()` — replaced with key-based `Cache::rememberForever()` + `Cache::forget("settings.{$key}")` / `Cache::forget("settings.group.{$group}")` on writes; works on the default `file` driver
- [ ] **FIX-19**: N+1 in `ReceptionOrderResource` fixed — `ReceptionOrderService::list()` and `show()` eager load `lineItems.productVariant.product.measurementUnit` and `lineItems.catalogEntry.unit`
- [ ] **FIX-20**: N+1 in `SalesOrderResource` fixed — `SalesOrderService::list()` eager loads `items` and `items.productVariant.product`
- [ ] **FIX-21**: Stock-deduction call sites pass the already-loaded `ProductVariant` into `recalculateStock()` instead of re-querying via `firstOrFail()` (no `ModelNotFoundException` for missing variants)
- [ ] **FIX-22**: Log rotation configured (`config/logging.php` daily driver); 310MB `browser.log` investigated and bounded

### Convention Alignment

- [ ] **CONV-01**: `ProductVariant` and `ProductVariantUnit` converted from `$casts` property to `casts()` method
- [ ] **CONV-02**: 7 models (`PendingMediaUpload`, `ProductOption`, `ProductOptionValue`, `ProductVariantOptionValue`, `PurchaseOrderProduct`, `ReceptionOrderProduct`, `StockTransferItem`) have `LogsActivity` trait + standard `getActivitylogOptions()` (or documented exclusion for pivot-style models)
- [ ] **CONV-03**: 9 application TypeScript errors fixed (useRoleClient import, axios response typing, Inertia RequestPayload casts, `PurchaseOrderLineItem.catalog`, DatePicker array value)
- [ ] **CONV-04**: Pest test property access wired via `uses()->property()` or `@property` PHPDoc on a base test class — no more dynamic `$this->service`/`$this->store` (removes ~464 PHPStan errors)
- [ ] **CONV-05**: Forbidden assertions in `BrandTest`, `CategoryTest`, `MeasurementUnitTest`, `CatalogTest` converted to `*Json()` variants (avoids `storage/logs/laravel.log` permission failures)
- [ ] **CONV-06**: PHPStan baseline generated for residual pre-existing errors so new violations surface cleanly
- [ ] **CONV-07**: `useCurrencyFormatter` composable widened to accept `number | string` (preparatory for POS cart + reports)

### API Layer Removal

- [ ] **API-01**: 8 unused API controllers deleted (`ActivityLogController`, `BatchesController`, `BrandController`, `CategoryController`, `MeasurementUnitController`, `PermissionsController`, `PurchaseOrdersController`, `RoleController`, `SettingsController`, `UserController`, `VendorsController`) — only `VariantsController`, `CustomerController`, `BatchesController` (or its `available` endpoint moved), `ActivityLogController`, and `VendorsController::getProductVariants` (moved to `VariantsController`) remain
- [ ] **API-02**: Unused API routes removed from `routes/api.php` — only the ~10 endpoints used by Inertia composables remain
- [ ] **API-03**: Unused API Form Requests deleted (`Api/Brands`, `Api/Categories`, `Api/MeasurementUnits`, `Api/Roles`, `Api/Users` if unused) — kept ones retained
- [ ] **API-04**: Shared Resources (used by both Web Inertia props AND API responses) identified and retained; API-only Resources deleted
- [ ] **API-05**: Unused axios composables deleted (`useBrandClient`, `useCategoryClient`, `useMeasurementUnitClient`, `usePermissionClient`, `useRoleClient`, `useUserClient`, `useSettingClient` if unused); kept: `useProductClient`/`useVariantClient`, `useCustomerClient`, `useBatchClient`, `useActivityLogClient`, `usePurchaseOrderClient`, `usePosClient`, `useMediaClient`, `useAuthClient`, `useSalesOrderClient`
- [ ] **API-06**: `usePosClient` refactored to use web routes via Inertia `router` (the `api.v1.pos.*` routes it calls don't exist) — aligns with API-removal decision
- [ ] **API-07**: Orphaned TypeScript types deleted; kept types retained
- [ ] **API-08**: Deletion verified bottom-up (routes → controllers → requests → resources → composables → types) with grep on both backend + frontend to catch Ziggy `route()` references and Resource imports

### POS Interface

- [ ] **POS-01**: New `PermissionsEnum` cases added (`POS_ACCESS`, `POS_SALE`, `POS_SHIFT_OPEN`, `POS_SHIFT_CLOSE`, `POS_SHIFT_FORCE_CLOSE`, `POS_CASH_MOVEMENT`, `POS_RETURNS` if returns in scope) and seeded via `PermissionSeeder`
- [ ] **POS-02**: `Pos/Index.vue` replaced with full POS interface (no longer a stub) using `PosLayout`
- [ ] **POS-03**: Product search and barcode/scan entry (keyboard-wedge input) into the cart
- [ ] **POS-04**: Cart management via `usePosStore` (Pinia) — line items, quantities, per-line discounts, order-level discounts
- [ ] **POS-05**: Per-line discount columns added to `sales_order_items` migration (`discount_type`, `discount_value`, `discount_amount`) — modify existing migration, `migrate:fresh`
- [ ] **POS-06**: Tax computed from `sales.tax_rate` setting using the same `/100` formula as `SalesOrderService` (reuse the shared tax helper from FIX-07, do not re-implement)
- [ ] **POS-07**: Payment collection — cash, card, mixed payments; payment-difference validation against the **taxed** total (not the buggy untaxed total)
- [ ] **POS-08**: Cash register shift open / close / force-close with movement tracking (cash in/out) via the fixed `CashRegisterShiftService` (uses `TRANSITION_MAP` from FIX-12)
- [ ] **POS-09**: Checkout creates a `SalesOrder` via a thin `PosService` orchestrator that calls `SalesOrderService::create()` with `status: 'paid'` — no duplicate sale-creation path
- [ ] **POS-10**: FIFO stock deduction at checkout via the consolidated `FifoStockDeductionService` (from FIX-11) — zero-stock batches closed
- [ ] **POS-11**: Print receipt via `window.print()` + `@media print` styles (HTML receipt view); `mike42/escpos-php` gated task for networked thermal printers (conditional on deployment target)
- [ ] **POS-12**: Hold and recall order — held orders do NOT reserve stock (availability pre-checked at recall)
- [ ] **POS-13**: Customer attach to sale (search via existing `useCustomerClient` or attach walk-in)
- [ ] **POS-14**: `PosController` (Web) authorizes via `PermissionsEnum::POS_ACCESS` (and finer cases per action)
- [ ] **POS-15**: `v-can` directive gates POS actions in the Vue page
- [ ] **POS-16**: i18n keys for all POS user-visible text in `resources/lang/en.json` + `es.json`
- [ ] **POS-17**: `SalesOrderStatus` extended if needed (e.g. `held` for hold/recall, `partially_paid` if partial payments) — backed enum, value matches DB enum column

### Dashboard

- [ ] **DASH-01**: New `PermissionsEnum` cases (`DASHBOARD_VIEW`) added and seeded; salesman may have restricted view (own sales) vs admin (all stores) — design decision in phase plan
- [ ] **DASH-02**: `Dashboard/Index.vue` replaced with manager dashboard (no longer a stub) using `AppLayout`
- [ ] **DASH-03**: KPI cards: today's sales total, today's transaction count, low-stock alert count, cash on hand (open shifts sum)
- [ ] **DASH-04**: Sales trend chart (last 7/30 days) via PrimeVue `<Chart>` (Chart.js) — data from `DashboardService` aggregation
- [ ] **DASH-05**: Top products chart (top N by revenue or units, last 7/30 days)
- [ ] **DASH-06**: Recent activity feed from `spatie/activitylog` (last N entries)
- [ ] **DASH-07**: Stock alert summary (existing `StockAlertService::getSummary()` reused)
- [ ] **DASH-08**: `DashboardService` (new) runs aggregate SQL server-side (`->sum()`, `->count()`, grouped queries — no `->get()->sum()` N+1) and passes pre-aggregated data via Inertia props
- [ ] **DASH-09**: Timezone-aware date helper introduced (server `today()` vs store TZ — `Settings` may store timezone; "today" uses the store's TZ, not UTC)
- [ ] **DASH-10**: Dashboard data refreshable (Inertia partial reload or `router.reload()`) so a sale reflects on next visit

### Reports

- [ ] **REP-01**: New `PermissionsEnum` cases (`REPORTS_VIEW`, `REPORTS_SALES`, `REPORTS_INVENTORY`, `REPORTS_CASH`, `REPORTS_PURCHASES`) added and seeded
- [ ] **REP-02**: `ReportController` (Web) + `ReportService` (new) + `Vue Pages/Reports/*` + `useReportClient` + `report-types.ts` following the module pattern
- [ ] **REP-03**: Sales report — by period, by user, by store, by customer (filterable on all four dimensions)
- [ ] **REP-04**: Inventory report — stock levels, stock valuation (FIFO batch cost basis), expiry tracking
- [ ] **REP-05**: Cash register report — shifts and movements per register/store/period
- [ ] **REP-06**: Purchases & receptions report — by vendor, by period
- [ ] **REP-07**: Top products report — by revenue, by units, by profit
- [ ] **REP-08**: Profit margin report — COGS from FIFO batch cost (canonical basis), revenue from sales order totals, margin per line + per order + aggregate
- [ ] **REP-09**: Report filters — date range (inclusive end date, TZ-aware using the DASH-09 helper), store, user, category, customer
- [ ] **REP-10**: CSV export via `spatie/simple-excel` streamed response — cells starting with `=`,`+`,`-`,`@` prefixed with `'` (CSV formula-injection guard)
- [ ] **REP-11**: PDF export via `barryvdh/laravel-dompdf` — chunked rendering for large datasets; pagination breaks avoid mid-row splits
- [ ] **REP-12**: Large exports use `->chunk()` + `StreamedResponse` (no memory exhaustion on >50k rows)
- [ ] **REP-13**: Reports are read-only — no write operations; reuse existing services for aggregation (no duplicated business logic)
- [ ] **REP-14**: `App\Support\MoneyFormatter` (PHP `NumberFormatter`/intl) introduced for server-side PDF/receipt rendering
- [ ] **REP-15**: Report routes (`reports.index`, `reports.sales`, `reports.inventory`, `reports.cash-register`, `reports.purchases`, `reports.top-products`, `reports.profit-margin`) named per `routes-and-api.md`
- [ ] **REP-16**: `v-can` directive gates report access; salesman sees only own-store data (if `DASHBOARD_VIEW` design carries over)
- [ ] **REP-17**: i18n keys for all report labels in `en.json` + `es.json`
- [ ] **REP-18**: `composer lint` and `npm run lint` / `npm run type-check` pass clean (modulo PHPStan baseline from CONV-06)

## v2 Requirements

Deferred to future milestone:

- **POS-2FA**: Two-factor auth for shift open
- **POS-OFFLINE**: Offline-first PWA POS (process sales without network, sync on reconnect)
- **POS-LOYALTY**: Loyalty points / customer loyalty program
- **POS-GIFTCARD**: Gift card issuance and redemption
- **DASH-LIVE**: Live dashboard with WebSocket push (no manual refresh)
- **DASH-PREDICT**: Predictive reorder suggestions (stockout forecast)
- **REP-ABC**: ABC inventory analysis
- **REP-SLOWMOVER**: Slow-mover detection report
- **REP-CUSTOM**: Custom report builder (user-defined columns/filters)
- **REP-FX**: Multi-currency / FX report consolidation
- **REPORTS-EXCEL**: Native xlsx export with formulas (currently CSV + PDF only)

## Out of Scope

| Feature | Reason |
|---------|--------|
| CI/CD pipeline | Infrastructure effort, doesn't deliver user value directly; CONCERNS.md notes the gap for a future milestone |
| Replacing `moment-timezone` with `date-fns` | Working library; migration is cosmetic and deferred |
| Multi-server horizontal scaling (Redis sessions/cache) | Single-server deployment is fine for current scale; CONCERNS.md notes the limit |
| Mobile app / native responsive POS | Web-first; mobile-friendly POS is a future milestone |
| Real-time chat / collaborative features | Not relevant to sales management |
| Table service / kitchen tickets (hospitality) | This is retail, not hospitality |
| Payroll / HR / full accounting | Not sales management scope |
| CRM (lead pipeline, marketing automation) | Out of sales-management scope |
| Native mobile app | Web-first; deferred |
| Full-text search engine (Meilisearch/Scout) | Current LIKE search is adequate for catalog size |
| WebSocket live updates | Dashboard refreshes via Inertia reload; live push is v2 |
| Predictive analytics / AI forecasting | v2 differentiator |
| Custom report builder | v2 differentiator; current milestone ships standard reports |

## Traceability

Updated 2026-06-21 during roadmap creation. Every v1 requirement maps to exactly one phase; Phase 7 (Test Coverage) introduces no new requirements but verifies FIX/POS/DASH/REP end-to-end.

| Requirement | Phase | Status |
|-------------|-------|--------|
| FIX-01 | Phase 1 | Pending |
| FIX-02 | Phase 1 | Pending |
| FIX-03 | Phase 1 | Pending |
| FIX-04 | Phase 1 | Pending |
| FIX-05 | Phase 1 | Pending |
| FIX-06 | Phase 1 | Pending |
| FIX-07 | Phase 1 | Pending |
| FIX-08 | Phase 1 | Pending |
| FIX-09 | Phase 1 | Pending |
| FIX-10 | Phase 1 | Pending |
| FIX-11 | Phase 1 | Pending |
| FIX-12 | Phase 1 | Pending |
| FIX-13 | Phase 1 | Pending |
| FIX-14 | Phase 1 | Pending |
| FIX-15 | Phase 1 | Pending |
| FIX-16 | Phase 1 | Pending |
| FIX-17 | Phase 1 | Pending |
| FIX-18 | Phase 1 | Pending |
| FIX-19 | Phase 1 | Pending |
| FIX-20 | Phase 1 | Pending |
| FIX-21 | Phase 1 | Pending |
| FIX-22 | Phase 1 | Pending |
| API-01 | Phase 2 | Pending |
| API-02 | Phase 2 | Pending |
| API-03 | Phase 2 | Pending |
| API-04 | Phase 2 | Pending |
| API-05 | Phase 2 | Pending |
| API-06 | Phase 2 | Pending |
| API-07 | Phase 2 | Pending |
| API-08 | Phase 2 | Pending |
| CONV-01 | Phase 3 | Pending |
| CONV-02 | Phase 3 | Pending |
| CONV-03 | Phase 3 | Pending |
| CONV-04 | Phase 3 | Pending |
| CONV-05 | Phase 3 | Pending |
| CONV-06 | Phase 3 | Pending |
| CONV-07 | Phase 3 | Pending |
| POS-01 | Phase 4 | Pending |
| POS-02 | Phase 4 | Pending |
| POS-03 | Phase 4 | Pending |
| POS-04 | Phase 4 | Pending |
| POS-05 | Phase 4 | Pending |
| POS-06 | Phase 4 | Pending |
| POS-07 | Phase 4 | Pending |
| POS-08 | Phase 4 | Pending |
| POS-09 | Phase 4 | Pending |
| POS-10 | Phase 4 | Pending |
| POS-11 | Phase 4 | Pending |
| POS-12 | Phase 4 | Pending |
| POS-13 | Phase 4 | Pending |
| POS-14 | Phase 4 | Pending |
| POS-15 | Phase 4 | Pending |
| POS-16 | Phase 4 | Pending |
| POS-17 | Phase 4 | Pending |
| DASH-01 | Phase 5 | Pending |
| DASH-02 | Phase 5 | Pending |
| DASH-03 | Phase 5 | Pending |
| DASH-04 | Phase 5 | Pending |
| DASH-05 | Phase 5 | Pending |
| DASH-06 | Phase 5 | Pending |
| DASH-07 | Phase 5 | Pending |
| DASH-08 | Phase 5 | Pending |
| DASH-09 | Phase 5 | Pending |
| DASH-10 | Phase 5 | Pending |
| REP-01 | Phase 6 | Pending |
| REP-02 | Phase 6 | Pending |
| REP-03 | Phase 6 | Pending |
| REP-04 | Phase 6 | Pending |
| REP-05 | Phase 6 | Pending |
| REP-06 | Phase 6 | Pending |
| REP-07 | Phase 6 | Pending |
| REP-08 | Phase 6 | Pending |
| REP-09 | Phase 6 | Pending |
| REP-10 | Phase 6 | Pending |
| REP-11 | Phase 6 | Pending |
| REP-12 | Phase 6 | Pending |
| REP-13 | Phase 6 | Pending |
| REP-14 | Phase 6 | Pending |
| REP-15 | Phase 6 | Pending |
| REP-16 | Phase 6 | Pending |
| REP-17 | Phase 6 | Pending |
| REP-18 | Phase 6 | Pending |

**Coverage:**
- v1 requirements: 82 total (FIX-22, API-08, CONV-07, POS-17, DASH-10, REP-18 — not 73; the prior count undercounted by 9)
- Mapped to phases: 82 ✓
- Unmapped: 0 ✓

**Phase mapping summary:**
- Phase 1 — Critical Fixes & Refactor: 22 (FIX-01..22)
- Phase 2 — API Layer Removal: 8 (API-01..08)
- Phase 3 — Convention Alignment: 7 (CONV-01..07)
- Phase 4 — POS Module: 17 (POS-01..17)
- Phase 5 — Manager Dashboard: 10 (DASH-01..10)
- Phase 6 — Reports Module: 18 (REP-01..18)
- Phase 7 — Test Coverage: 0 new (verifies FIX/POS/DASH/REP end-to-end)

---
*Requirements defined: 2026-06-21*
*Last updated: 2026-06-21 after roadmap creation (traceability populated)*