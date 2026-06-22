# Phase 01: Critical Fixes & Refactor - Research

**Researched:** 2026-06-21
**Domain:** Laravel 12 / Inertia.js / Vue 3 bug-fix and refactor phase
**Confidence:** HIGH

## Summary

Phase 1 repairs the existing financial core and critical defects across the sales-management application so that later feature work (POS, Dashboard, Reports) builds on trustworthy services. The phase is strictly scoped to the 22 `FIX-XX` requirements (`FIX-01` through `FIX-22`); no new user-visible capabilities, no API-layer removal (that is Phase 2), and no convention-alignment work such as the `casts()` migration or `LogsActivity` rollout (that is Phase 3).

Research grounded every fix in source code, conventions, and the current runtime. Key findings:
- `composer lint` / `vendor/bin/phpstan analyse` currently reports **0 errors** — the 616-error baseline referenced in `CONCERNS.md` is stale, so the phase does not need to fight a noisy static-analysis baseline. `npm run type-check` still reports 17 errors (8 internal PrimeVue declaration gaps and 9 application-level errors that are intentionally deferred to Phase 3).
- The biggest structural change is `FIX-11` FIFO consolidation: `FifoStockDeductionService` becomes the single source of truth, `BatchService::deductFIFO()` (dead code) is removed, and `BatchService::deductFIFOForTransfer()` becomes a thin delegate. The transaction-ownership contracts are intentionally mixed (`deductForOrder` caller-owned, `deductForTransfer` self-contained) per the locked decision in `01-CONTEXT.md`.
- `FIX-18` (`Setting` cache) is a runtime correctness issue: `Cache::tags(['settings'])` throws `BadMethodCallException` on the default `file` driver (and on the `array` driver used in tests). The fix keeps `Cache::rememberForever()` semantics but switches to key-based invalidation.
- `FIX-14` (narrowing `catch (Exception)` to `catch (InvalidArgumentException)`) has a hidden dependency: several services (`CategoryService::delete`, `VendorService::delete`, `MeasurementUnitService::delete`, `BrandService::delete`, `CustomerService::delete`, `ProductService::delete`) currently throw generic `Exception` for business-rule guards. Those must be converted to `InvalidArgumentException` at the same time, or the narrowed catches will stop catching them.
- `FIX-22` log rotation affects `laravel.log` (daily driver is already configured but the default `stack` uses `single`) and `browser.log`. The 310 MB `browser.log` is produced by Laravel Boost registering a `browser` Monolog channel that dumps Vue warnings, Inertia page data, and Vite polling messages to a single unbounded file.

**Primary recommendation:** Treat the phase as a surgical pass — fix the 22 items in place, keep public method signatures stable wherever the phase description requires it, run `composer lint` and `npm run type-check` after all changes, and do not expand scope into API deletion or convention alignment.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **Phase Boundary:** Only `FIX-01..FIX-22` are in scope. No new user-visible capabilities, no new modules, no API-layer removal, no `casts()` migration or `LogsActivity` rollout.
- **FIFO Consolidation (FIX-11):**
  - `FifoStockDeductionService` is the canonical FIFO implementation.
  - `BatchService::deductFIFO()` (no callers) is **deleted**.
  - `BatchService::deductFIFOForTransfer()` becomes a thin delegate to `FifoStockDeductionService::deductForTransfer()` while keeping its existing public signature so `StockTransferService` wiring does not change.
  - `FifoStockDeductionService::deductForOrder(SalesOrder $order): void` — existing method, fixes the auto-close-at-zero gap, **caller owns the transaction** (no internal `DB::transaction()`).
  - `FifoStockDeductionService::deductForTransfer(int $variantId, int $storeId, int $quantity): void` — **NEW**, opens its own `DB::transaction()` internally.
  - Both methods throw `InvalidArgumentException` on insufficient stock and auto-close batches when `remaining_quantity` reaches 0.
- **FIX-07 Tax Frontend:** Read `useAuth().getSetting('sales', 'tax_rate', 0)` and apply `/100` to match `SalesOrderService`. Payment-difference validation must compare against the **taxed** total.
- **FIX-08 CSRF Header:** Delete the broken `X-XSRF-TOKEN` headers block in `useApi.ts`; rely on `withXSRFToken: true` + `withCredentials: true`.
- **FIX-15 AuthServiceProvider:** Remove the dead `StockAdjustment::class => StockAdjustmentPolicy::class` mapping (Laravel auto-discovers policies), not add imports.
- **FIX-18 Setting Cache:** Replace `Cache::tags(['settings'])` with key-based `Cache::rememberForever("settings.{$key}", ...)` and `Cache::forget("settings.{$key}")` / `Cache::forget("settings.group.{$group}")` on writes; works on the default `file` driver.
- **FIX-21 recalculateStock():** Pass the already-loaded `ProductVariant` into `recalculateStock()` (signature change acceptable) or use `find()` with an `InvalidArgumentException` null check. Must NOT throw `ModelNotFoundException` for a missing variant.
- **FIX-22 Log Rotation:** Configure `config/logging.php` daily driver; investigate and bound the 310 MB `browser.log`.

### the agent's Discretion

For all FIX items not explicitly decided above, follow the fix approach in `.planning/codebase/CONCERNS.md` and `.claude/rules/*.md` directly. This includes:
- Creating API Form Requests for Vendors/PurchaseOrders and returning `PurchaseOrderResource` (FIX-01, FIX-09), even though those controllers are deleted in Phase 2.
- Adding `$this->authorize(PermissionsEnum::X->value, auth()->user())` to the 5 API controllers with no authorization (FIX-02), even though some are deleted in Phase 2.
- Fixing `CashRegisterShiftResource` with `$this->whenLoaded(...)` (FIX-03), `StockTransferResource` date/`identifier` handling (FIX-04/05), status enum comparisons (FIX-06), `ApiCollection` pagination meta (FIX-10), `TRANSITION_MAP` in `CashRegisterShiftService` (FIX-12), per-service `SORT_COLUMN_MAP` (FIX-13), narrow catches (FIX-14), claimed-quantities extraction (FIX-16), dead `validated()` removal (FIX-17), and N+1 eager loads (FIX-19/20).

### Deferred Ideas (OUT OF SCOPE)

- API layer deletion (`API-01..API-08`) → Phase 2.
- `casts()` migration + `LogsActivity` rollout (`CONV-01..CONV-02`) → Phase 3.
- TypeScript error fixes, Pest property wiring, PHPStan baseline, forbidden-assertion verb fixes (`CONV-03..CONV-07`) → Phase 3.
- POS / Dashboard / Reports feature work → Phases 4–6.
- CI/CD pipeline, `moment-timezone` replacement, multi-server scaling → out of milestone scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| FIX-01 | `Api/VendorsController` and `Api/PurchaseOrdersController` stop using `$request->all()`; use new API Form Requests and `$request->validated()`. | Web Form Requests exist for both modules and can be mirrored under `app/Http/Requests/Api/`. `PurchaseOrderResource`/`Collection` already exist. |
| FIX-02 | 5 API controllers (`ActivityLog`, `Batches`, `Permissions`, `PurchaseOrders`, `Settings`) add `authorize()` per action. | `VendorsController` already demonstrates the required API pattern. `PermissionsEnum` cases exist for the domains. |
| FIX-03 | `CashRegisterShiftResource` uses `$this->whenLoaded()` so movements are no longer silently dropped. | Other resources already use the `whenLoaded` + fallback pattern. |
| FIX-04 | `StockTransferResource` no longer calls `toISOString()` on string-cast dates. | `StockTransfer` model casts `created_at`/`updated_at` as formatted strings; either change model casts or handle strings in the resource. |
| FIX-05 | `ProductVariant` `sku`/identifier reference fixed in `StockTransferResource`. | `ProductVariant` has no `sku` column; the actual field is `identifier`. |
| FIX-06 | Status comparisons standardized to enum-to-enum on the model side; `->value` only for DB writes / raw input. | Most code already follows this; FIX-12 will replace the remaining inline `->value` checks in `CashRegisterShiftService`. |
| FIX-07 | Sales order frontend reads `tax_rate` from settings and applies `/100`, matching backend. | `useAuth().getSetting()` can read group/key settings; backend formula is `(subTotal - discount) * (taxRate / 100)`. |
| FIX-08 | `useApi.ts` stops sending a DOM element as `X-XSRF-TOKEN`. | Axios `withXSRFToken: true` handles the header automatically; remove the broken `headers` block. |
| FIX-09 | `Api/PurchaseOrdersController` returns `PurchaseOrderResource` instead of raw models. | `PurchaseOrderResource` and `PurchaseOrderCollection` already exist. |
| FIX-10 | `ApiCollection` includes pagination `meta` matching `UserCollection`. | `UserCollection` provides the canonical `meta` block and `paginationInformation()` override. |
| FIX-11 | Duplicate FIFO logic consolidated into `FifoStockDeductionService`. | Locked decisions cover exact contract (caller-owned vs self-contained txn, auto-close, `InvalidArgumentException`). |
| FIX-12 | `CashRegisterShiftService` gets `TRANSITION_MAP` + `validateTransition()`. | `PurchaseOrderService`/`SalesOrderService`/`StockTransferService` already provide the pattern. |
| FIX-13 | 11 services whitelist user input to `orderBy()` via per-service `SORT_COLUMN_MAP`. | `StockService::SORT_COLUMN_MAP` is the canonical safe pattern; `CatalogService` already uses a `match()` whitelist for one method. |
| FIX-14 | 12 web controllers catch `InvalidArgumentException` specifically instead of base `Exception`. | `laravel-backend.md` convention; several services must also switch from generic `Exception` to `InvalidArgumentException`. |
| FIX-15 | `AuthServiceProvider` dead `StockAdjustment` mapping removed. | Laravel auto-discovers policies; every other policy in the app is not registered. |
| FIX-16 | `ReceptionOrderController` claimed-quantities logic extracted into `ReceptionOrderService`. | Service already has `getClaimedQuantities()` for internal use; extend/reuse it for both controller methods. |
| FIX-17 | Dead `$request->validated()` calls removed or captured and used in `Api/RoleController` and `Api/MeasurementUnitController`. | `MeasurementUnitController` already uses `$validated` in store/update; remove the dead index() call. |
| FIX-18 | `Setting` model stops using `Cache::tags()`; uses key-based invalidation. | `Cache::tags()` throws on `file`/`array` drivers; verified experimentally. |
| FIX-19 | N+1 in `ReceptionOrderResource` fixed via eager loads in `ReceptionOrderService`. | Missing `lineItems.productVariant.product.measurementUnit` and `lineItems.catalogEntry.unit`. |
| FIX-20 | N+1 in `SalesOrderResource` fixed via eager loads in `SalesOrderService`. | Missing `items` and `items.productVariant.product`. |
| FIX-21 | `recalculateStock()` call sites pass the already-loaded `ProductVariant` or null-check with `InvalidArgumentException`. | Re-query via `firstOrFail()` is spread across 5 services; signature change is acceptable. |
| FIX-22 | Log rotation configured; 310 MB `browser.log` investigated and bounded. | `daily` channel already defined; `browser.log` is an unbounded Laravel Boost Monolog channel. |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Tax calculation consistency (FIX-07) | Frontend (Vue) | API / Backend | Backend already computes correctly; frontend must mirror the same formula and read the live setting. |
| FIFO stock deduction (FIX-11) | API / Backend | Database | Business rule lives in `FifoStockDeductionService`; batch rows are mutated under `DB::transaction()` with `lockForUpdate()`. |
| Cash register shift state machine (FIX-12) | API / Backend | — | `CashRegisterShiftService` owns transitions and business-rule validation. |
| Resource serialization (FIX-03/04/05/09/10) | API / Backend | — | Eloquent Resources transform models for both Inertia props and API JSON. |
| API authorization / mass-assignment (FIX-01/02) | API / Backend | — | Form Requests + Gates/Policies enforce authorization and input validation at the API boundary. |
| Settings caching (FIX-18) | API / Backend | — | `Setting` model wraps cache reads/writes; key-based invalidation is a backend concern. |
| CSRF header for API calls (FIX-08) | Browser / Client | — | Axios configuration runs in the browser; broken header is a client-side bug. |
| Order-by whitelisting (FIX-13) | API / Backend | — | Service-layer `list()` methods sanitize user input before it reaches SQL. |
| Web controller error handling (FIX-14) | API / Backend (Web controllers) | — | Controllers decide which business exceptions to surface vs. propagate to the global handler. |
| Query N+1 prevention (FIX-19/20) | API / Backend | — | Eager-loading belongs in service `list()`/`show()` methods. |
| Log rotation (FIX-22) | API / Backend | Infrastructure/OS | `config/logging.php` defines channels; OS/logrotate or Monolog rotation bounds files. |

## Standard Stack

Phase 1 installs **no new external packages**. All fixes are implemented with the existing stack.

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel framework | ^12.0 (`12.56.0` resolved) | Backend framework, Eloquent, routing, cache, validation | Project baseline per `AGENTS.md` / `PROJECT.md` [VERIFIED: composer.lock] |
| Inertia.js | server `^1.0`, client `@inertiajs/vue3 ^2.0.17` | Server-driven SPA glue | Project baseline [VERIFIED: composer.lock/package.json] |
| Vue | ^3.5.18 | Frontend framework | Project baseline [VERIFIED: package.json] |
| PrimeVue | ^4.3.3 | UI component library | Project baseline [VERIFIED: package.json] |
| Tailwind CSS | ^3 | Utility CSS | Project baseline [VERIFIED: package.json] |
| Pest | ^3.8 | PHP testing | Project baseline [VERIFIED: composer.json] |
| spatie/laravel-permission | ^6.20 | Role/permission authorization | Project baseline [VERIFIED: composer.lock] |
| spatie/laravel-activitylog | ^4.11 | Audit trail (`LogsActivity`) | Project baseline [VERIFIED: composer.lock] |
| tightenco/ziggy | (resolved) | Named routes in JS | Project baseline [VERIFIED: composer.lock] |
| laravel/sanctum | (resolved) | API token auth | Project baseline [VERIFIED: composer.lock] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| VeeValidate + @vee-validate/yup | ^4.15.1 / ^4.15.1 | Vue form validation | Already used for create/edit forms; not changed in Phase 1 [VERIFIED: package.json] |
| axios | ^1.6.1 | HTTP client for API composables | FIX-08 only changes configuration, not the library [VERIFIED: package.json] |
| moment-timezone | (resolved) | Date/time formatting | Existing; not replaced in this phase [VERIFIED: package.json] |

### Package Legitimacy Audit

No new packages are installed in Phase 1. Existing packages are all from the project lock files and have already been vetted by the project. No [SUS] or [SLOP] packages are introduced.

## Architecture Patterns

### System Architecture Diagram

```
Browser (Vue 3 + Inertia)
    │
    ├─ useApi.ts ──── Axios ─────┐
    │                              ▼
    │                       API routes (routes/api.php)
    │                              │ auth:sanctum
    ▼                              ▼
Inertia pages (routes/web.php)  API controllers
    │                              │
    ▼                              ▼
Web controllers ─────────────── Form Requests (validation + authorization)
    │                              │
    └──────────────┬─────────────┘
                   ▼
            Service layer
            (business logic, DB::transaction)
                   │
                   ▼
            Eloquent models + Resources
                   │
                   ▼
            Database (MySQL dev / SQLite tests)
```

Data flow for a typical Phase 1 fix (e.g., FIX-07 tax):
1. `HandleInertiaRequests` middleware shares settings via `Setting::get()` → FIX-18 makes this work on `file` driver.
2. `SalesOrders/Create/Index.vue` reads the shared setting through `useAuth().getSetting('sales', 'tax_rate', 0)`.
3. The computed `taxAmount` uses the same `(subTotal - discount) * (taxRate / 100)` formula as `SalesOrderService`.
4. Payment-difference validation compares against the **taxed** total before `Inertia.router.post()` sends the order.
5. `SalesOrderService::create()` recomputes totals server-side (client total is not trusted) and persists.

### Recommended Project Structure (no new layers)

Phase 1 touches files inside the existing module pattern only:

```
app/
├── Http/
│   ├── Controllers/{Module}/        # Web controllers — FIX-14 catch narrowing
│   ├── Controllers/Api/{Module}/      # API controllers — FIX-01/02/09
│   ├── Requests/Api/{Module}/        # New API Form Requests — FIX-01
│   └── Resources/{Module}/           # Resource fixes — FIX-03/04/05/09/10
├── Services/                         # Business logic — FIX-11/12/13/16/18/19/20/21
├── Models/                           # Setting cache fix — FIX-18
└── Providers/                        # AuthServiceProvider cleanup — FIX-15
resources/js/
├── Composables/                      # useApi.ts fix — FIX-08
└── Pages/SalesOrders/Create|Edit/    # Tax frontend fix — FIX-07
config/
└── logging.php                       # Log rotation — FIX-22
```

### Pattern 1: Service `list()` with `SORT_COLUMN_MAP`
**What:** Every service `list()` method that accepts user-supplied `orderBy` whitelists the column through a private constant and validates direction to `asc`/`desc` only.
**When to use:** Any paginated list endpoint that accepts `?order_by=` / `?order_direction=`.
**Example:** `StockService::listStockOverview()` is the canonical implementation:

```php
// Source: app/Services/StockService.php (verified via codebase read)
private const SORT_COLUMN_MAP = [
    'product_name' => 'products.name',
    'brand_name' => 'brands.name',
    'total_stock' => 'product_variants.stock',
    'identifier' => 'product_variants.identifier',
    'price' => 'product_variants.price',
];

$sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'products.name';
```

### Pattern 2: `TRANSITION_MAP` + `validateTransition()`
**What:** Services that manage status changes define a private `TRANSITION_MAP` constant and a private `validateTransition()` helper that throws `InvalidArgumentException` for illegal transitions.
**When to use:** `CashRegisterShiftService` (FIX-12); already used by `PurchaseOrderService`, `SalesOrderService`, `StockTransferService`.
**Example:** `PurchaseOrderService` provides the pattern to mirror.

### Pattern 3: API Resource Response
**What:** API controllers return `(new ModuleResource($model))->response()->setStatusCode(201)` for single resources and a `ResourceCollection` subclass with a manual `meta` block for lists.
**When to use:** FIX-09 and FIX-10.
**Example:** `UserCollection` provides the canonical `meta` shape.

```php
// Source: app/Http/Resources/User/UserCollection.php (verified via codebase read)
public function toArray(Request $request): array
{
    return [
        'data' => $this->collection,
        'meta' => [
            'current_page' => $this->resource->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'per_page' => $this->resource->perPage(),
            'total' => $this->resource->total(),
        ],
    ];
}
```

### Anti-Patterns to Avoid
- **Broad `catch (Exception $e)` exposing raw messages:** Replaced by `catch (InvalidArgumentException $e)` and a generic global handler for everything else.
- **Re-querying a model that is already loaded:** Prefer passing the loaded instance (FIX-21) to avoid `ModelNotFoundException` mid-transaction.
- **Using `Cache::tags()` without a tag-supporting driver:** The default `file` driver and the test `array` driver both throw `BadMethodCallException`.
- **String-vs-enum status comparisons on enum-cast model properties:** Compare enum-to-enum (`$order->status === SalesOrderStatus::DRAFT`); use `->value` only for DB writes or raw input.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| FIFO stock deduction | A second copy inside `BatchService` | `FifoStockDeductionService` as the single canonical implementation | Eliminates behavioral drift in exception types, counters, and close-at-zero logic. |
| Settings cache invalidation | Manual tag flushing on a non-tagging driver | Key-based `Cache::rememberForever()` + explicit `Cache::forget()` | `file` and `array` drivers do not support `Cache::tags()`. |
| API authorization gating | Inline role checks or skipping authorization | `$this->authorize(PermissionsEnum::X->value, auth()->user())` + policies | Matches project convention and is auditable. |
| Pagination metadata | Custom pagination math in controllers | Override `paginationInformation()` / manual `meta` in `ResourceCollection` | Laravel already computes `current_page`, `last_page`, `per_page`, `total`. |
| Order-by whitelisting | Raw string concatenation or `match()` duplicated everywhere | Per-service `SORT_COLUMN_MAP` constant | Prevents SQL injection/side-channel sorting by sensitive columns; matches `StockService`. |
| Cash-register shift state machine | Inline `if ($status->value !== ...)` scattered across methods | `TRANSITION_MAP` + `validateTransition()` | Centralizes transition rules and simplifies testing. |

## Runtime State Inventory

Phase 1 is a code-level refactor with no database schema changes, no renamed entities, and no migration of stored data. The only runtime state of note is the `browser.log` file.

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | None — no data migration needed. | None. |
| Live service config | None — no external service configuration references the strings being changed. | None. |
| OS-registered state | None — no OS-level registrations depend on the touched code. | None. |
| Secrets/env vars | `CACHE_DRIVER=file` in `.env.example` will now work after FIX-18. `CACHE_DRIVER=redis` in local `.env` currently masks the bug; test with `CACHE_DRIVER=file` or `array` to verify. | Optional local verification; no env key renames. |
| Build artifacts / installed packages | `storage/logs/browser.log` (310 MB) and `storage/logs/laravel.log` (10 MB) are gitignored but consume disk. `browser.log` must be bounded by logging configuration/rotation. | Add daily/stack channel config and rotate/truncate the existing file if needed. |

## Common Pitfalls

### Pitfall 1: Converting `catch (Exception)` to `catch (InvalidArgumentException)` breaks delete guards
**What goes wrong:** Several `*Service::delete()` methods throw generic `Exception` for business-rule violations. If controllers are narrowed to `InvalidArgumentException` without also changing those services, delete guards will bubble to the global handler and show a generic error instead of the intended message.
**Why it happens:** The controller/service pair was written inconsistently — the controller convention changed but the service exception type did not.
**How to avoid:** Audit every service method called inside the 12 controllers' `try` blocks. Convert service `throw new Exception(...)` guards to `throw new InvalidArgumentException(...)`. Known files: `CategoryService::delete`, `VendorService::delete`, `MeasurementUnitService::delete`, `BrandService::delete`, `CustomerService::delete`, `ProductService::delete`.
**Warning signs:** `composer lint` passes but a delete action that should show a business message now shows a generic 500 page.

### Pitfall 2: Auto-closing batches inside a caller-owned transaction locks rows longer
**What goes wrong:** `FifoStockDeductionService::deductForOrder()` auto-closes batches at zero inside the same `DB::transaction()` that `SalesOrderService::create()`/`update()` already opened. Holding `lockForUpdate()` rows while payment/other side effects run may increase lock contention.
**Why it happens:** The consolidation moves the close logic into the canonical service but keeps it inside the caller's transaction boundary.
**How to avoid:** Keep the close logic minimal (single `update(['status' => 'closed'])` after `refresh()`). The phase scope intentionally does not redesign the transaction boundary; monitor tests for deadlock timeouts.
**Warning signs:** Pest tests around paid sales orders start timing out on SQLite/MySQL.

### Pitfall 3: `Setting` cache key invalidation misses stale group cache
**What goes wrong:** `Setting::get('tax_rate')` caches under `settings.tax_rate` while `Setting::getGroup('sales')` caches under `settings.group.sales`. Writing `tax_rate` must forget both keys or the group cache will remain stale.
**Why it happens:** Two separate cache key families serve single-key and group-key reads.
**How to avoid:** On every write (`set()` / `setGroup()` / `SettingsService::updateGroup()`), call `Cache::forget("settings.{$key}")` and `Cache::forget("settings.group.{$group}")`. Also update `Api\SettingsController::update` which currently calls `Cache::forget('settings')` (a non-existent key).
**Warning signs:** ROADMAP success criterion #2 fails: `Setting::set('tax_rate', 13)` followed by `get()` returns the old value.

### Pitfall 4: `Api/PurchaseOrdersController` fix is thrown away in Phase 2
**What goes wrong:** The controller is deleted in Phase 2, so investing extra effort beyond the requirement is wasteful.
**Why it happens:** The requirements list FIX-01/02/09 for this controller even though it will be removed.
**How to avoid:** Apply the minimum viable fix (create the API Form Requests, return the existing `PurchaseOrderResource`, add `authorize()`) and do not polish unrelated endpoints. Do not add new tests for endpoints that will disappear.
**Warning signs:** Time spent writing dedicated PurchaseOrders API tests.

### Pitfall 5: `browser.log` re-grows immediately after rotation
**What goes wrong:** Adding a `daily` channel only helps `laravel.log`. `browser.log` is written by Laravel Boost's custom `browser` Monolog handler; if the handler remains unconfigured, it will continue appending to a single file.
**Why it happens:** `browser.log` is not a configured channel in `config/logging.php`; it is created at runtime by the Boost service provider.
**How to avoid:** Either register a `browser` channel with the `daily` driver in `config/logging.php` and point the Boost handler at it, or add logrotate/os-level rotation for `storage/logs/browser.log`. Also investigate whether the volume of Vue warnings/Inertia dumps can be reduced.
**Warning signs:** File size returns to hundreds of MB within hours of truncation.

### Pitfall 6: `npm run type-check` still fails after frontend fixes
**What goes wrong:** 8 of the 17 TypeScript errors are internal PrimeVue 4 module declaration gaps (`primevue/confirmationeventbus`, etc.). They cannot be fixed by application code and are deferred to Phase 3.
**Why it happens:** PrimeVue 4 package-level `.d.ts` is incomplete.
**How to avoid:** Do not treat PrimeVue declaration errors as Phase 1 regressions. The 9 application-level errors are also intentionally deferred (CONV-03).
**Warning signs:** Fixing the 2–3 TypeScript files touched by Phase 1 does not bring `npm run type-check` to zero.

## Code Examples

### Canonical FIFO deduction pattern (FIX-11)
```php
// Source: app/Services/FifoStockDeductionService.php (to be extended)
public function deductForOrder(SalesOrder $order): void
{
    // Caller already owns DB::transaction() (SalesOrderService::create/update)
    $uniqueVariantIds = [];

    foreach ($order->items as $item) {
        $remaining = $item->quantity;
        $batches = Batch::query()
            ->where('product_variant_id', $item->product_variant_id)
            ->where('store_id', $item->store_id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($batch->remaining_quantity, $remaining);
            $batch->decrement('remaining_quantity', $deduct);
            $batch->increment('sold_quantity', $deduct);
            $remaining -= $deduct;

            $batch->refresh();
            if ($batch->remaining_quantity === 0) {
                $batch->update(['status' => 'closed']);
            }

            $uniqueVariantIds[$item->product_variant_id] = true;
        }

        if ($remaining > 0) {
            $sku = ProductVariant::find($item->product_variant_id)?->identifier ?? "ID {$item->product_variant_id}";
            throw new InvalidArgumentException("Insufficient stock for variant {$sku}: requested {$item->quantity}, available {$batches->sum('remaining_quantity')}.");
        }
    }

    foreach (array_keys($uniqueVariantIds) as $variantId) {
        $variant = ProductVariant::find($variantId);
        if ($variant) {
            $variant->recalculateStock();
        }
    }
}
```

### `TRANSITION_MAP` + `validateTransition()` (FIX-12)
```php
// Source: pattern from PurchaseOrderService/SalesOrderService (verified via codebase read)
private const TRANSITION_MAP = [
    'open' => ['closed', 'forced_close'],
    'closed' => [],
    'forced_close' => [],
];

private function validateTransition(string $from, string $to): void
{
    $allowed = self::TRANSITION_MAP[$from] ?? [];
    if (! in_array($to, $allowed, true)) {
        throw new InvalidArgumentException("Invalid transition from {$from} to {$to}.");
    }
}
```

### Key-based settings cache (FIX-18)
```php
// Source: pattern derived from Setting.php + conventions in 01-CONTEXT.md
public static function get(string $key, float|int|string|array|bool|null $default = null): float|int|string|array|bool|null
{
    return Cache::rememberForever("settings.{$key}", fn () => self::query()->where('key', $key)->value('value') ?? $default);
}

public static function set(string $key, mixed $value): void
{
    $setting = self::query()->where('key', $key)->first();
    if ($setting === null) {
        throw new InvalidArgumentException("Setting {$key} not found.");
    }
    $setting->update(['value' => $value]);
    Cache::forget("settings.{$key}");
    Cache::forget("settings.group.{$setting->group}");
}
```

### Safe `orderBy` via `SORT_COLUMN_MAP` (FIX-13)
```php
// Source: pattern from StockService::SORT_COLUMN_MAP (verified via codebase read)
private const SORT_COLUMN_MAP = [
    'name' => 'name',
    'created_at' => 'created_at',
    'status' => 'status',
];

$sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'created_at';
$orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc'], true) ? strtolower($orderDirection) : 'asc';
$query->orderBy($sortColumn, $orderDirection);
```

### Frontend tax mirroring backend (FIX-07)
```typescript
// Source: pattern derived from 01-CONTEXT.md + SalesOrders Create/Edit pages
const { getSetting } = useAuth();
const taxRate = computed(() => Number(getSetting("sales", "tax_rate", 0)) / 100);
const taxAmount = computed(() => round((subTotal.value - discountAmount.value) * taxRate.value, 2));
const total = computed(() => subTotal.value - discountAmount.value + taxAmount.value);
// Payment-difference validation compares paymentTotal against total (taxed).
```

### `whenLoaded` in resource (FIX-03)
```php
// Source: pattern used across other resources (verified via codebase read)
'items' => $this->whenLoaded('items', fn () => SalesOrderItemResource::collection($this->items)->resolve(), []),
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Broad `catch (Exception)` in web controllers | `catch (InvalidArgumentException)` only | Phase 1 (FIX-14) | Non-business exceptions no longer leak raw messages to users. |
| `Cache::tags(['settings'])` | Key-based `Cache::rememberForever()` + explicit forget | Phase 1 (FIX-18) | Settings cache works on default `file` driver and test `array` driver. |
| Duplicate FIFO in `BatchService` | Single source of truth in `FifoStockDeductionService` | Phase 1 (FIX-11) | Consistent exception type, close-at-zero, and counter semantics. |
| Raw `orderBy($userInput)` | `SORT_COLUMN_MAP` whitelist | Phase 1 (FIX-13) | Prevents column-name injection/side-channel sorting. |
| Hard-coded `taxRate = 0` in Vue | Read from settings with `/100` | Phase 1 (FIX-07) | Frontend live preview and payment validation match backend. |
| Inline shift-status checks | `TRANSITION_MAP` + `validateTransition()` | Phase 1 (FIX-12) | Centralized, testable state machine. |

**Deprecated/outdated:**
- `BatchService::deductFIFO()` — no callers; delete in FIX-11.
- `Api/PurchaseOrdersController` — will be deleted in Phase 2; minimum viable fix only in Phase 1.
- `browser.log` unbounded single file — must be rotated/bounded in FIX-22.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `composer lint` (Pint + PHPStan) currently passes with 0 errors; the 616-error baseline in `CONCERNS.md` is stale. | Summary / Validation Architecture | If PHPStan errors reappear on a different config, the phase will need to budget time for them. Verified with `vendor/bin/phpstan analyse` on `phpstan.neon.dist`. |
| A2 | The 17 TypeScript errors (8 PrimeVue internal + 9 app) are intentionally out of scope for Phase 1 and handled by CONV-03 in Phase 3. | Common Pitfalls | If a frontend fix in Phase 1 accidentally creates new errors, it may be missed against the noisy baseline. Verify touched files individually with `npm run type-check`. |
| A3 | `browser.log` is produced by Laravel Boost's `browser` Monolog channel and contains Vue warnings + Inertia dumps. | FIX-22 | If the source is something else (e.g., custom browser instrumentation), the fix may need adjustment. |
| A4 | `ProductVariant` field for SKU is `identifier`; no `sku` accessor exists. | FIX-05 | If the project later adds a `sku` column, the resource fix may need revisiting. |
| A5 | `Setting::get()` returns group-organized settings; `useAuth().getSetting('sales', 'tax_rate', 0)` is the correct frontend access pattern. | FIX-07 | If the setting group/key naming differs, the frontend will read a default of 0. Verify `settings` table keys during implementation. |

**If this table is empty:** Not applicable — assumptions are listed above.

## Open Questions

1. **Exact `browser.log` writer binding**
   - What we know: The file is produced by `vendor/laravel/boost/src/BoostServiceProvider.php` registering a `browser` Monolog channel with a `single` file handler.
   - What's unclear: Whether the project can configure the channel in `config/logging.php` to use `daily`, or whether Boost overrides the channel definition at runtime.
   - Recommendation: Inspect the service provider source during implementation and add a `browser` daily channel entry; if Boost ignores config, add an OS-level logrotate rule.

2. **`CashRegisterShiftService` transition semantics**
   - What we know: `01-CONTEXT.md` proposes `['open' => ['closed', 'forced_close'], 'closed' => [], 'forced_close' => []]`.
   - What's unclear: Whether `open()` should be allowed from `closed` or `forced_close` (re-opening a shift). The current inline checks at lines 102/136/170 only guard from `open`, so the proposed map matches.
   - Recommendation: Implement the proposed map and add a Pest test for valid/invalid transitions; adjust if a test reveals unexpected behavior.

3. **`recalculateStock()` signature change scope**
   - What we know: `01-CONTEXT.md` allows changing the signature to accept a `ProductVariant` instance or an ID-with-find-and-null-check.
   - What's unclear: Whether all call sites have the variant already loaded.
   - Recommendation: Prefer passing the loaded instance where available; for `FifoStockDeductionService`, use `find()` with an `InvalidArgumentException` null check because only IDs are available there.

4. **PurchaseOrders API Form Request depth**
   - What we know: The controller is deleted in Phase 2.
   - What's unclear: How much validation parity is required for a controller that will be removed.
   - Recommendation: Create minimal API Form Requests that mirror the web `StorePurchaseOrderRequest`/`UpdatePurchaseOrderRequest` rules; do not add cross-field validation beyond what is needed to satisfy FIX-01.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | Laravel backend | ✓ | 8.3.10 | — |
| Composer | Package management | ✓ | 2.4.1 | — |
| Node.js / npm | Frontend build / type-check | ✓ | 22.23.0 / 10.9.8 | — |
| MySQL | Dev database | ✓ | 8.0.46 | SQLite for tests (configured in `phpunit.xml`) |
| Redis | Dev cache/session (current `.env`) | ✓ | (ping PONG) | `file`/`array` driver (FIX-18 must make `file` work) |
| Vite | Frontend dev server / build | ✓ | via npm | — |
| Pest | Testing | ✓ | ^3.8 | — |
| PHPStan | Static analysis | ✓ | level 8 (Larastan) | — |
| Laravel Boost | Browser logging | ✓ | installed | Configure/bound in FIX-22 |

**Missing dependencies with no fallback:** None.

**Missing dependencies with fallback:** None.

## Validation Architecture

`.planning/config.json` has `workflow.nyquist_validation: true`, so this section is required.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 3 with Laravel plugin |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

Phase 1's requirements are mostly bug fixes; the existing test suite is thin for the affected modules. No new test files are strictly required by the phase description, but ROADMAP success criteria call for targeted Pest coverage of the financial core.

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| FIX-07 | Frontend tax preview matches backend formula | integration / manual | manual UI check with `tax_rate=13` | ❌ Wave 0 — none |
| FIX-11 | FIFO auto-closes batch at zero and throws `InvalidArgumentException` on insufficient stock (sale + transfer) | feature/unit | `php artisan test --compact --filter=FifoStockDeduction` | ❌ Wave 0 — none |
| FIX-12 | `CashRegisterShiftService` valid/invalid transitions throw `InvalidArgumentException` | unit | `php artisan test --compact --filter=CashRegisterShiftService` | ❌ Wave 0 — none |
| FIX-14 | Web controllers surface business-rule messages but do not leak raw SQL/stack errors | feature | `php artisan test --compact --filter=BrandTest` (if delete guard exists) | ⚠️ partial |
| FIX-18 | `Setting::get()` works on `CACHE_DRIVER=file` and invalidates on write | feature/unit | `CACHE_DRIVER=file php artisan test --compact --filter=Settings` | ❌ Wave 0 — none |
| ROADMAP #1 | Sales order with tax_rate=13 saves correct tax_amount | feature/unit | `php artisan test --compact --filter=SalesOrder` | ❌ Wave 0 — none |
| ROADMAP #3 | Sale-to-zero and transfer-to-zero both close batch and throw `InvalidArgumentException` on insufficient stock | feature/unit | `php artisan test --compact --filter=StockTransfer` | ❌ Wave 0 — partial (`StockTransferTest.php` exists) |
| ROADMAP #5 | `CashRegisterShiftResource` returns movements, `StockTransferResource` serializes dates, `useApi()` search returns 200 | feature / manual | manual smoke + existing API tests | ⚠️ partial |

### Sampling Rate
- **Per task commit:** `php artisan test --compact` and `vendor/bin/phpstan analyse` on changed files.
- **Per wave merge:** Full `php artisan test --compact` + `composer lint` + `npm run type-check` + `npm run lint`.
- **Phase gate:** Full suite green before `/gsd-verify-work`.

### Wave 0 Gaps
- [ ] Pest tests for `FifoStockDeductionService` (FIX-11 / ROADMAP #3).
- [ ] Pest tests for `CashRegisterShiftService` transitions (FIX-12 / ROADMAP #4).
- [ ] Pest tests for `Setting` cache on `file`/`array` driver (FIX-18 / ROADMAP #2).
- [ ] Pest tests for sales-order tax computation end-to-end (ROADMAP #1).
- [ ] Existing TypeScript errors are deferred to Phase 3, but touched Vue files should be individually checked with `npm run type-check`.

*(Note: The phase requirements do not mandate writing tests, but the ROADMAP success criteria do. The planner should include at least targeted Pest tests for the five success-criterion areas.)*

## Security Domain

`workflow.security_enforcement` is enabled with `asvs_level: 1` and `block_on: high`.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V1 Architecture | yes | Keep business logic in services; controller actions delegate; no new attack surface introduced. |
| V2 Authentication | no | Phase 1 does not touch authentication flows. |
| V3 Session Management | no | Phase 1 does not touch session management. |
| V4 Access Control | yes | FIX-02 adds `authorize()` to 5 API controllers; FIX-01 API Form Requests include `authorize()` gates via `PermissionsEnum`. |
| V5 Input Validation | yes | FIX-01 replaces `$request->all()` with `$request->validated()`; FIX-13 whitelists `orderBy`/`orderDirection`; FIX-12 validates state transitions; FIX-14 narrows exception handling. |
| V6 Cryptography | no | No crypto changes. |
| V7 Error Handling | yes | FIX-14 stops exposing raw exception messages for non-business errors. |
| V8 Data Protection | no | No PII handling changes. |
| V12 File / Resources | no | No file upload changes. |
| V13 API | yes | FIX-01/02/09/10 harden the surviving API surface; mass-assignment and authorization gaps are closed. |

### Known Threat Patterns for the Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Mass assignment (FIX-01) | Tampering | API Form Requests + `$request->validated()` + narrow `$fillable`. |
| Missing authorization (FIX-02) | Elevation of privilege | `$this->authorize(PermissionsEnum::X->value, auth()->user())` + policies. |
| SQL `orderBy` injection/side-channel (FIX-13) | Tampering / Information disclosure | Per-service `SORT_COLUMN_MAP` whitelist + direction validation. |
| Broad exception catch leaking internals (FIX-14) | Information disclosure | Catch `InvalidArgumentException` only; propagate other exceptions to the global handler. |
| Cache tag misuse causing runtime crash (FIX-18) | Denial of service | Use driver-agnostic `Cache::rememberForever()` + explicit key invalidation. |
| Unbounded log files (FIX-22) | Denial of service / Resource exhaustion | Daily log rotation + bounding of `browser.log`. |
| Resource serialization of internal state (FIX-03/04/05/09) | Information disclosure | Use `whenLoaded()` and explicit Resource fields; return Resources instead of raw models. |

## Sources

### Primary (HIGH confidence)
- `01-CONTEXT.md` — Locked decisions for FIX-11 and all discretion-area fix approaches.
- `.planning/REQUIREMENTS.md` — Canonical FIX-01..FIX-22 requirement text.
- `.planning/codebase/CONCERNS.md` — Detailed bug descriptions, file references, and fix approaches.
- `.claude/rules/laravel-backend.md` — Service conventions (`final`, `TRANSITION_MAP`, `InvalidArgumentException`, `DB::transaction`, `SORT_COLUMN_MAP`, `list()` signature, Resources).
- `.claude/rules/routes-and-api.md` — API response format and `authorize()` pattern.
- `.claude/rules/authorization.md` — `PermissionsEnum` enforcement points.
- `.claude/rules/vue-frontend.md` — `useAuth().getSetting()`, form patterns, Inertia usage.
- `.claude/rules/testing.md` — Pest conventions and `getJson()` guidance.
- Source files read by the explore agent: `FifoStockDeductionService.php`, `BatchService.php`, `StockTransferService.php`, `SalesOrderService.php`, `CashRegisterShiftService.php`, `Setting.php`, `CashRegisterShiftResource.php`, `StockTransferResource.php`, `ApiCollection.php`, `UserCollection.php`, `useApi.ts`, `SalesOrders/Create/Index.vue`, `SalesOrders/Edit/Index.vue`, `AuthServiceProvider.php`, etc.

### Secondary (MEDIUM confidence)
- `vendor/bin/phpstan analyse` output showing 0 errors (run on 2026-06-21).
- `npm run type-check` output showing 17 errors (run on 2026-06-21).
- `storage/logs/browser.log` size and content inspection (verified via `wc -l`/`head`).

### Tertiary (LOW confidence)
- None — all claims in this research are either derived from project documents/source or verified by tool execution.

## Metadata

**Confidence breakdown:**
- Standard stack: **HIGH** — all packages are project baseline and verified via `composer.json`/`package.json`/`composer.lock`.
- Architecture: **HIGH** — patterns are explicitly documented in `.claude/rules/*.md` and already implemented in sibling files (`StockService`, `UserCollection`, `PurchaseOrderService`).
- Pitfalls: **HIGH** — derived from direct source inspection and one experimental verification of `Cache::tags()` failure on the `file` driver.

**Research date:** 2026-06-21
**Valid until:** 2026-07-21 (stable Laravel 12 / PrimeVue 4 stack; re-verify if any dependency minor version is bumped before implementation).
