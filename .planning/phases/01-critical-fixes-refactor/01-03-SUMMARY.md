---
phase: 01
plan: 03
subsystem: backend
tags: [laravel, settings, cache, n+1, eager-loading, invalid-argument-exception, reception-orders, sales-orders, tdd]

requires:
  - phase: "01-critical-fixes-refactor"
    provides: "Phase planning context and FIX-xx requirements"
  - phase: "01-critical-fixes-refactor"
    plan: "01"
    provides: "Wave 0 Pest stubs including SettingsCacheTest todo stubs"
  - phase: "01-critical-fixes-refactor"
    plan: "02"
    provides: "Stable FIFO/recalculate contracts and initial ReceptionOrderService/SalesOrderService eager loads"

provides:
  - "Driver-agnostic key-based settings cache (Setting::get/set/group + SettingsService::updateGroup + Api\\SettingsController::update) with per-key and per-group invalidation"
  - "Completed SalesOrderResource eager loads (items.saleUnit, payments) in SalesOrderService::list()"
  - "Public ReceptionOrderService::getClaimedQuantities() reused by ReceptionOrderController create()/edit() instead of duplicated bcadd loops"
  - "Six service delete() guards throw InvalidArgumentException so narrowed controller catches (Plan 01-05) can handle them"
  - "Pest feature tests proving Setting cache works under the array driver and invalidates on write"

affects:
  - "01-critical-fixes-refactor"
  - "01-critical-fixes-refactor plan 01-05 (web controller narrowed catch depends on InvalidArgumentException service guards)"
  - "Phase 1 Wave 6 (targeted Pest coverage)"

tech-stack:
  added: []
  patterns:
    - "Key-based Cache::rememberForever() + explicit Cache::forget() per key and per group instead of Cache::tags() (driver-agnostic)"
    - "Service delete guards throw InvalidArgumentException for business-rule violations (not generic Exception)"
    - "Shared aggregation helpers live on the service and are called from the controller, not duplicated in the controller"

key-files:
  created: []
  modified:
    - app/Models/Setting.php
    - app/Services/SettingsService.php
    - app/Http/Controllers/Api/SettingsController.php
    - app/Services/ReceptionOrderService.php
    - app/Services/SalesOrderService.php
    - app/Http/Controllers/ReceptionOrderController.php
    - app/Services/CategoryService.php
    - app/Services/VendorService.php
    - app/Services/MeasurementUnitService.php
    - app/Services/BrandService.php
    - app/Services/CustomerService.php
    - app/Services/ProductService.php
    - tests/Feature/Settings/SettingsCacheTest.php

key-decisions:
  - "Setting::set() fetches the row to learn its group, then forgets both settings.{key} and settings.group.{group}; throws InvalidArgumentException for unknown keys"
  - "Added HasFactory trait to Setting model (was missing despite SettingFactory existing) so Pest tests can use Setting::factory()"
  - "SalesOrderService::list() eager loads items.productVariant.product (via nested path), items.saleUnit, and payments to fully cover SalesOrderResource + SalesOrderItemResource + SalesOrderPaymentResource rendering"
  - "ReceptionOrderService::list() eager loads were already added in Plan 01-02; this plan verified them and made getClaimedQuantities() public"
  - "Service delete guards: one InvalidArgumentException per guard (VendorService has two guards = two throws); DB/unexpected exceptions are not converted"

patterns-established:
  - "All settings cache invalidation is key-based (settings.{key}, settings.group.{group}); Cache::tags() is forbidden because it throws BadMethodCallException on file/array drivers"
  - "Service delete() business-rule guards throw InvalidArgumentException; controllers catch InvalidArgumentException specifically (narrowed catch in Plan 01-05)"
  - "Aggregation helpers used by both controller create()/edit() and service validation are public on the service (single source of truth)"

requirements-completed:
  - FIX-14
  - FIX-16
  - FIX-18
  - FIX-19
  - FIX-20

# Metrics
duration: 12 min
completed: 2026-06-22
status: complete
---

# Phase 01 Plan 03: Backend Service Fixes (Cache, N+1, Helper, Exceptions) Summary

**Replaced `Cache::tags()` with driver-agnostic key-based settings cache invalidation, completed `SalesOrderResource` eager loads, extracted `getClaimedQuantities()` into a public service helper reused by the controller, and converted six service delete guards from generic `Exception` to `InvalidArgumentException` so narrowed controller catches will work.**

## Performance

- **Duration:** 12 min
- **Started:** 2026-06-22T01:28:43Z
- **Completed:** 2026-06-22T01:40:23Z
- **Tasks:** 4
- **Files modified:** 13 (12 source + 1 test)

## Accomplishments

- `Setting::get()`/`group()` use `Cache::rememberForever()` without tags; `Setting::set()` fetches the row, updates it, then forgets `settings.{key}` and `settings.group.{group}`; throws `InvalidArgumentException` for unknown keys. Works on the default `file` driver and the `array` driver used in tests (where `Cache::tags()` throws `BadMethodCallException`).
- `SettingsService::updateGroup()` forgets each written key plus the group cache instead of `Cache::tags()->flush()`.
- `Api\SettingsController::update()` forgets per-key and per-group caches instead of the non-existent flat `settings` key.
- `SalesOrderService::list()` eager loads `items.productVariant.product`, `items.saleUnit`, and `payments` (in addition to `customer`, `user`, `store`, `cashRegisterShift`) so `SalesOrderResource` / `SalesOrderItemResource` / `SalesOrderPaymentResource` render without N+1 queries.
- `ReceptionOrderService::list()` eager loads (`lineItems.productVariant.product.measurementUnit`, `lineItems.catalogEntry.unit`) were verified present (added in 01-02).
- `ReceptionOrderService::getClaimedQuantities()` is now public; `ReceptionOrderController::create()` and `edit()` call it instead of duplicating the `bcadd` aggregation loop.
- The six service `delete()` methods (`CategoryService`, `VendorService`, `MeasurementUnitService`, `BrandService`, `CustomerService`, `ProductService`) throw `InvalidArgumentException` for business-rule guards; the `use Exception;` import was replaced with `use InvalidArgumentException;` in each.
- Pest `SettingsCacheTest` has four real tests (replacing `->todo()` stubs) proving `Setting::get()` returns the configured value, `Setting::set()` invalidates the single-key and group caches immediately, and no `BadMethodCallException` is thrown.

## Task Commits

Each task was committed atomically:

1. **Task 1 (TDD RED): Failing tests for key-based settings cache** - `f85ff35` (test)
2. **Task 1 (TDD GREEN): Implement key-based settings cache invalidation** - `f317fbc` (feat)
3. **Task 2: Complete SalesOrderResource eager loads in SalesOrderService::list()** - `480fe11` (fix)
4. **Task 3: Extract claimed-quantities helper and reuse in controller** - `3b17e16` (refactor)
5. **Task 4: Convert service delete guards to InvalidArgumentException** - `bdbcb4f` (fix)

_Note: Task 1 is a `tdd="true"` task — RED (failing tests) then GREEN (implementation). No REFACTOR commit was needed; the implementation was already clean._

## Files Created/Modified

- `app/Models/Setting.php` - Removed `Cache::tags()`; `get()`/`group()` use `Cache::rememberForever()`; `set()` fetches row, updates, forgets per-key + per-group caches; added `HasFactory` trait
- `app/Services/SettingsService.php` - `updateGroup()` forgets each written key plus the group cache instead of `Cache::tags()->flush()`
- `app/Http/Controllers/Api/SettingsController.php` - `update()` forgets per-key + per-group caches instead of flat `settings`; uses `Illuminate\Support\Facades\Cache`
- `app/Services/SalesOrderService.php` - `list()` eager loads `items.productVariant.product`, `items.saleUnit`, `payments` (multi-line `with([...])`)
- `app/Services/ReceptionOrderService.php` - `getClaimedQuantities()` made public (was private); `list()` eager loads already present from 01-02
- `app/Http/Controllers/ReceptionOrderController.php` - `create()` and `edit()` call `$this->receptionService->getClaimedQuantities()` instead of duplicated `bcadd` loops; removed now-unused `receptionOrders.lineItems` eager-load constraints
- `app/Services/CategoryService.php` - `delete()` guard throws `InvalidArgumentException`
- `app/Services/VendorService.php` - `delete()` guards (two) throw `InvalidArgumentException`
- `app/Services/MeasurementUnitService.php` - `delete()` guard throws `InvalidArgumentException`
- `app/Services/BrandService.php` - `delete()` guard throws `InvalidArgumentException`
- `app/Services/CustomerService.php` - `delete()` guard throws `InvalidArgumentException`
- `app/Services/ProductService.php` - `delete()` guard throws `InvalidArgumentException`
- `tests/Feature/Settings/SettingsCacheTest.php` - Four real Pest tests replacing `->todo()` stubs

## Decisions Made

- **`Setting::set()` fetches the row to learn its group:** The plan's canonical pattern (01-RESEARCH.md / 01-PATTERNS.md) fetches the `Setting` model first so both `settings.{key}` and `settings.group.{group}` can be invalidated. Unknown keys throw `InvalidArgumentException` rather than silently creating a row.
- **Added `HasFactory` to `Setting`:** The `SettingFactory` existed but the model lacked the `HasFactory` trait, so `Setting::factory()` threw `BadMethodCallException` in tests. Added the trait with the standard `@use HasFactory<SettingFactory>` PHPDoc, matching every other model in the app. This is a Rule 2 missing-critical fix for testability.
- **`SalesOrderService::list()` loads `payments` and `items.saleUnit`:** The plan's acceptance criteria only required `items` and `items.productVariant.product`, but `SalesOrderResource` also renders `payments` and `SalesOrderItemResource` renders `saleUnit`. Loading them prevents real N+1 queries and fully satisfies FIX-20 ("N+1 in SalesOrderResource"). `items` is loaded transitively via the `items.productVariant.product` nested path.
- **Task 2 `list()` eager loads already present:** `ReceptionOrderService::list()` already eager-loads `lineItems.productVariant.product.measurementUnit` and `lineItems.catalogEntry.unit` (added in Plan 01-02). This plan verified them and completed the `SalesOrderService` side.
- **One `InvalidArgumentException` per guard:** `VendorService::delete()` has two guards (purchase orders + catalog entries), so it has two `throw new InvalidArgumentException(...)` statements. The other five services have one guard each. DB/unexpected exceptions are not converted.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Added `HasFactory` trait to `Setting` model**
- **Found during:** Task 1 (RED phase — tests failed with `Call to undefined method App\Models\Setting::factory()`)
- **Issue:** `database/factories/SettingFactory.php` exists but `Setting` did not use the `HasFactory` trait, so `Setting::factory()` was unavailable. The TDD tests (and any future factory-based test) could not create settings.
- **Fix:** Added `use HasFactory;` with `@use HasFactory<SettingFactory>` PHPDoc and the `Database\Factories\SettingFactory` import, matching the pattern in every other model (`Brand`, `Category`, etc.).
- **Files modified:** `app/Models/Setting.php`
- **Verification:** `SettingsCacheTest` passes (4 tests, 8 assertions); `composer lint` passes
- **Committed in:** `f317fbc` (Task 1 GREEN commit)

**2. [Rule 2 - Missing Critical] Added `payments` and `items.saleUnit` eager loads to `SalesOrderService::list()`**
- **Found during:** Task 2 (verifying `SalesOrderResource` rendering)
- **Issue:** The plan's acceptance criteria required only `items` and `items.productVariant.product`, but `SalesOrderResource` also renders `payments` (via `SalesOrderPaymentResource`) and `SalesOrderItemResource` renders `saleUnit`. Without these loads, FIX-20 ("N+1 in SalesOrderResource") would be only partially fixed.
- **Fix:** Expanded the `with([...])` array to include `items.saleUnit` and `payments` alongside the already-loaded `items.productVariant.product`.
- **Files modified:** `app/Services/SalesOrderService.php`
- **Verification:** PHPStan clean; `composer lint` passes
- **Committed in:** `480fe11` (Task 2 commit)

---

**Total deviations:** 2 auto-fixed (2 missing critical)
**Impact on plan:** Both auto-fixes were necessary for correctness/testability. The `HasFactory` fix unblocks the TDD tests; the extra eager loads fully resolve the SalesOrderResource N+1. No scope creep.

## Acceptance Criteria Grep Notes

- **Task 2 verify grep `grep -c "with(\['lineItems" app/Services/ReceptionOrderService.php`:** The `ReceptionOrderService::list()` eager loads are present (lines 32-33) but use a multi-line `with([` array, so the single-line grep returns 0. The functional requirement (eager-load `lineItems.productVariant.product.measurementUnit` and `lineItems.catalogEntry.unit`) is satisfied. Same for `SalesOrderService` (`with(['items` returns 0 because the load is now multi-line `with([` ... `'items.productVariant.product'`). Verified via `grep -n "lineItems\|items"` showing the loads present and PHPStan clean.
- **Task 4 verify grep `grep -c "throw new InvalidArgumentException" ... | grep -q "6"`:** This checks for a file containing exactly 6 occurrences. The correct semantic is "one `InvalidArgumentException` per guard" — `VendorService` has 2 guards (2 throws), the other five services have 1 guard each (1 throw each) = 7 total across 6 files. No single file has 6. The semantic requirement (acceptance criterion #2: "exactly one per guard") is satisfied; the grep literal is over-constrained. Database/unexpected exceptions were not converted (acceptance criterion #3).

## TDD Gate Compliance

Task 1 is `tdd="true"`. Gate sequence verified in git log:
1. **RED gate:** `test(01-03): add failing tests for key-based settings cache` (`f85ff35`) — 4 tests, all failed (Setting used `Cache::tags()` which throws on the array driver; model lacked `HasFactory`).
2. **GREEN gate:** `feat(01-03): implement key-based settings cache invalidation` (`f317fbc`) — all 4 tests pass (8 assertions).
3. **REFACTOR gate:** Not needed — the implementation was already clean; no separate refactor commit.

Gate sequence: RED → GREEN ✓. RED tests failed for the right reason (tag-based cache throws on array driver). GREEN implementation is minimal (key-based cache + `HasFactory`).

## Issues Encountered

None — all four tasks executed cleanly. The two deviations (`HasFactory` trait, extra eager loads) were auto-fixed inline as Rule 2 missing-critical items.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Backend service fixes for Phase 1 Wave 2 are complete and `composer lint` passes (Pint + PHPStan level 8).
- `SettingsCacheTest` passes under the `array` driver; `CACHE_DRIVER=file` will now work for local development.
- The six service `delete()` guards throw `InvalidArgumentException`, enabling Plan 01-05 to narrow the web controller `catch (Exception)` to `catch (InvalidArgumentException)` without losing the business-rule messages.
- `ReceptionOrderController` no longer duplicates the claimed-quantities aggregation.
- Ready for Plan 01-05 (web controller catch narrowing) and the remaining Wave 2/3 plans.

## Self-Check: PASSED

- [x] `app/Models/Setting.php` contains no `Cache::tags()` and uses `Cache::rememberForever()`/`Cache::forget()`
- [x] `app/Services/SettingsService.php` contains no `Cache::tags()` and forgets per-key + per-group
- [x] `app/Http/Controllers/Api/SettingsController.php` contains no `Cache::tags()` and forgets per-key + per-group
- [x] `app/Services/ReceptionOrderService.php` `getClaimedQuantities()` is public
- [x] `app/Http/Controllers/ReceptionOrderController.php` calls `getClaimedQuantities()` twice (create + edit) with no `bcadd` loop
- [x] `app/Services/SalesOrderService.php` `list()` eager loads `items.productVariant.product`, `items.saleUnit`, `payments`
- [x] The six service `delete()` methods contain 0 `throw new Exception` and one `throw new InvalidArgumentException` per guard
- [x] `composer lint` passes (Pint `{"result":"pass"}` + PHPStan `[OK] No errors`)
- [x] `SettingsCacheTest` passes (4 tests, 8 assertions)
- [x] Commits `f85ff35`, `f317fbc`, `480fe11`, `3b17e16`, `bdbcb4f` exist on current branch

---
*Phase: 01-critical-fixes-refactor*
*Completed: 2026-06-22*