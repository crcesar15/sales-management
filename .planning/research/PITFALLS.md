# Pitfalls Research

**Domain:** Sales-management app — adding POS, dashboard, and reports onto an existing Laravel 12 + Inertia + Vue 3 + PrimeVue codebase that carries 616 PHPStan errors, broken resources, a tax bug, duplicate FIFO logic, Cache::tags on a file driver, mass-assignment gaps, and a large unused API layer. Plus the refactor to clean that up.
**Researched:** 2026-06-21
**Confidence:** HIGH (grounded in `.planning/codebase/CONCERNS.md` audit + direct file reads of SalesOrderService, FifoStockDeductionService, BatchService, Setting, both broken resources, the tax-bug Vue page, useApi.ts, CashRegisterShiftService, and StockTransferService)

## Critical Pitfalls

### Pitfall 1: POS cart reuses the buggy frontend tax formula and rejects valid payments

**What goes wrong:**
The POS cart computes a live total and validates "payments must equal order total" client-side. If it copies the existing pattern from `resources/js/Pages/SalesOrders/Create/Index.vue`, it will hardcode `taxRate = 0` and multiply by `taxRate` directly (line 53-55) instead of dividing by 100 like the backend. When a non-zero `tax_rate` setting is eventually enabled, the displayed total is wrong AND the payment-difference check at line 67-70 compares the payment sum against the **untaxed** frontend total. The backend (`SalesOrderService::calculateTotals()` at line 269) computes `($subTotal - $discount) * ($taxRate / 100)`. So the backend rejects the submission (payments don't cover the real taxed total) or accepts it with a mismatch that silently corrupts the order's `tax_amount`.

**Why it happens:**
The existing sales-order create page already has this exact bug and is the only template a developer will mirror. The frontend hardcodes `taxRate = 0` with a `// TODO: read from settings` comment and never applied `/100`. There are no sales-order tests to catch the drift.

**How to avoid:**
Fix the tax calculation in **both** `SalesOrders/Create/Index.vue`, `SalesOrders/Edit/Index.vue`, AND the new POS cart in the **same phase**, before any of them ship. The fix: read `useAuth().getSetting('sales', 'tax_rate', 0)` and compute `taxAmount = (subTotal - discount) * (taxRate / 100)` to match `SalesOrderService::calculateTotals()` exactly. Add a shared frontend composable `useOrderTotals(items, discountType, discountValue)` so all three call sites share one formula — do NOT copy-paste the calculation into the POS. Add a Pest test that creates a sales order with `tax_rate=13` and asserts the saved `tax_amount` matches the backend formula.

**Warning signs:**
- POS "Payment difference" error on an order that visually totals correctly
- Saved sales orders have `tax_amount = 0` while settings show a non-zero `tax_rate`
- Frontend total != backend `total` field after a fresh page load
- Any new cart component with a local `taxRate` constant instead of reading settings

**Phase to address:**
**Refactor/critical-fix phase — BEFORE POS build phase.** The tax fix is a prerequisite, not a POS-phase task. If POS is built on the buggy formula, the bug multiplies to a second surface and the fix touches two forms + the cart simultaneously (higher risk).

---

### Pitfall 2: CashRegisterShiftResource drops movements silently (broken serialization) — POS shift UI shows empty movements

**What goes wrong:**
`CashRegisterShiftResource.php:37` calls `$this->relationLoaded('movements')` and accesses `$this->movements` directly on the `JsonResource` wrapper. `relationLoaded()` is an Eloquent-model method, not a `JsonResource` method — it resolves to null/throws and the `?: []` fallback always returns an empty array. Movements are silently stripped from every API/Inertia response that serializes a shift. The POS shift bar (PosLayout) and the cash-register report will show "0 movements" on a shift that has movements, making reconciliation look correct when it isn't, or making a manager think a cash-in/cash-out never happened.

**Why it happens:**
The resource author treated `$this` as the model. Other resources in the app use `$this->whenLoaded(...)` or `$this->resource->...` — this one didn't follow the pattern. PHPStan flags it (`method.notFound` ×2) but the 616-error baseline drowns it out.

**How to avoid:**
Fix the resource in the critical-fix phase: replace line 37-39 with `$this->whenLoaded('movements', fn () => CashRegisterMovementResource::collection($this->resource->movements)->resolve(), [])`. Verify by hitting the shift endpoint with a movement-loaded shift and asserting the array is non-empty. **Do not build POS shift reconciliation UI until this is fixed** — otherwise the reconciliation screen will show a false "balanced" shift.

**Warning signs:**
- POS shift bar shows zero cash-in/out despite movements existing in the DB
- Cash register report "movements" column is empty for all shifts
- ` CashRegisterShiftResource` line 37 uses `$this->relationLoaded` instead of `$this->resource->relationLoaded` or `$this->whenLoaded`

**Phase to address:**
**Refactor/critical-fix phase — before POS shift UI and before cash-register report.** Both consumers depend on correct movement serialization.

---

### Pitfall 3: Duplicate FIFO logic diverges — POS sales deduct via one path, transfers via another, and they throw different exceptions

**What goes wrong:**
Two FIFO implementations exist with behavioral drift:
- `FifoStockDeductionService::deductForOrder()` (used by `SalesOrderService` for POS/admin sales) throws `InvalidArgumentException` on insufficient stock, increments `sold_quantity`, and does NOT auto-close batches when `remaining_quantity` hits 0.
- `BatchService::deductFIFO()` and `deductFIFOForTransfer()` (used by `StockTransferService`) throw `RuntimeException`, increment `sold_quantity` OR `transferred_quantity`, and DO auto-close batches when `remaining_quantity === 0` (line 124-126, 156-158).

If POS calls the sales path and a transfer calls the transfer path, a variant sold via POS leaves its batch "active" with `remaining_quantity=0` (not closed), while the same variant transferred closes the batch. Reports that filter `status = 'active'` will include dead-but-unclosed POS batches, inflating "available stock" counts. The different exception types mean a controller catching `InvalidArgumentException` (per the convention) will let transfer stock-outs bubble as uncaught `RuntimeException`.

**Why it happens:**
The codebase has no single FIFO owner. `BatchService::deductFIFO*` predates or parallels `FifoStockDeductionService`, and nobody consolidated them. CONCERNS.md flags this explicitly.

**How to avoid:**
Consolidate in the critical-fix phase: make `BatchService::deductFIFO*` delegate to `FifoStockDeductionService` (or extract a shared `FifoDeductor` helper), and standardize on `InvalidArgumentException` per `laravel-backend.md`. Decide once whether auto-closing batches at `remaining_quantity=0` is desired (yes — it matches the transfer path and keeps reports clean) and apply it in the canonical path. Add a unit test: sell a variant to zero, assert the batch is closed. Add a second test: transfer a variant to zero, assert the same closed status. Both must pass through the same code path.

**Warning signs:**
- Inventory report lists batches with `remaining_quantity=0` and `status='active'` (POS-sold batches not closed)
- `RuntimeException` surfaces uncaught in a transfer flow while `InvalidArgumentException` is caught in a sales flow
- Two services both reference "FIFO" and both lock batches

**Phase to address:**
**Refactor/critical-fix phase — before POS sales and before inventory/stock-valuation report.** POS will heavily exercise `FifoStockDeductionService`; reports will read batch status. Both need the canonical behavior locked down first.

---

### Pitfall 4: `Cache::tags()` on the file driver crashes the first `Setting::get()` — and the fix, if it doesn't flush, leaves stale tax rates

**What goes wrong:**
`Setting::get()` (line 41), `Setting::set()` (line 59), and `Setting::group()` (line 69) all call `Cache::tags(['settings'])->...`. The default `CACHE_DRIVER=file` (`.env.example`) does not support tags — `Cache::tags()` throws `BadMethodCallException` at runtime. Every code path that reads a setting (tax rate, currency, expiry alert days, timezone) crashes on a stock deployment. This is already a live bug, but POS and dashboard make it **worse** in two ways:
1. POS reads `tax_rate` on every sale → crash on every sale if the driver isn't Redis.
2. When the fix lands (replace `Cache::tags(['settings'])->flush()` with per-key `Cache::forget`), any environment that was somehow running (e.g. with Redis) keeps stale cached settings because the new invalidation logic doesn't know about the old tagged entries. A stale `tax_rate` means POS computes the wrong tax silently.

**Why it happens:**
The code was written for Redis but `.env.example` advertises `file`. The fix changes invalidation strategy; without a one-time cache flush, old entries linger.

**How to avoid:**
In the critical-fix phase: replace `Cache::tags(['settings'])->rememberForever(...)` with plain `Cache::rememberForever("settings.{$key}", ...)` and replace `Cache::tags(['settings'])->flush()` with explicit `Cache::forget("settings.{$key}")` + a `Cache::flush()`-by-prefix or a versioned key (`"settings.v2.{$key}"`) to invalidate all old entries atomically. Run `php artisan cache:clear` once after deploying the fix. Add a test that calls `Setting::set('tax_rate', 13)` then `Setting::get('tax_rate')` and asserts `13.0` with the `file` driver.

**Warning signs:**
- `BadMethodCallException: tag` on first POS sale or first dashboard load
- POS tax amount doesn't change after updating `tax_rate` in settings (stale cache)
- Tests pass only because `phpunit.xml` uses `array` driver which silently no-ops tags

**Phase to address:**
**Refactor/critical-fix phase — before POS and before dashboard.** Both features read settings on the hot path.

---

### Pitfall 5: CashRegisterShiftService has no `TRANSITION_MAP` — adding it while POS is live changes the state machine mid-flight

**What goes wrong:**
`CashRegisterShiftService` uses inline `if ($shift->status->value !== CashRegisterShiftStatus::OPEN->value)` checks at lines 67, 102, 136, 170 — no central `TRANSITION_MAP` or `validateTransition()`. Every other stateful service (`SalesOrderService`, `StockTransferService`, `PurchaseOrderService`) has one. When the critical-fix phase adds `TRANSITION_MAP` (per CONCERNS.md), the allowed transitions must match the existing inline checks EXACTLY. If the new map is stricter (e.g. omits `forced_close` from `open`'s allowed list, or adds a `closed → open` transition that didn't exist), POS operations break: a cashier can't close a shift, or can reopen a closed shift that shouldn't be reopenable. Doing this refactor while POS is actively using shifts means a cashier mid-sale hits a transition that worked yesterday and fails today.

**Why it happens:**
The gap was tolerated because the shift UI was a stub. Adding POS turns shifts into a live, in-use state machine. Refactoring the state machine while it's under load is the classic "change the engine while driving" mistake.

**How to avoid:**
Add `TRANSITION_MAP` to `CashRegisterShiftService` in the **critical-fix phase, before POS wiring**. Map: `'open' => ['closed', 'forced_close'], 'closed' => [], 'forced_close' => []` (mirroring the current inline rules — `openShift` blocks if any open exists, `close`/`forceClose` require open, movements require open). Add a Pest test for each valid transition and each invalid one BEFORE touching POS. Do NOT add new transitions (like `closed → open`) in the same change — keep it a pure refactor, add new transitions in a later phase with their own tests.

**Warning signs:**
- "Cannot transition shift from X to Y" `InvalidArgumentException` thrown on a close/force-close that used to work
- A shift can be reopened after closing (new transition leaked in)
- POS cashier gets a 500 mid-sale after the refactor deploys

**Phase to address:**
**Refactor/critical-fix phase — strictly before POS shift integration.** POS must be built on the final state machine, not a moving target.

---

### Pitfall 6: Dashboard N+1 on KPI aggregation — "today's sales" joins sales_order_payments per row in PHP

**What goes wrong:**
The dashboard KPI "today's sales total" and "transaction count" will naively query `SalesOrder::whereDate('created_at', today())->get()` and sum in PHP, OR query with a `->join('sales_order_payments')` and group — but the existing `SalesOrderService::list()` already has an N+1 (it doesn't eager-load `items`, per CONCERNS.md). The dashboard, run on every manager login, will fire one query per sales order to load payments for the "cash on hand" KPI, plus one per order for the trend chart. At 100 orders/day this is 200+ queries per dashboard load. The "cash on hand" KPI from `CashRegisterShiftService::calculateExpectedClosing()` already does a `->join('sales_order_payments')` (line 207-210) — copying that into a dashboard KPI without caching repeats the join on every page load.

**Why it happens:**
KPIs feel like "just a query" and get written ad-hoc in a controller or a new `DashboardService` without reusing the existing eager-load patterns. No one benchmarks a dashboard with 10 orders; the N+1 only surfaces at scale.

**How to avoid:**
Create a dedicated `DashboardService` with aggregation methods that use **single queries with `->sum()`, `->count()`, and `->selectRaw()`** — never `->get()->sum()`. Cache KPI results with a short TTL (e.g. `Cache::remember('dashboard.todays_sales', 60, fn() => ...)`) using the fixed (non-tagged) cache pattern. Eager-load `items.productVariant.product` in any sales-order query the dashboard reuses from `SalesOrderService`. Add a test that asserts the dashboard route fires ≤ N queries (use `DB::flushQueryLog()` + `assertCount`).

**Warning signs:**
- Dashboard takes >500ms with 100 sales orders
- `debugbar` / `telescope` shows 100+ queries on `/dashboard`
- "Today's sales" number flickers or sums differently on refresh (race with in-flight sales)

**Phase to address:**
**Dashboard build phase.** Build the `DashboardService` from the start with aggregation + caching, not a controller-level `->get()->sum()`.

---

### Pitfall 7: Dashboard timezone drift — "today's sales" uses server timezone while the user's "today" is different

**What goes wrong:**
`SalesOrderService::list()` uses `->whereDate('created_at', '>=', $from)` with raw date strings (line 62-68). The dashboard "today's sales" KPI will likely use `today()` or `Carbon::today()` which resolves to the server timezone (config('app.timezone')). If the server is UTC and the store is in La Paz (UTC-4), a sale at 22:00 local on June 21 is stored as June 22 02:00 UTC. The dashboard "today" (June 21 server-time) won't include it, but the cashier sees it as today's sale. The dashboard KPI and the POS shift close will disagree — the shift shows the sale, the dashboard doesn't, and the manager thinks money is missing.

**Why it happens:**
Laravel defaults to UTC; the app's settings may have a `timezone` setting (the `useDatetimeFormatter` composable reads a timezone setting). The DB writes in UTC. Nobody converts the user/store timezone to UTC before querying `whereDate`. This is a silent, intermittent bug that only affects stores in non-UTC timezones at the edges of their day.

**How to avoid:**
In the dashboard phase: read the configured timezone (`Setting::get('timezone', config('app.timezone'))`), convert "today" to a UTC range explicitly: `->whereBetween('created_at', [Carbon::parse($date, $tz)->startOfDay()->utc(), Carbon::parse($date, $tz)->endOfDay()->utc()])`. Add a test with `timezone='America/La_Paz'` and a sale at 23:30 local asserting it appears in "today's" KPI. Document that all date-range filters in reports use the same conversion.

**Warning signs:**
- Dashboard "today's sales" != POS shift closing balance (off by the late-evening sales)
- Reports filter by `created_at` date and miss/over-include boundary sales
- KPIs match only when server timezone == store timezone

**Phase to address:**
**Dashboard phase (introduce the pattern) + Reports phase (reuse it).** This must be solved once, in a shared helper, before either feature's date filters are written.

---

### Pitfall 8: CSV export allows Excel formula injection (=, +, -, @ prefixes) from product/customer names

**What goes wrong:**
The reports phase builds CSV export. Product names, customer names, vendor names, and notes are free-text fields. If a CSV cell starts with `=`, `+`, `-`, or `@`, Excel and Google Sheets interpret it as a formula. A malicious (or accidentally named) product like `=HYPERLINK("http://evil.com","Click")` becomes a clickable link when the manager opens the sales report in Excel. CSV injection is a known OWASP issue; sales-management apps export customer data (PII) making this a data-exfiltration vector.

**Why it happens:**
PHP's `fputcsv` doesn't escape formula triggers. Developers assume CSV is "just text." The existing `Setting` model and product/customer names have no sanitization on input.

**How to avoid:**
In the reports phase: prefix every CSV cell that begins with `=`, `+`, `-`, `@`, tab, or carriage return with a single quote `'` (or a space) in the export helper. Build a single `CsvExporter` helper class that wraps `fputcsv` and applies the prefix to every field. Add a test exporting a product named `=CMD("calc")` and asserting the cell starts with `'`. Alternatively, offer only PDF export for PII reports and restrict CSV to aggregate/numeric data.

**Warning signs:**
- Opening a sales CSV in Excel prompts about "external data" or shows a formula bar with `=...`
- Customer notes field contains arbitrary text that could start with `=`
- No CSV sanitization helper exists in the reports module

**Phase to address:**
**Reports build phase.** Any CSV export must go through the sanitizing helper from the first export.

---

### Pitfall 9: Large report export exhausts memory — `SalesOrder::all()->toArray()` for a year of sales

**What goes wrong:**
A sales report for "last 12 months" across all stores can be tens of thousands of rows. If the report controller does `SalesOrder::with('items')->get()` and maps to an array for CSV/PDF, PHP hits the memory limit (default 128MB) and the request 500s. The existing `SalesOrderService::list()` paginates (good), but reports need the full dataset, and a developer will be tempted to drop pagination for export.

**Why it happens:**
Pagination is for display; export needs everything. The lazy path is `->get()->map()`. Without streaming, a 50k-row export loads the entire Eloquent collection into memory.

**How to avoid:**
Use streamed responses for CSV: `response()->stream(function () { ... fputcsv ... }, 200, $headers)` and query with `->chunk(500, function ($orders) { ... })` or `->cursor()`. For PDF, either paginate the PDF (one page per N rows) or pre-aggregate in SQL and render only summary rows. Add a test with a factory creating 5,000 sales orders and asserting the export route returns 200 without memory exhaustion.

**Warning signs:**
- Export route 500s on a large date range but works on a small one
- `memory_limit` warnings in `storage/logs/laravel.log` during export
- Export of "all time" sales hangs the browser

**Phase to address:**
**Reports build phase.** Build export with `->chunk()` / streamed response from day one; do not retrofit.

---

### Pitfall 10: Report reads sales-order totals while a POS sale is in-flight (no lock) → inconsistent profit margin

**What goes wrong:**
The profit-margin report reads `SalesOrder` totals and `Batch` costs. A POS sale in the same second is inside `DB::transaction()` deducting FIFO batches with `lockForUpdate()`. If the report query reads `SalesOrder::where('status','paid')->sum('total')` without a lock or a snapshot, it can read a half-committed state (order created, items not yet inserted, or batches decremented but `recalculateStock()` not yet run). The report shows a profit margin that doesn't match the dashboard, and a manager auditing the two sees a discrepancy.

**Why it happens:**
Reports are read-only, so developers assume no locking is needed. But the financial core writes under transactions. MySQL's default isolation (REPEATABLE READ) helps within a transaction, but a bare `->sum()` outside a transaction reads committed data at the moment of each row — not a consistent snapshot.

**How to avoid:**
Wrap report aggregation queries in `DB::transaction(function () { ... })` (read-only transactions get a consistent snapshot) OR accept that reports are "eventually consistent" and add a `as_of` timestamp to the report header ("Generated at 2026-06-21 14:32 — figures may differ from live totals"). For the profit-margin report specifically, compute COGS from the FIFO batches deducted (the `sold_quantity` × per-unit cost from the reception order), not from `purchase_price` (which is the vendor's price, not the landed cost). Decide which is canonical for THIS app: FIFO batch cost (includes reception freight/adjustments) is more accurate; `purchase_price` is simpler but ignores landed costs. Document the choice in the report footer.

**Warning signs:**
- Report total != dashboard total for the same period
- Profit margin fluctuates on re-running the same report within seconds
- Report includes orders with `status='draft'` that were being created at query time

**Phase to address:**
**Reports build phase.** Use read-only transactions for aggregation; document the COGS basis.

---

### Pitfall 11: API-layer deletion orphans a Resource still used by an Inertia controller's props → 500 on page render

**What goes wrong:**
The plan removes the unused API layer. If a developer deletes `CashRegisterShiftResource` (because "it's only used by the API controller which we're deleting") but the **Web** `CashRegisterShiftController` (or a future POS controller) still does `Inertia::render('...', ['shift' => new CashRegisterShiftResource($shift)])`, the page 500s with "Class not found." Same for any Resource that straddles web + API. The CONCERNS audit lists 24 Resource directories; some are used by web controllers, some only by API, some by both. Deleting by directory name without grepping web controllers breaks Inertia renders.

**Why it happens:**
Resources are shared between web and API layers more often than assumed. The audit says only ~10 API endpoints are used by Inertia, but that doesn't mean the Resources are API-only — web controllers use Resources for Inertia props too.

**How to avoid:**
Before deleting any Resource file, grep: `rg "CashRegisterShiftResource" app/Http/Controllers/` (not just `app/Http/Controllers/Api/`). Delete a Resource only if zero web controllers and zero remaining API controllers reference it. Same for Form Requests: grep `app/Http/Controllers/` (all of it) before removing a request class. Delete in order: **routes → API controllers → API Form Requests → API-only Resources → API composables → API Types**, verifying the web app still renders after each step (run `php artisan route:list` and hit a few Inertia pages). Keep a shared Resource if ANY web controller uses it.

**Warning signs:**
- `Class App\Http\Resources\X\XResource not found` on an Inertia page after API removal
- `php artisan route:list` shows a web route whose controller `__construct` type-hints a deleted Form Request
- Ziggy route cache (`resources/js/ziggy.js`) references a deleted API route name

**Phase to address:**
**Refactor/critical-fix phase (API removal).** Delete bottom-up (routes → controllers → requests → resources → composables → types), grepping web controllers at each step.

---

### Pitfall 12: Deleting an API route that a Vue page references via `route("api.v1.x.store")` → silent 404 on a dynamic fetch

**What goes wrong:**
The app uses Ziggy, which generates a JS route manifest from the live routes. A Vue page (e.g. a composable using `useApi()`) calls `route("api.v1.products.search")`. The API removal phase deletes the `api/v1/products/search` route. Ziggy regenerates the manifest on next page load, the named route is gone, and `route("api.v1.products.search")` throws "route not defined" at runtime — but only when the user triggers that specific dynamic fetch (search, autocomplete). The page renders fine; the error appears later, silently in the console, and the search box returns nothing.

**Why it happens:**
Dynamic fetches (product/variant search, customer search, batches available) are the ~10 endpoints the plan KEEPS. But the removal phase might delete a route that a composable uses if the route name is similar to a deleted one, or if a composable was written against an endpoint that's being consolidated. The CONCERNS audit lists the kept endpoints; anything outside that list that a composable references is a trap.

**How to avoid:**
Before deleting any API route, grep the frontend: `rg "api\.v1\." resources/js/`. For every hit, confirm the route is on the "keep" list. If a composable references a route being deleted, either (a) move the fetch to an Inertia partial-reload / deferred prop (preferred per PROJECT.md constraints), or (b) keep that specific route. After deletion, run `php artisan ziggy:generate` and `npm run type-check` — a missing route will surface as a TS error in the composable's `route()` call. Add a smoke test that visits each page with a dynamic fetch and asserts no 404.

**Warning signs:**
- Console error `route() is not defined` or `Ziggy error: route 'api.v1.x' not found`
- A search/autocomplete box returns empty after the refactor
- `npm run type-check` passes but the runtime still throws (Ziggy route names aren't type-checked)

**Phase to address:**
**Refactor/critical-fix phase (API removal).** Greppable before deletion; verify with `ziggy:generate` after.

---

### Pitfall 13: `useApi.ts` CSRF header bug breaks all kept API endpoints after the fix is applied wrong

**What goes wrong:**
`useApi.ts:12` sets `"X-XSRF-TOKEN": document.head.querySelector('meta[name="csrf-token"]')` — this sends an `HTMLMetaElement` (or null) as the header value, which is `[object HTMLMetaElement]`. It also uses the wrong token (the Laravel CSRF meta token, not the `XSRF-TOKEN` cookie value). The fix per CONCERNS.md is to delete the headers block (rely on `withXSRFToken: true`). BUT if the fix instead "corrects" it to `"X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content`, that's a different header (`X-CSRF-TOKEN` vs `X-XSRF-TOKEN`) and only works for web sessions, not Sanctum API tokens. The ~10 kept API endpoints use Sanctum (`auth:sanctum`), which expects `X-XSRF-TOKEN` from the cookie. Applying the wrong fix breaks every dynamic fetch (product search, customer search, POS session) with 419 CSRF mismatch.

**Why it happens:**
The CSRF token (meta tag, for web) and the XSRF token (cookie, for Sanctum SPA) are different tokens for different middleware. The original code confuses them; a naive "fix" that swaps header names without understanding the SPA auth flow makes it worse.

**How to avoid:**
Apply the CONCERNS.md fix verbatim: **delete the `headers` block entirely** and rely on `withXSRFToken: true` + `withCredentials: true` (already set, line 14-15). Do NOT add `X-CSRF-TOKEN`. After the fix, manually test a product search (which uses `useApi()`) and confirm 200, not 419. Add a Pest test that hits an `auth:sanctum` endpoint via the axios client and asserts 200.

**Warning signs:**
- 419 `CSRF token mismatch` on any `useApi()`-based fetch after the refactor
- Network tab shows `X-CSRF-TOKEN` instead of `X-XSRF-TOKEN`
- Login still works (web session) but product search fails (SPA auth)

**Phase to address:**
**Refactor/critical-fix phase — before POS build.** POS uses dynamic product search via `useApi()`; the CSRF path must be correct first.

---

### Pitfall 14: New POS/dashboard/reports code uses Inertia `useForm` for a cart/form (convention violation)

**What goes wrong:**
`.claude/rules/vue-frontend.md` mandates VeeValidate + Yup for create/edit forms; Inertia `useForm` is ONLY for delete/restore with empty body. The POS cart is a complex form. A developer (especially one used to other Inertia apps) will reach for `useForm` from `@inertiajs/vue3` for the cart because it has `processing` and `reset()` helpers. This violates the convention, breaks the VeeValidate validation schema pattern, and means field-level errors from the backend won't map cleanly to `setErrors()` (the VeeValidate path). The cart validation (quantities, payment amounts) needs Yup's `number().positive().required()` — `useForm` doesn't provide that.

**Why it happens:**
Inertia's `useForm` is the first result in the Inertia docs. The convention to prefer VeeValidate is project-specific and not obvious to a new contributor. The existing sales-order create page shows the correct pattern (`useForm` from `vee-validate`, line 5) but it's easy to miss.

**How to avoid:**
Mirror `resources/js/Pages/SalesOrders/Create/Index.vue` for the POS cart: `import { useForm } from "vee-validate"`, define a `toTypedSchema(object({...}))` with Yup, use `defineField` for each cart field. Use Inertia `router.post()` for submission (not `form.post()`). Add an ESLint rule or a code-review checklist item: "if `useForm` is imported from `@inertiajs/vue3` outside of a delete/restore handler, reject." The existing pages already follow this — copy them.

**Warning signs:**
- `import { useForm } from "@inertiajs/vue3"` in a POS cart or report filter form
- No Yup schema in a form that has validation
- Backend field errors not appearing next to the right input

**Phase to address:**
**POS build phase, Dashboard filter phase, Reports filter phase.** All three write forms; all three must use VeeValidate + Yup.

---

### Pitfall 15: Missing `PermissionsEnum` cases for POS/dashboard/reports → silent 403 or unguarded pages

**What goes wrong:**
The existing `PermissionsEnum` has cases like `POS_ACCESS`, but the new dashboard and reports modules need their own permissions (`DASHBOARD_VIEW`, `REPORTS_VIEW`, `REPORTS_SALES_VIEW`, etc. per `authorization.md`). Two failure modes:
1. **Missing cases → silent 403:** A web controller calls `$this->authorize(PermissionsEnum::REPORTS_VIEW)` but the case doesn't exist → PHP fatal error (class constant not found) on the first report visit. Or the case exists but isn't seeded in `PermissionSeeder` → `can('reports.view')` returns false for everyone → every user sees a 403 with no clear reason.
2. **Missing cases → unguarded pages:** The dashboard/reports controllers forget `$this->authorize(...)` entirely (easy to forget on read-only pages) → any logged-in user (including `salesman`) sees the manager dashboard with all-store KPIs.

**Why it happens:**
`authorization.md` lists the four-step process (add case → seed → sidebar → authorize) but read-only pages are easy to forget authorizing because "they don't write anything." The `v-can` directive in the sidebar hides the menu item for users without permission, which masks the missing backend check — the page is reachable by direct URL.

**How to avoid:**
In the POS/dashboard/reports phases: add `PermissionsEnum` cases first, run `php artisan db:seed --class=PermissionSeeder`, then write controllers with `$this->authorize(...)` in EVERY method (index, show, export). Add `v-can` to sidebar entries. Add a Pest test: a `salesman` user GETs `/reports` and asserts 403. Add another: an admin GETs `/reports` and asserts 200. The dashboard, being the manager landing page, should still be viewable by `salesman` (they see their own sales) — decide and document this.

**Warning signs:**
- `Error: Class App\Enums\PermissionsEnum not found` or `Constant not found` on first report visit
- A `salesman` can reach `/reports` by typing the URL
- Sidebar hides "Reports" but the route works directly
- `PermissionSeeder` wasn't re-run after adding cases

**Phase to address:**
**Each feature phase (POS, Dashboard, Reports) — at the start of each, before controllers.** Permissions are a prerequisite, not a finishing touch.

---

### Pitfall 16: Receipt printing from browser — silent failures, wrong paper size, ESC/POS vs HTML mismatch

**What goes wrong:**
POS receipt printing via `window.print()` on an HTML receipt: the browser's print dialog may be disabled by the POS kiosk setup, the paper size (58mm vs 80mm thermal roll) isn't set so the receipt spills onto a second page, and the browser doesn't auto-cut. If the team tries ESC/POS (raw thermal printer commands) over a web USB/network connection, the JS layer can't easily send raw bytes without a driver, and failures are silent (printer offline, paper out) — the cashier thinks the receipt printed and hands the customer nothing.

**Why it happens:**
Browser printing is designed for A4 letter, not 58mm thermal. ESC/POS requires a native helper or a printer with a web interface. There's no existing printing code in the app to copy from.

**How to avoid:**
Start with HTML + `window.print()` + a dedicated `@media print` CSS that sizes to 80mm (or 58mm) and hides everything except the receipt. Accept that the cashier clicks "Print" and confirms the dialog — do not attempt silent/auto printing in the first milestone. If silent printing is required later, integrate a print proxy (e.g. a local Node/Python service or QZ Tray) as a separate phase. Test on the actual thermal printer model. Include a "Reprint last receipt" button so a silent-failure recovery path exists. Log every print attempt via `activity()` so a missing receipt is traceable.

**Warning signs:**
- Cashier clicks "Print" but nothing comes out of the printer (silent failure)
- Receipt spans 2 pages (wrong paper size in CSS)
- Print dialog appears every sale (no kiosk print mode)
- Customer leaves without receipt, returns later complaining

**Phase to address:**
**POS build phase.** Start with HTML print; defer silent printing to a later milestone.

---

### Pitfall 17: Held/recalled order state leak — held items aren't stock-reserved, recalled order can oversell

**What goes wrong:**
`SalesOrderService::holdOrder()` (line 239-244) creates a sales order with `status='held'` and does NOT deduct stock (stock is only deducted on transition to `paid`, line 131-133). When the order is recalled (`resumeOrder` → transition to `draft`), the items are back in the cart. Meanwhile, another sale could have deducted the same variant's FIFO batches to zero. When the recalled order is paid, `deductForOrder()` throws `InvalidArgumentException("Insufficient stock...")`. The cashier is stuck: the held order can't be paid, and the customer already left. Conversely, if a developer "fixes" this by reserving stock on hold, the reserved stock is locked and unavailable to other sales until the held order is recalled or cancelled — a variant with 5 units and one held order for 5 can't be sold to anyone else, even if the held-order customer never returns.

**Why it happens:**
"Hold and recall" is a POS convenience feature with no clear stock-reservation semantics. The backend `TRANSITION_MAP` allows `draft → held → draft` but stock is only touched at `paid`. This is a design gap, not just a bug.

**How to avoid:**
Decide and document the reservation policy **before** building hold/recall: recommend **no reservation on hold** (stock is first-come-first-served; a recalled order may fail at payment if stock ran out — show a clear "insufficient stock, order cannot be completed" toast and let the cashier adjust quantities). Add a pre-check on recall: `FifoStockDeductionService` (or a `checkAvailability()` method) verifies stock exists before recalling, warning the cashier. Add a test: hold an order for 5 units, sell 5 units to another customer, recall the held order, attempt to pay → assert `InvalidArgumentException` is caught and shown to the cashier, not a 500. Do NOT reserve stock on hold in this milestone — it's a larger feature (reservation, expiry, release) and out of scope.

**Warning signs:**
- "Insufficient stock" 500 error when paying a recalled order that was held for hours
- Held orders locking stock so other sales fail (if reservation was added naively)
- Held orders accumulating indefinitely with no expiry/cancellation

**Phase to address:**
**POS build phase (hold/recall sub-feature).** Decide the policy in the plan, implement the availability pre-check, and test the stock-ran-out path.

---

### Pitfall 18: PHPStan baseline of 616 errors hides new violations — fixing them while adding POS/dashboard/reports creates noise vs. signal

**What goes wrong:**
`composer lint` runs PHPStan level 8 on every PHP edit (PostToolUse hook). The codebase has 616 pre-existing errors. Without a baseline, every `composer lint` run dumps hundreds of errors and a developer can't tell which are new. The CONCERNS fix plan says "fix the 464 test-property errors first" — but if the fix phase is running concurrently with POS/dashboard/reports feature phases, new code introduces new PHPStan errors that blend into the (shrinking) baseline. A real bug (e.g. a nullable model access in a new report resource) is invisible among 200 remaining pre-existing errors.

**Why it happens:**
No baseline file. The team plans to fix the 616 errors, but that's a large effort and runs parallel to feature work. Without a baseline, the signal-to-noise ratio is zero.

**How to avoid:**
In the critical-fix phase, **before** feature work: generate a PHPStan baseline (`vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon`), commit it, and configure `phpstan.neon` to `include: phpstan-baseline.neon`. Now `composer lint` only reports NEW errors above the baseline. As the fix phase resolves pre-existing errors, remove the corresponding entries from the baseline (don't regenerate — manually shrink it). When the baseline hits zero, delete the file. This gives clean signal during feature phases. The 9 TypeScript errors should get the same treatment (`tsc --noEmit` baseline or fix them first — they're only 9).

**Warning signs:**
- `composer lint` output is 600+ lines and developers ignore it
- A new PHPStan error in a report resource goes unnoticed for days
- The PostToolUse hook fires constantly during feature work, drowning real issues

**Phase to address:**
**Refactor/critical-fix phase — first task, before any feature work.** Generate the baseline on day one.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Copy the buggy `taxRate = 0` pattern into POS cart | Faster POS launch | Tax drift between POS and backend; payment rejection; duplicate bug | Never — fix the source first |
| `->get()->sum()` for dashboard KPIs | Quick KPI | N+1 at 100 orders; dashboard slow | Never — use `->sum()` / `->count()` with cache |
| Delete API routes without grepping frontend | Smaller API surface | Silent 404 on dynamic fetches | Never — grep `api.v1.` in `resources/js/` first |
| Reserve stock on hold order | Prevent oversell | Locked stock, dead inventory, complex expiry | Never this milestone — use availability pre-check instead |
| Skip `$this->authorize()` on report controllers (read-only) | Less code | Salesman sees all-store financials | Never — read-only still needs authorization |
| Use `moment-timezone` for the new dashboard date formatting | Consistent with existing | Bundle bloat, maintenance-mode lib | Acceptable this milestone (migration deferred per PROJECT.md) |
| Hardcode `CACHE_DRIVER=redis` in `.env` to skip the Cache::tags fix | Settings work | Deployment crashes on file driver; fix is still owed | Never — fix the code, don't pin the driver |
| Add new POS permissions to `PermissionsEnum` but forget `PermissionSeeder` re-run | Faster | Silent 403 for everyone | Never — re-seed after enum changes |
| Build reports with `SalesOrder::all()` (no chunking) | Simple | Memory exhaustion on large date range | Never — use `->chunk()` / streamed response |
| Compute profit margin from `purchase_price` not FIFO batch cost | Simpler query | Wrong margin (ignores landed costs) | Acceptable in MVP if documented; canonical is FIFO batch cost |

## Integration Gotchas

Common mistakes when connecting to external services / existing modules.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| POS ↔ `SalesOrderService::create()` | Sending `status='paid'` from the cart without confirming stock is available → `InvalidArgumentException` mid-transaction | Pre-check availability before allowing "Pay"; catch `InvalidArgumentException` in controller and show toast, don't 500 |
| POS ↔ `CashRegisterShiftService` | Opening a shift but not linking `cash_register_shift_id` on the sales order → shift reconciliation misses the sale | Pass `cash_register_shift_id` from the POS session into `SalesOrderService::create()` data; the service already accepts it (line 96) |
| Dashboard ↔ `StockAlertService::getSummary()` | Re-querying on every dashboard load (already injected by `HomeController`) | Reuse the existing `alertSummary` prop; add KPIs as additional props or a dedicated `DashboardService` |
| Reports ↔ `FifoStockDeductionService` | Reading `sold_quantity` for COGS without joining to the batch's per-unit cost (in `reception_order_products`) | Join `batches` → `reception_order_products` to get landed unit cost; `purchase_price` is not the COGS |
| Reports ↔ `Setting::get()` | Calling before the Cache::tags fix → `BadMethodCallException` | Fix the cache first; reports read settings (currency, timezone) on every render |
| API removal ↔ Ziggy | Deleting routes without regenerating `resources/js/ziggy.js` | Run `php artisan ziggy:generate` after every route deletion; rebuild Vite |
| API removal ↔ Inertia props | Deleting a Resource that a web controller passes to `Inertia::render()` | Grep `app/Http/Controllers/` (non-Api) for the Resource class name before deleting |
| POS receipt ↔ Browser print | Assuming `window.print()` works silently in a kiosk | Use HTML + `@media print` CSS for 80mm; accept manual print dialog; add reprint button |
| Dashboard chart ↔ Date ranges | Using `today()` (server TZ) for "today's sales" | Convert to store/user timezone via `Setting::get('timezone')` before `whereDate` |
| Reports CSV ↔ Excel | Exporting raw text without escaping `=`/`+`/`-`/`@` prefixes | Route all CSV cells through a sanitizing helper that prefixes triggers with `'` |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Dashboard `->get()->sum()` for today's sales | Dashboard >500ms, 100+ queries | `SalesOrder::whereDate(...)->sum('total')` single query + `Cache::remember(..., 60)` | ~100 orders/day |
| Sales-order list without eager `items` (existing N+1) | Sales list page slow | Add `'items.productVariant.product'` to `SalesOrderService::list()` eager load | ~20 orders per page |
| Report `->get()` on 12-month range | 500 / memory exhausted | `->chunk(500, fn($orders) => fputcsv(...))` streamed | ~10k orders |
| `ProductVariant::where('id', $id)->firstOrFail()->recalculateStock()` after every sale (existing) | Extra query per sale; 404 if variant deleted mid-tx | Pass loaded `$variant` into `recalculateStock()` | Always — latent bug |
| Ziggy route manifest regenerated on every page load (no static file) | Slight latency per page; stale routes after API deletion | `php artisan ziggy:generate` at build step | After API route deletion without regen |
| Dashboard chart loads 30 days of sales on every visit | Slow dashboard, repeated heavy queries | `Cache::remember('dashboard.trend_30d', 300, ...)` 5-min TTL | ~30 days of data, repeat visits |
| Soft-deleted records in default `'all'` list (existing) | List grows unbounded | Default to `'active'`, require explicit `?status=all` | Hundreds of soft-deleted records |
| `Setting::get()` uncached after Cache::tags removal (if not replaced with `rememberForever`) | DB hit per setting read; POS reads tax_rate per sale | Replace tags with `Cache::rememberForever("settings.{$key}", ...)` | Every POS sale |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Report controllers skip `$this->authorize()` (read-only) | Salesman sees all-store KPIs, profit margins, customer lists | Authorize every report route with `PermissionsEnum::REPORTS_*`; add Pest 403 test |
| CSV export of customer data without formula sanitization | Excel formula injection → data exfiltration via `=HYPERLINK` | Prefix `=`,`+`,`-`,`@`-prefixed cells with `'`; or restrict PII to PDF only |
| API removal deletes a route but leaves the controller method public | Orphaned method reachable if route re-added later or via `Route::fallback` | Delete controller methods, not just routes; or remove the controller class |
| POS cart submitted with client-side `total` trusted by backend | Client can send `total=0` and backend stores a free order | Backend MUST recompute totals via `SalesOrderService::calculateTotals()` (already does — verify POS doesn't pass `total` in payload) |
| Payment `amount` from frontend trusted | Client can submit `amount=9999` for a 100 order | Backend should ignore client payments total and recompute; or validate `abs(payments_sum - computed_total) < 0.01` server-side (the frontend check at line 67 is client-only) |
| `orderBy` injection in 11 services (existing) | Sort by `password`/`email` to infer data via side-channel | Add `SORT_COLUMN_MAP` whitelist per CONCERNS.md; critical before reports expose arbitrary sort |
| Held order recalled by different cashier | Recall lacks ownership check | `resumeOrder` should verify the recalling user is the original `user_id` or an admin; add to `SalesOrderService::resumeOrder` |
| `useApi()` CSRF header sending wrong token (existing) | CSRF bypass or 419 depending on middleware | Delete the headers block; rely on `withXSRFToken: true` |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Payment rejected with "Payments must equal order total" on a correctly-taxed order | Cashier can't complete sale; customer waits | Fix tax formula (frontend matches backend); show the taxed total prominently before payment |
| POS shift bar shows 0 movements (broken resource) | Manager thinks cash drawer balanced when it isn't | Fix `CashRegisterShiftResource` before POS shift UI |
| Receipt prints on A4 instead of 80mm thermal | Wasteful, unprofessional, slow | `@media print` CSS sized to 80mm/58mm; test on actual printer |
| Dashboard "today's sales" excludes evening sales (timezone drift) | Manager thinks day was slow; sales mismatch | Convert "today" to store timezone before querying |
| Report date range end-date is exclusive (misses last day) | "June 1-30" shows only through June 29 | Make end-date inclusive (`<= endOfDay`) and document it in the UI |
| Held order can't be paid because stock ran out | Cashier stuck mid-sale with no recovery | Availability pre-check on recall; clear error toast; allow quantity edit |
| Barcode scanner (keyboard wedge) vs camera scanner confusion | Cashier waits for camera, nothing scans | Support keyboard-wedge scanners (default — they type into any focused input); camera is a separate opt-in |
| KPIs that look impressive but are vanity metrics | Manager optimizes wrong metric | Show actionable KPIs (today's sales, low-stock count, cash variance) not vanity (total users, total products) |
| Cart state lost on page refresh (Pinia not persisted) | Cashier refreshes and loses the whole cart | Persist cart to `sessionStorage` via `pinia-plugin-persistedstate` (Pinia already used for POS) |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **POS sale:** Tax computed — verify it reads `useAuth().getSetting('sales','tax_rate')` and applies `/100`, NOT hardcoded `0`
- [ ] **POS sale:** Payment validation — verify it compares against the **taxed** total, and that the backend recomputes (doesn't trust client `total`)
- [ ] **POS shift close:** Movements shown — verify `CashRegisterShiftResource` returns non-empty movements (currently broken)
- [ ] **POS receipt:** Prints — verify on the actual thermal printer at 80mm, not just `window.print()` on A4
- [ ] **POS hold/recall:** Stock availability — verify a recalled order checks stock and handles "insufficient" gracefully, not a 500
- [ ] **Dashboard:** Today's sales KPI — verify it uses store timezone, not server UTC
- [ ] **Dashboard:** Cache invalidation — verify KPIs refresh after a sale (TTL or manual flush), not stale forever
- [ ] **Reports:** Date range — verify end-date is inclusive and documented in the UI
- [ ] **Reports:** CSV — verify formula-injection sanitization on at least one export
- [ ] **Reports:** Large export — verify a 12-month export doesn't 500 (streamed/chunked)
- [ ] **Reports:** Profit margin — verify COGS basis is documented (FIFO batch cost vs purchase price)
- [ ] **Reports:** Authorization — verify a `salesman` gets 403 on `/reports`
- [ ] **API removal:** Inertia pages still render — verify no `Class not found` after Resource deletion
- [ ] **API removal:** Dynamic fetches still work — verify product/customer search returns 200, not 404/419
- [ ] **Cache fix:** Settings read — verify `Setting::get('tax_rate')` works with `CACHE_DRIVER=file`
- [ ] **Cache fix:** Settings update — verify `Setting::set('tax_rate', 13)` then `get` returns `13.0` (no stale cache)
- [ ] **FIFO consolidation:** Both paths throw `InvalidArgumentException` — verify transfers don't throw `RuntimeException` uncaught
- [ ] **FIFO consolidation:** Batches auto-close at `remaining_quantity=0` — verify a POS-sold-to-zero batch is `closed`, not `active`
- [ ] **TRANSITION_MAP:** Shift close/force-close still work — verify after adding the map to `CashRegisterShiftService`
- [ ] **PHPStan baseline:** New errors are visible — verify `composer lint` reports only new violations above baseline

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Tax bug shipped to POS (payments rejected) | MEDIUM | Hotfix: read `tax_rate` setting, apply `/100`; redeploy; reprocess any orders saved with wrong tax via a one-off artisan command |
| CashRegisterShiftResource broken in production (movements missing) | LOW | Hotfix the resource line 37 (`$this->resource->relationLoaded` or `whenLoaded`); redeploy; no data loss (movements exist in DB) |
| FIFO divergence (unclosed POS batches) | HIGH | Write artisan command: `Batch::where('remaining_quantity',0)->where('status','active')->update(['status','closed'])`; consolidate code; re-run stock recalculation |
| Cache::tags crash on file driver | LOW | Apply the fix (remove tags, add per-key forget); `php artisan cache:clear`; redeploy |
| Stale cache after cache fix (old tagged entries) | LOW | `php artisan cache:clear` once; or use versioned keys |
| TRANSITION_MAP broke shift close | MEDIUM | Revert the map addition; re-add inline checks; re-introduce map with exact existing transitions + tests |
| API deletion broke an Inertia page (500) | LOW | Restore the Resource/route; grep web controllers before re-deleting |
| API deletion broke a dynamic fetch (404) | LOW | Restore the route or move fetch to Inertia partial-reload; regenerate Ziggy |
| CSRF fix broke product search (419) | LOW | Revert to `withXSRFToken: true` only (no manual headers); redeploy |
| CSV injection exported | MEDIUM | Add sanitizing helper; re-export any distributed CSVs; audit if any were opened in Excel |
| Report memory exhaustion | MEDIUM | Refactor to `->chunk()` / streamed; no data loss; rerun the export |
| Dashboard N+1 | MEDIUM | Replace `->get()->sum()` with `->sum()` + cache; add query count test |
| Timezone drift in KPIs | LOW | Fix the date helper; restate the affected KPIs (historical sales already correct in DB, only the aggregation was wrong) |
| Held order oversell (stock ran out on recall) | LOW | By design (no reservation); show clear error; allow quantity edit — no data recovery needed |
| PHPStan baseline never created | MEDIUM | Generate baseline now; commit; treat all pre-existing as ignored; only fix new violations until the baseline can be shrunk |

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls. Assumes the milestone ordering: **Phase 1 — Critical fixes & refactor (incl. API removal, PHPStan baseline, Cache, FIFO, TRANSITION_MAP, resources, tax, CSRF, conventions)** → **Phase 2 — POS** → **Phase 3 — Dashboard** → **Phase 4 — Reports**.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Tax bug (POS reuses buggy formula) | Phase 1 (fix in SalesOrder frontend + shared composable) | Pest test: order with `tax_rate=13` saves correct `tax_amount`; POS cart uses the composable |
| CashRegisterShiftResource broken | Phase 1 (fix resource) | Test: shift with movements serializes non-empty `movements` array |
| FIFO divergence | Phase 1 (consolidate) | Unit test: sell-to-zero closes batch; transfer-to-zero closes batch; both throw `InvalidArgumentException` |
| Cache::tags on file driver | Phase 1 (fix Setting) | Test: `Setting::get`/`set` works with `CACHE_DRIVER=file`; `php artisan cache:clear` run once |
| Stale cache after fix | Phase 1 (versioned keys or flush) | Test: set then get returns new value |
| TRANSITION_MAP for shifts | Phase 1 (add map) | Tests: each valid transition passes; each invalid throws; close/force-close unchanged |
| PHPStan baseline | Phase 1 (first task) | `composer lint` reports only new errors; baseline committed |
| API deletion orphans Resource | Phase 1 (API removal, bottom-up) | `php artisan route:list` clean; grep web controllers; smoke-test Inertia pages |
| API deletion orphans Ziggy route | Phase 1 (API removal) | `php artisan ziggy:generate`; `npm run type-check`; visit each page with dynamic fetch |
| CSRF fix wrong | Phase 1 (fix useApi.ts) | Manual: product search returns 200, not 419; Pest: auth:sanctum endpoint 200 |
| Payment-difference validation vs wrong total | Phase 1 (tax fix) + Phase 2 (POS uses shared composable) | POS payment screen accepts exact-tender payment on a taxed order |
| Dashboard N+1 on KPIs | Phase 3 (DashboardService with aggregation + cache) | Query count test: dashboard route fires ≤ N queries |
| Dashboard timezone drift | Phase 3 (introduce TZ-aware date helper) | Test with `America/La_Paz`: 23:30 sale appears in "today" |
| Dashboard stale data | Phase 3 (cache TTL or manual flush on sale) | After a sale, dashboard refresh shows updated KPI within TTL |
| Reports CSV injection | Phase 4 (sanitizing helper) | Test: export product named `=CMD(...)` → cell starts with `'` |
| Reports memory exhaustion | Phase 4 (streamed/chunked export) | Test: 5,000 orders export returns 200 |
| Reports date-range edge cases | Phase 4 (inclusive end date, TZ conversion) | Test: "June 1-30" includes June 30 23:59 in store TZ |
| Reports profit margin COGS | Phase 4 (document + use FIFO batch cost) | Test: margin matches manual calculation from batches × reception cost |
| Reports read during in-flight sale | Phase 4 (read-only transaction or "as_of" stamp) | Report header shows generation timestamp; totals match dashboard within TTL |
| Missing PermissionsEnum for POS/dashboard/reports | Phase 2/3/4 (start of each) | Pest: salesman 403 on reports; admin 200 |
| v-can gaps in new Vue pages | Phase 2/3/4 (review per phase) | Manual: salesman doesn't see "Reports" sidebar; admin does |
| Inertia useForm used for cart (convention) | Phase 2 (POS) | ESLint/review: `useForm` from `@inertiajs/vue3` only in delete/restore |
| Receipt printing silent failure | Phase 2 (HTML print + reprint button) | Manual: print on thermal printer; reprint works; print attempts logged |
| Held/recall state leak | Phase 2 (availability pre-check, no reservation) | Test: hold 5, sell 5 elsewhere, recall → clear error, not 500 |
| Cart state desync (Pinia vs server) | Phase 2 (server is source of truth; persist cart) | Backend recomputes totals; cart persists to sessionStorage |
| Mass-assignment in 2 API controllers | Phase 1 (add API Form Requests or delete the controllers) | If kept: `$request->validated()`; if deleted: controllers gone |
| Missing authorize() in 5 API controllers | Phase 1 (add authorize or delete) | If kept: each action authorizes; if deleted: N/A |
| orderBy injection (11 services) | Phase 1 (SORT_COLUMN_MAP) — critical before reports expose sort | Test: `?order_by=password` falls back to whitelist column |
| Broad `catch (Exception)` (12 controllers) | Phase 1 (narrow to InvalidArgumentException) | Review: no `$e->getMessage()` exposed for non-business exceptions |
| LogsActivity missing on 7 models | Phase 1 (add trait) | If report snapshot tables added later (Phase 4), they also get LogsActivity |
| `casts()` vs `$casts` on 2 models | Phase 1 (convert) | PHPStan clean; consistent with other 28 models |

## Sources

- `.planning/codebase/CONCERNS.md` — full audit of existing defects (the pitfalls are grounded in this)
- `.planning/codebase/CONCERNS.md` Fragile Areas section — Sales Order Tax Calculation (Frontend/Backend Drift), Cache Configuration Mismatch, ReceptionOrder claimed quantities
- Direct file reads: `app/Services/SalesOrderService.php` (calculateTotals line 269, TRANSITION_MAP, holdOrder line 239), `app/Services/FifoStockDeductionService.php` (no auto-close), `app/Services/BatchService.php` (deductFIFO/deductFIFOForTransfer with auto-close + RuntimeException), `app/Models/Setting.php` (Cache::tags lines 41,59,69), `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php` (broken line 37), `app/Http/Resources/StockTransfer/StockTransferResource.php` (toISOString on string dates), `resources/js/Pages/SalesOrders/Create/Index.vue` (taxRate=0 line 53, payment validation line 67-70), `resources/js/Composables/useApi.ts` (CSRF header line 12), `app/Services/CashRegisterShiftService.php` (inline status checks, no TRANSITION_MAP), `app/Services/StockTransferService.php` (uses BatchService::deductFIFOForTransfer)
- `.claude/rules/laravel-backend.md` — TRANSITION_MAP requirement, InvalidArgumentException convention, LogsActivity mandate
- `.claude/rules/vue-frontend.md` — VeeValidate + Yup mandate, Inertia useForm only for delete/restore
- `.claude/rules/authorization.md` — four-step permission addition process, enforcement points
- `.claude/rules/routes-and-api.md` — API response format, Ziggy usage
- OWASP CSV Injection guidance (formula injection via `=`,`+`,`-`,`@` prefixes)
- Common Laravel POS/dashboard/reports domain knowledge: browser receipt printing limitations, timezone drift in KPIs, FIFO stock reservation semantics, CSV memory exhaustion on large exports

---
*Pitfalls research for: Sales-management app — POS + Dashboard + Reports addition and refactor*
*Researched: 2026-06-21*