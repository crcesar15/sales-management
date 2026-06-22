---
phase: 01-critical-fixes-refactor
plan: 04
subsystem: api
tags: [laravel, inertia, api, authorization, mass-assignment, form-requests, resources, pagination, serialization]

# Dependency graph
requires:
  - phase: "01-critical-fixes-refactor"
    plan: "03"
    provides: "Driver-agnostic settings cache invalidation in Api\\SettingsController (assumed correct before this plan)"
provides:
  - "API Form Requests for vendors (Store/Update) and purchase orders (Store/Update) with authorization + array-format validation"
  - "All five target API controllers (ActivityLog, Batches, Permissions, PurchaseOrders, Settings) call \$this->authorize(PermissionsEnum::X->value, auth()->user())"
  - "Api\\VendorsController and Api\\PurchaseOrdersController use \$request->validated() (no \$request->all())"
  - "Api\\PurchaseOrdersController show/store/update return PurchaseOrderResource with 200/201 status codes"
  - "CashRegisterShiftResource serializes movements via whenLoaded() (no longer dropped when not eager loaded)"
  - "StockTransferResource serializes all four date fields (cancelled_at, completed_at, created_at, updated_at) Carbon-safely and exposes productVariant identifier"
  - "ApiCollection returns {data, meta} pagination shape matching UserCollection"
affects:
  - "01-critical-fixes-refactor"
  - "Frontend API consumers relying on ApiCollection pagination meta"
  - "Phase 1 Wave 6 (Pest API authorization coverage)"

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "API Form Requests live in app/Http/Requests/Api/{Module}/ and mirror web Form Request validation + authorization via PermissionsEnum::X->value"
    - "API controllers authorize with \$this->authorize(PermissionsEnum::X->value, auth()->user()) (->value + explicit user)"
    - "API Form Requests for updates reference the API route param name (e.g. route('order') not route('purchaseOrder'))"
    - "Resource date serialization uses getAttribute('field')?->toISOString() for Carbon-cast datetime fields"
    - "Resource relationship inclusion uses whenLoaded('relation', fn () => ..., []) to avoid dropping data when not loaded"

key-files:
  created:
    - app/Http/Requests/Api/Vendors/StoreVendorRequest.php
    - app/Http/Requests/Api/Vendors/UpdateVendorRequest.php
    - app/Http/Requests/Api/PurchaseOrders/StorePurchaseOrderRequest.php
    - app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php
  modified:
    - app/Http/Controllers/Api/VendorsController.php
    - app/Http/Controllers/Api/PurchaseOrdersController.php
    - app/Http/Controllers/Api/ActivityLogController.php
    - app/Http/Controllers/Api/BatchesController.php
    - app/Http/Controllers/Api/PermissionsController.php
    - app/Http/Controllers/Api/SettingsController.php
    - app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php
    - app/Http/Resources/StockTransfer/StockTransferResource.php
    - app/Http/Resources/ApiCollection.php

key-decisions:
  - "API UpdatePurchaseOrderRequest uses route('order') (API route param {order}) instead of route('purchaseOrder') used by the web Form Request"
  - "PermissionsController::index authorizes via ROLES_VIEW since permissions are managed alongside roles and no dedicated permission-view enum case exists"
  - "StockTransferResource date fields are cancelled_at, completed_at, created_at, updated_at (the plan referenced shipped_at/received_at which do not exist as columns); updated_at was added as it was missing"
  - "Api\\PurchaseOrdersController::destroy authorizes via PURCHASE_ORDERS_EDIT (no dedicated delete permission enum case for purchase orders)"

patterns-established:
  - "API Form Requests: app/Http/Requests/Api/{Module}/ mirrors web validation, authorizes via PermissionsEnum::X->value, uses API route param names in withValidator cross-field checks"
  - "ApiCollection pagination meta: {data, meta: {current_page, last_page, per_page, total}} with paginationInformation() overridden to [] to avoid duplicate Laravel meta"
  - "whenLoaded('relation', fn () => SubResource::collection(\$this->relation)->resolve(), []) for optional relationships in JsonResources"

requirements-completed:
  - FIX-01
  - FIX-02
  - FIX-03
  - FIX-04
  - FIX-05
  - FIX-06
  - FIX-09
  - FIX-10

# Metrics
duration: 7 min
completed: 2026-06-22
status: complete
---

# Phase 01 Plan 04: API Controller & Resource Fixes Summary

**Fixed API mass-assignment (\$request->all() → \$request->validated() via 4 new API Form Requests), added \$this->authorize() to 5 API controllers, returned PurchaseOrderResource from the purchase-orders API, fixed CashRegisterShiftResource movements (whenLoaded) and StockTransferResource date/identifier serialization, and added pagination meta to ApiCollection.**

## Performance

- **Duration:** 7 min
- **Started:** 2026-06-22T01:45:45Z
- **Completed:** 2026-06-22T01:53:05Z
- **Tasks:** 4
- **Files modified:** 13 (4 created + 9 modified)

## Accomplishments

- Created 4 API Form Requests (`Api/Vendors/StoreVendorRequest`, `Api/Vendors/UpdateVendorRequest`, `Api/PurchaseOrders/StorePurchaseOrderRequest`, `Api/PurchaseOrders/UpdatePurchaseOrderRequest`) mirroring web validation with array-format rules, `authorize()` via `PermissionsEnum::X->value`, and the purchase-order `withValidator` catalog-active cross-field checks.
- `Api\VendorsController` store/update now type-hint the new Form Requests and use `$request->validated()` instead of `$request->all()`; removed the `@phpstan-ignore-next-line` TODO comments.
- `Api\PurchaseOrdersController` now authorizes every action (`PURCHASE_ORDERS_VIEW`/`CREATE`/`EDIT`), uses the new Form Requests with `$request->validated()`, and `show`/`store`/`update` return `PurchaseOrderResource` with 200/201 status codes (replacing raw `response()->json($order, ...)`).
- Added `$this->authorize(PermissionsEnum::X->value, auth()->user())` to the 5 API controllers that lacked it: `ActivityLogController` (`ACTIVITY_LOGS_VIEW`), `BatchesController` (`BATCHES_VIEW`), `PermissionsController` (`ROLES_VIEW`), `PurchaseOrdersController` (`PURCHASE_ORDERS_VIEW`/`CREATE`/`EDIT`), `SettingsController` (`SETTINGS_MANAGE`).
- `CashRegisterShiftResource` now uses `$this->whenLoaded('movements', fn () => CashRegisterMovementResource::collection($this->movements)->resolve(), [])` instead of `relationLoaded('movements')` so movements are no longer dropped when the relationship is not eager loaded.
- `StockTransferResource` serializes all four date fields (`cancelled_at`, `completed_at`, `created_at`, `updated_at`) Carbon-safely via `getAttribute()?->toISOString()` and exposes `productVariant?->identifier` (no `->sku` references).
- `ApiCollection` now returns `{data, meta: {current_page, last_page, per_page, total}}` matching `UserCollection`, with `paginationInformation()` overridden to `[]` to avoid duplicate Laravel meta.

## Task Commits

Each task was committed atomically:

1. **Task 1: Create API Form Requests for vendors and purchase orders** - `08e3bb0` (feat)
2. **Task 2: API controller authorization, validated input, and resource responses** - `9722849` (fix)
3. **Task 3: Resource serialization fixes** - `78a8f17` (fix)
4. **Task 4: ApiCollection pagination meta** - `698ff5b` (fix)

## Files Created/Modified

- `app/Http/Requests/Api/Vendors/StoreVendorRequest.php` - API vendor create validation + authorization (VENDORS_CREATE)
- `app/Http/Requests/Api/Vendors/UpdateVendorRequest.php` - API vendor update validation + authorization (VENDORS_EDIT), unique email ignore by route('vendor')
- `app/Http/Requests/Api/PurchaseOrders/StorePurchaseOrderRequest.php` - API purchase-order create validation + authorization (PURCHASE_ORDERS_CREATE) + withValidator catalog-active check
- `app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php` - API purchase-order update validation + authorization (PURCHASE_ORDERS_EDIT) + withValidator draft-only + catalog check, uses route('order')
- `app/Http/Controllers/Api/VendorsController.php` - store/update use StoreVendorRequest/UpdateVendorRequest + $request->validated(); authorize calls normalized to ->value
- `app/Http/Controllers/Api/PurchaseOrdersController.php` - authorize all actions, Form Requests + validated(), show/store/update return PurchaseOrderResource (200/201)
- `app/Http/Controllers/Api/ActivityLogController.php` - $this->authorize(ACTIVITY_LOGS_VIEW->value, auth()->user())
- `app/Http/Controllers/Api/BatchesController.php` - $this->authorize(BATCHES_VIEW->value, auth()->user())
- `app/Http/Controllers/Api/PermissionsController.php` - $this->authorize(ROLES_VIEW->value, auth()->user())
- `app/Http/Controllers/Api/SettingsController.php` - $this->authorize(SETTINGS_MANAGE->value, auth()->user()) on index + update
- `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php` - movements via whenLoaded() instead of relationLoaded()
- `app/Http/Resources/StockTransfer/StockTransferResource.php` - added updated_at serialization; getAttribute() for all 4 dates; added productVariant identifier field
- `app/Http/Resources/ApiCollection.php` - {data, meta} pagination shape + paginationInformation() override

## Decisions Made

- **API `UpdatePurchaseOrderRequest` uses `route('order')`:** The API route is `purchase-orders/{order}` (param name `order`), while the web route uses `{purchaseOrder}`. The API Form Request's `withValidator` cross-field check needs the route-bound model, so it references `$this->route('order')` instead of the web's `route('purchaseOrder')`.
- **`PermissionsController::index` authorizes via `ROLES_VIEW`:** There is no dedicated `PERMISSIONS_VIEW` enum case. Permissions are managed alongside roles (the `Api\RoleController` also uses `ROLES_VIEW`), so `ROLES_VIEW` is the correct authorization gate for listing permissions.
- **StockTransfer date fields are `cancelled_at`, `completed_at`, `created_at`, `updated_at`:** The plan's acceptance criterion referenced `shipped_at` and `received_at`, but those columns do not exist on the `stock_transfers` table (verified via `Schema::getColumnListing`). The actual date columns are the four serialized here. `updated_at` was missing from the original resource and was added.
- **`Api\PurchaseOrdersController::destroy` uses `PURCHASE_ORDERS_EDIT`:** There is no dedicated `PURCHASE_ORDERS_DELETE` enum case; `EDIT` is the closest available permission for destructive actions on purchase orders.
- **`Api\VendorsController` authorize calls normalized to `->value`:** The existing calls passed the enum case directly (`PermissionsEnum::VENDORS_VIEW`). Per `.claude/rules/authorization.md`, API controllers must use `PermissionsEnum::X->value, auth()->user()`. The store/update calls were normalized; the pre-existing index/show/catalog calls were left as-is (passing the enum directly also works via Gate resolution) to keep scope tight to the FIX-01/FIX-02 tasks.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] API `UpdatePurchaseOrderRequest` route param mismatch**
- **Found during:** Task 2 (wiring Form Request into PurchaseOrdersController)
- **Issue:** The web `UpdatePurchaseOrderRequest` references `$this->route('purchaseOrder')`, but the API route param is `{order}`. Copying the web request verbatim would have made the `withValidator` draft-status check fail (route('purchaseOrder') returns null on the API route).
- **Fix:** Changed the API `UpdatePurchaseOrderRequest::withValidator` to use `$this->route('order')` matching the API route definition.
- **Files modified:** `app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php`
- **Verification:** PHPStan clean; route param matches `routes/api.php` `purchase-orders/{order}`.
- **Committed in:** `9722849` (Task 2 commit)

### Plan Inaccuracies (documented, not blocking)

- **StockTransfer date fields:** The plan's Task 3 acceptance criterion #2 references `shipped_at` and `received_at`, which do not exist as columns on `stock_transfers`. The actual date columns (`cancelled_at`, `completed_at`, `created_at`, `updated_at`) were serialized correctly. The semantic intent (serialize all date fields without `toISOString` on string-cast dates) is satisfied.
- **`PermissionsController` permission enum:** The plan does not specify which permission to use for the permissions index endpoint. `ROLES_VIEW` was selected (see Decisions Made).

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** The route-param fix was necessary for correctness — without it the draft-status validation would silently no-op. No scope creep.

## Issues Encountered

None — all four tasks executed cleanly. The single deviation (route param name) was auto-fixed inline as a Rule 1 bug.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- API authorization, mass-assignment, resource serialization, and collection pagination fixes for Phase 1 Wave 3 are complete.
- `composer lint` passes (Pint `{"result":"pass"}` + PHPStan `[OK] No errors`).
- All 5 target API controllers now authorize; `Api\VendorsController` and `Api\PurchaseOrdersController` use `$request->validated()`; `Api\PurchaseOrdersController` returns `PurchaseOrderResource`.
- `ApiCollection` now emits pagination meta, so all API list endpoints (vendors, permissions, settings, etc.) return the `{data, meta}` shape expected by frontend consumers.
- Ready for Plan 01-05 (web controller catch narrowing) and remaining Wave plans.

## Self-Check: PASSED

- [x] `app/Http/Requests/Api/Vendors/StoreVendorRequest.php` exists and authorizes via VENDORS_CREATE->value
- [x] `app/Http/Requests/Api/Vendors/UpdateVendorRequest.php` exists and authorizes via VENDORS_EDIT->value
- [x] `app/Http/Requests/Api/PurchaseOrders/StorePurchaseOrderRequest.php` exists and authorizes via PURCHASE_ORDERS_CREATE->value
- [x] `app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php` exists and authorizes via PURCHASE_ORDERS_EDIT->value
- [x] `request->all()` count is 0 in `Api\VendorsController` and `Api\PurchaseOrdersController`
- [x] All 5 API controllers (ActivityLog, Batches, Permissions, PurchaseOrders, Settings) contain at least one `$this->authorize(` call
- [x] `Api\PurchaseOrdersController::store()` returns `(new PurchaseOrderResource($order))->response()->setStatusCode(201)`
- [x] `Api\PurchaseOrdersController::update()` returns `(new PurchaseOrderResource($order))->response()->setStatusCode(200)`
- [x] `CashRegisterShiftResource` uses `whenLoaded('movements', ...)` and no `relationLoaded('movements')`
- [x] `StockTransferResource` has 0 `->sku` references and includes `->identifier`
- [x] `StockTransferResource` serializes all 4 date fields (cancelled_at, completed_at, created_at, updated_at)
- [x] `ApiCollection` has `meta` block and `paginationInformation()` override
- [x] `composer lint` passes (Pint `{"result":"pass"}` + PHPStan `[OK] No errors`)
- [x] Commits `08e3bb0`, `9722849`, `78a8f17`, `698ff5b` exist on current branch

---
*Phase: 01-critical-fixes-refactor*
*Completed: 2026-06-22*