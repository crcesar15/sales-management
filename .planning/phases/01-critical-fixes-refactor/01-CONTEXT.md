# Phase 1: Critical Fixes & Refactor - Context

**Gathered:** 2026-06-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Repair the existing financial core and critical defects across backend services, resources, controllers, and the sales-order frontend so every later feature is built on trustworthy services. This is a refactor/bug-fix phase scoped to **FIX-01..FIX-22** — no new user-visible capabilities, no new modules, no API-layer removal (that's Phase 2), no convention-alignment work like `casts()` migration or `LogsActivity` rollout (that's Phase 3).

In scope:
- Security fixes: mass-assignment in `Api/VendorsController` + `Api/PurchaseOrdersController` (FIX-01), missing `authorize()` in 5 API controllers (FIX-02)
- Broken serialization: `CashRegisterShiftResource` (FIX-03), `StockTransferResource` dates + `ProductVariant::sku` (FIX-04, FIX-05)
- Standardization: status enum-vs-string comparisons (FIX-06), tax frontend/backend drift (FIX-07), CSRF header (FIX-08), `PurchaseOrdersController` raw-model return (FIX-09), `ApiCollection` pagination meta (FIX-10)
- Financial core: FIFO consolidation (FIX-11), `CashRegisterShiftService` TRANSITION_MAP (FIX-12), `SORT_COLUMN_MAP` on 11 services (FIX-13), narrow `catch` to `InvalidArgumentException` in 12 web controllers (FIX-14)
- Dead code/imports: `AuthServiceProvider` (FIX-15), `ReceptionOrderController` claimed-quantities extraction (FIX-16), dead `$request->validated()` calls (FIX-17)
- Infrastructure: `Setting` cache `Cache::tags()` removal (FIX-18), N+1 in `ReceptionOrderResource` + `SalesOrderResource` (FIX-19, FIX-20), `recalculateStock()` re-query (FIX-21), log rotation + `browser.log` investigation (FIX-22)

Out of scope (handled in other phases):
- API layer deletion (API-01..08 → Phase 2) — Phase 1 *fixes* the API controllers that survive Phase 2 and *also* fixes those that get deleted, because the requirements list both
- `casts()` migration + `LogsActivity` rollout (CONV-01..02 → Phase 3)
- TypeScript errors, Pest property wiring, PHPStan baseline (CONV-03..07 → Phase 3)
- Any POS/Dashboard/Reports feature work (Phases 4–6)

</domain>

<decisions>
## Implementation Decisions

### FIFO Consolidation (FIX-11)
- **D-01 — Single source of truth:** `FifoStockDeductionService` becomes the canonical FIFO implementation. `BatchService::deductFIFO()` (no callers — dead code) is **deleted**. `BatchService::deductFIFOForTransfer()` becomes a thin delegate that forwards to `FifoStockDeductionService::deductForTransfer()`, preserving `BatchService`'s public method so `StockTransferService` does not need to change its dependency wiring.
- **D-02 — Two public methods on `FifoStockDeductionService`:**
  - `deductForOrder(SalesOrder $order): void` — existing method, for sales. Iterates `$order->items`, increments `sold_quantity`, auto-closes batches at zero (NEW — fixes current gap). Contract: **caller owns the transaction** (`SalesOrderService::create/update` wrap it in `DB::transaction()`) — no internal transaction. PHPDoc must state this.
  - `deductForTransfer(int $variantId, int $storeId, int $quantity): void` — NEW method, for stock transfers. Increments `transferred_quantity`, auto-closes batches at zero. Contract: **opens its own `DB::transaction()`** internally (matches the old `BatchService::deductFIFOForTransfer()` behavior so `StockTransferService`'s call site does not need a new wrapping transaction). PHPDoc must state this.
- **D-03 — Transaction ownership is intentionally mixed:** `deductForOrder()` = caller-owned (no internal txn), `deductForTransfer()` = self-contained (internal txn). Both contracts documented in PHPDoc. Rationale: least churn — preserves existing caller contracts on both sides. Do not "align" them.
- **D-04 — Auto-close at zero in BOTH methods:** When `remaining_quantity` hits 0 after a deduction, the batch `status` is set to `'closed'` within the same iteration (same pattern as the old `BatchService` — `$batch->refresh(); if ($batch->remaining_quantity === 0) $batch->update(['status' => 'closed'])`). This closes the gap in `deductForOrder()` and satisfies ROADMAP success criterion #3 for *both* the sale path and the transfer path in one consolidation.
- **D-05 — Standardize exception type:** Both methods throw `InvalidArgumentException` on insufficient stock (not `RuntimeException`). Exception message format mirrors the current `deductForOrder()` message: `"Insufficient stock for variant {sku}: requested {requested}, available {available}."` where `{sku}` resolves via `ProductVariant::find($variantId)?->identifier ?? "ID {$variantId}"`. This makes ROADMAP success criterion #3's "both throw `InvalidArgumentException`" true.
- **D-06 — `BatchService::deductFIFOForTransfer()` delegation:** Keep the public method signature `(int $variantId, int $storeId, int $quantity): void` so `StockTransferService`'s constructor injection and call site (`$this->batchService->deductFIFOForTransfer(...)`) stay intact. Body becomes a one-line delegate to `$this->fifoStockDeductionService->deductForTransfer(...)`. `BatchService` constructor gains a `FifoStockDeductionService` dependency. Delete `BatchService::deductFIFO()` entirely (no callers) and its now-unused `getAvailableBatches()` private helper if it has no remaining callers.
- **D-07 — `recalculateStock()` integration:** The consolidated service calls `recalculateStock()` on the affected variant(s) at the end of each deduction, mirroring the current behavior of both methods. The variant-resolution style (re-query vs. passed-in model) is decided under FIX-21, not here — but whatever FIX-21 decides applies uniformly to both `deductForOrder()` and `deductForTransfer()`.

### the agent's Discretion
The following FIX-01..FIX-22 items were **not** selected for discussion — the user has no specific preference on HOW to implement them, so the researcher and planner should follow the fix approach documented in `.planning/codebase/CONCERNS.md` and `.claude/rules/*.md` directly:
- **FIX-01 (mass-assignment):** Create API Form Requests mirroring web ones; replace `$request->all()` with `$request->validated()`. Note: `Api/PurchaseOrdersController` is deleted in Phase 2 — the requirements still list FIX-01 for it, so fix it in Phase 1 anyway (the requirements are the contract).
- **FIX-02 (missing authorize):** Add `$this->authorize(PermissionsEnum::X->value, auth()->user())` per API controller action. Same Phase-2 deletion note applies to PurchaseOrders/Permissions/Settings.
- **FIX-03, FIX-04, FIX-05 (broken resources):** Use `$this->resource->relationLoaded(...)` / `$this->whenLoaded(...)`; fix date casts to plain `'datetime'` or wrap with Carbon; use `ProductVariant::identifier` (the actual field) — do not add a fake `sku` accessor.
- **FIX-06 (status comparison):** Standardize on enum-to-enum comparisons on the model side (`$model->status === SalesOrderStatus::DRAFT`); keep `->value` only for DB writes and raw-input comparison.
- **FIX-07 (tax frontend):** Frontend reads `useAuth().getSetting('sales', 'tax_rate', 0)` and applies the same `/100` formula as `SalesOrderService`. Payment-difference validation compares against the **taxed** total.
- **FIX-08 (CSRF header):** Delete the broken `X-XSRF-TOKEN` headers block in `useApi.ts`; rely on `withXSRFToken: true` + `withCredentials: true`.
- **FIX-09 (raw model return):** Create `PurchaseOrderResource` + Collection; return via `(new PurchaseOrderResource($order))->response()->setStatusCode(201)`. Same Phase-2 deletion note.
- **FIX-10 (ApiCollection meta):** Add `paginationInformation()` override or manual `meta` block matching `UserCollection`.
- **FIX-12 (TRANSITION_MAP):** Add `TRANSITION_MAP` constant + `validateTransition()` to `CashRegisterShiftService` mirroring `PurchaseOrderService`/`SalesOrderService`. Map: `['open' => ['closed', 'forced_close'], 'closed' => [], 'forced_close' => []]` (researcher/planner should confirm the exact transitions against the existing inline checks at `CashRegisterShiftService.php:67,102,136,170`).
- **FIX-13 (SORT_COLUMN_MAP):** Add a `SORT_COLUMN_MAP` constant (per-service, not a shared trait) to the 11 services; unknown columns default to a safe column (`created_at`); validate `orderDirection` to `asc`/`desc` only. Mirror the existing `StockService::SORT_COLUMN_MAP` pattern.
- **FIX-14 (narrow catch):** Change `catch (Exception $e)` to `catch (InvalidArgumentException $e)` in the 12 web controllers; let other exceptions propagate to the global handler.
- **FIX-15 (AuthServiceProvider):** Remove the dead `StockAdjustment::class => StockAdjustmentPolicy::class` mapping (auto-discovery handles it) OR add the missing imports. Removing is consistent with every other policy in the app.
- **FIX-16 (claimed-quantities):** Extract the `bcadd` aggregation into a single `ReceptionOrderService` method; call it from both `ReceptionOrderController` methods.
- **FIX-17 (dead validated calls):** Remove the dead `$request->validated();` statements in `Api/RoleController` + `Api/MeasurementUnitController`, or capture the return and use it.
- **FIX-18 (Setting cache):** Replace `Cache::tags(['settings'])->*` with key-based `Cache::rememberForever("settings.{$key}", ...)` + `Cache::forget("settings.{$key}")` / `Cache::forget("settings.group.{$group}")` on writes. Works on the default `file` driver.
- **FIX-19, FIX-20 (N+1):** Add the missing eager loads to `ReceptionOrderService::list()`/`show()` (`lineItems.productVariant.product.measurementUnit`, `lineItems.catalogEntry.unit`) and `SalesOrderService::list()` (`items`, `items.productVariant.product`).
- **FIX-21 (recalculateStock re-query):** Pass the already-loaded `ProductVariant` into `recalculateStock()` instead of re-querying via `firstOrFail()`. Signature change is acceptable; researcher/planner decides whether `recalculateStock()` accepts a `ProductVariant` instance or an ID-with-`find()`-and-null-check, but it must NOT throw `ModelNotFoundException` for a missing variant — use `InvalidArgumentException` for the business-rule violation.
- **FIX-22 (log rotation):** Configure `config/logging.php` daily driver; investigate the 310MB `browser.log` (likely an unbounded `console.log` capture or Playwright/Dusk run) and bound it.

### Folded Todos
No todos were folded into scope — `todo.match-phase` returned zero matches for Phase 1.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project planning
- `.planning/ROADMAP.md` §Phase 1 — Goal, dependencies, requirements list (FIX-01..22), and the 5 success criteria (esp. criterion #3 on FIFO + close-at-zero + InvalidArgumentException)
- `.planning/REQUIREMENTS.md` §Critical Fixes — Canonical FIX-01..FIX-22 requirement text (each line is the acceptance contract)
- `.planning/PROJECT.md` §Constraints + §Key Decisions — Tech-stack locks, no-new-migrations rule, `InvalidArgumentException` convention, "remove unused API layer" decision (Phase 2 deletes; Phase 1 fixes-then-deletes per requirements)
- `.planning/STATE.md` §Accumulated Context — Fixes-before-features ordering, API-removal-by-deletion decision

### Codebase maps (the source of every FIX-xx)
- `.planning/codebase/CONCERNS.md` — Full audit: every issue has a Files section, Trigger, Workaround, and Fix approach. This is the primary reference for every FIX item not explicitly decided above.
- `.planning/codebase/ARCHITECTURE.md` §Service Layer + §Key Abstractions (TRANSITION_MAP, Module Pattern) — The patterns the fixes must conform to
- `.planning/codebase/STACK.md` §Code Quality — Pint/PHPStan/Rector configs that the fixed code must satisfy (modulo the Phase-3 baseline)

### Convention rules (the target state)
- `.claude/rules/laravel-backend.md` — Services (`final`, `TRANSITION_MAP`, `InvalidArgumentException`, `DB::transaction`, `lockForUpdate`, `SORT_COLUMN_MAP`, `list()` signature), Resources (`whenLoaded`, manual `meta`, `->value`, `(float)`), Models (`casts()` method, `LogsActivity`), Form Requests, Policies
- `.claude/rules/routes-and-api.md` §API Response Format — `JsonResource` + `ResourceCollection` with `meta` shape that FIX-09/FIX-10 must produce
- `.claude/rules/authorization.md` §Enforcement Points — API controller `$this->authorize(PermissionsEnum::X->value, auth()->user())` pattern for FIX-02
- `.claude/rules/testing.md` §Known Issues — `getJson()` for forbidden assertions (relevant if Phase 1 touches tests; main test fixes are Phase 3)
- `.claude/rules/commands.md` §Code Quality — `composer lint` / `npm run lint` / `npm run type-check` must pass after changes (run after all PHP changes complete, not mid-implementation)

### Source files named in CONCERNS.md (researcher/planner should read these to ground each fix)
- `app/Services/FifoStockDeductionService.php` — Canonical FIFO (FIX-11 target — gets `deductForTransfer` added, `deductForOrder` gap fixed)
- `app/Services/BatchService.php:100-163` — Duplicate FIFO to delete/delegate (FIX-11)
- `app/Services/StockTransferService.php:136` — Call site for `deductFIFOForTransfer` (must keep working after delegation)
- `app/Services/SalesOrderService.php:29,132,221` — Caller of `deductForOrder` (transaction wrapper — must not change)
- `app/Services/CashRegisterShiftService.php:67,102,136,170` — Inline status checks to replace with `TRANSITION_MAP` (FIX-12)
- `app/Models/Setting.php:41,59,69` + `app/Services/SettingsService.php:29` — `Cache::tags()` calls to remove (FIX-18)
- `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php:36-37` — `relationLoaded()` bug (FIX-03)
- `app/Http/Resources/StockTransfer/StockTransferResource.php:26-28,56` — date + `sku` bugs (FIX-04, FIX-05)
- `app/Http/Resources/ApiCollection.php` — missing pagination `meta` (FIX-10)
- `app/Http/Controllers/Api/VendorsController.php:66,77` + `app/Http/Controllers/Api/PurchaseOrdersController.php:57,64` — mass-assignment (FIX-01)
- `app/Http/Controllers/Api/{ActivityLog,Batches,Permissions,PurchaseOrders,Settings}Controller.php` — missing `authorize()` (FIX-02)
- `app/Http/Controllers/Api/PurchaseOrdersController.php:52,59,66` — raw model return (FIX-09)
- `app/Providers/AuthServiceProvider.php:34` — dead policy mapping (FIX-15)
- `app/Http/Controllers/ReceptionOrderController.php:86,172` + `app/Services/ReceptionOrderService.php:323` — claimed-quantities duplication (FIX-16)
- `app/Http/Controllers/Api/RoleController.php:21,54,77` + `app/Http/Controllers/Api/MeasurementUnitController.php:22` — dead `validated()` calls (FIX-17)
- `app/Services/{Role,Category,Variant,Vendor,Store,User,MeasurementUnit,Brand,Customer,Product,Catalog}Service.php` — `orderBy` injection (FIX-13)
- `app/Http/Controllers/{Vendors,Catalog,MeasurementUnit,Customer,ProductVariant,Product,ProductOption,OptionValue,Brand,Category}Controller.php` — broad `catch (Exception)` (FIX-14)
- `resources/js/Composables/useApi.ts:8-14` — CSRF header bug (FIX-08)
- `resources/js/Pages/SalesOrders/Create/Index.vue:53-55,68` + `resources/js/Pages/SalesOrders/Edit/Index.vue:83-85` — tax frontend drift (FIX-07)
- `app/Services/ReceptionOrderService.php:27` + `app/Http/Resources/ReceptionOrder/ReceptionOrderResource.php:79-110` — N+1 (FIX-19)
- `app/Services/SalesOrderService.php:39` + `app/Http/Resources/SalesOrder/SalesOrderResource.php:54` — N+1 (FIX-20)
- `app/Services/{StockAdjustment,Batch,StockTransfer,ReceptionOrder}Service.php` + `app/Services/SalesOrderService.php` — `recalculateStock()` re-query (FIX-21)
- `config/logging.php` + `storage/logs/browser.log` — log rotation (FIX-22)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **`FifoStockDeductionService`** (`app/Services/FifoStockDeductionService.php`) — Canonical FIFO. Becomes the single source of truth under D-01. Already throws `InvalidArgumentException`, uses `lockForUpdate()`, resolves variant via `ProductVariant::find()` (nullable) with a graceful `identifier` fallback in the exception message — reuse this message style for `deductForTransfer()`.
- **`StockService::SORT_COLUMN_MAP`** — The existing safe-pattern constant for FIX-13. Mirror it (per-service constant, not a shared trait) in the 11 services that lack it.
- **`CatalogService.php:164`** — An alternative `match()` expression pattern for orderBy whitelisting. Either `SORT_COLUMN_MAP` const or `match()` is acceptable; prefer the const for consistency with `StockService`.
- **`UserCollection`** (`app/Http/Resources/User/UserCollection.php:35`) — Canonical `meta` block shape for FIX-10. `ApiCollection` should mirror it: `{data, meta:{current_page,last_page,per_page,total}}`.
- **`PurchaseOrderService` / `SalesOrderService` / `StockTransferService`** — All define `TRANSITION_MAP` + `validateTransition()` (the pattern FIX-12 must mirror in `CashRegisterShiftService`). Read one of these before implementing FIX-12.
- **`Batch` model `scopeAvailable`** — Used by `BatchService::getAvailableBatches()`; the consolidated `deductForTransfer()` should use the same scope (or inline the same `where` clauses `deductForOrder()` uses) — keep the query shape identical so lock semantics don't drift.

### Established Patterns
- **Module pattern (Controller ×2, Service, Form Request ×2, Resource, Vue Page, Composable, Types)** — every fix touches files in this layer set; do not invent new layers.
- **`final` classes, constructor property promotion, `DB::transaction()` for critical ops, `lockForUpdate()` for stock-sensitive ops** — the consolidated FIFO service and every touched service must keep these.
- **Status enums are backed string enums cast on the model** — comparisons on the model side are enum-to-enum (FIX-06); `->value` only for DB writes / raw input.
- **`InvalidArgumentException` for business rule violations** — not `RuntimeException`, not custom exception classes. The current `BatchService::deductFIFO*` throwing `RuntimeException` is the bug FIX-11 fixes.
- **Resources use `whenLoaded()` for conditional relations, override `paginationInformation()` for `meta`** — FIX-03/04/09/10 must follow this.
- **`Setting::get()` wraps `Cache::rememberForever()`** — FIX-18 keeps the `rememberForever` wrapping, only drops the `tags()` layer.

### Integration Points
- **`SalesOrderService::create()` / `update()`** wrap `deductForOrder()` in `DB::transaction()` (lines 132, 221) — must continue to work unchanged after `deductForOrder()` gains auto-close-at-zero (D-04). The caller already owns the txn; auto-close happens inside it.
- **`StockTransferService:136`** calls `$this->batchService->deductFIFOForTransfer($variantId, $storeId, $quantity)` — the delegate (D-06) keeps this call site working. `BatchService` constructor gains `FifoStockDeductionService` as a new dependency (watch for circular DI: `FifoStockDeductionService` does not depend on `BatchService`, so the edge is one-way — safe).
- **`HandleInertiaRequests::share()`** caches settings via `Setting::get()` → FIX-18's key-based invalidation must keep `Setting::get()` semantics identical (`get($key, $default)` returns the cached value or default). Only the cache *backend* mechanics change.
- **`SalesOrderService` tax calc** (`app/Services/SalesOrderService.php:86,161`) — `($subTotal - $discount) * ($taxRate / 100)`. FIX-07's frontend must match this exact formula; extract a shared TS helper if the frontend has more than one tax-calculation site (Create + Edit).
- **`AuthServiceProvider`** — removing the dead mapping (FIX-15) is safe because Laravel auto-discovers policies (per `authorization.md`); no registration in `AuthServiceProvider` needed.

</code_context>

<specifics>
## Specific Ideas

- **FIFO message format:** Reuse the existing `deductForOrder()` exception message style — `"Insufficient stock for variant {sku}: requested {requested}, available {available}."` — for `deductForTransfer()` too. The `{sku}` should resolve via `ProductVariant::find($variantId)?->identifier ?? "ID {$variantId}"` (graceful null fallback, no `firstOrFail()`).
- **`BatchService::deductFIFOForTransfer()` stays as a thin delegate** — explicitly preserve the public method signature so `StockTransferService`'s constructor injection and call site do not change. The delegate body is a one-liner forwarding to `FifoStockDeductionService::deductForTransfer()`.
- **Auto-close pattern in the consolidated service:** Mirror the existing `BatchService` close logic — `$batch->refresh(); if ($batch->remaining_quantity === 0) { $batch->update(['status' => 'closed']); }` — inside the deduction loop, applied to both methods.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope. The user selected only the FIFO area for discussion; all other FIX-01..FIX-22 items are left to the researcher/planner's discretion guided by CONCERNS.md and `.claude/rules/*.md` (captured in the "the agent's Discretion" section above).

### Reviewed Todos (not folded)
None — `todo.match-phase` returned zero matches for Phase 1.

</deferred>

---

*Phase: 1-Critical Fixes & Refactor*
*Context gathered: 2026-06-21*