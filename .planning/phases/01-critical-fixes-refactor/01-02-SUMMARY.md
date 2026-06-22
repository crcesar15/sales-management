---
phase: 01
plan: 02
subsystem: backend
tags: [laravel, services, fifo, cash-register, stock, log-rotation, invalid-argument-exception]

requires:
  - phase: "01-critical-fixes-refactor"
    provides: "Phase planning context and FIX-xx requirements"
  - phase: "01-critical-fixes-refactor"
    plan: "01"
    provides: "Wave 0 Pest stubs for FIFO and cash-register shift tests"

provides:
  - "Canonical FIFO stock deduction in FifoStockDeductionService with deductForOrder + deductForTransfer"
  - "Batch auto-close at zero and InvalidArgumentException on insufficient stock for sale and transfer paths"
  - "CashRegisterShiftService state machine with TRANSITION_MAP + validateTransition()"
  - "recalculateStock() call sites using find() + null check instead of firstOrFail()"
  - "Daily log rotation for default stack and bounded browser channel"

affects:
  - "01-critical-fixes-refactor"
  - "Phase 1 Wave 6 (targeted Pest coverage)"

tech-stack:
  added: []
  patterns:
    - "Per-service FifoStockDeductionService as single source of truth"
    - "TRANSITION_MAP + validateTransition() state machine for CashRegisterShiftService"
    - "find() + InvalidArgumentException null check instead of firstOrFail() for variant re-query"
    - "Default stack channel switched to daily"

key-files:
  created: []
  modified:
    - app/Services/FifoStockDeductionService.php
    - app/Services/BatchService.php
    - app/Services/StockAdjustmentService.php
    - app/Services/StockTransferService.php
    - app/Services/ReceptionOrderService.php
    - app/Services/SalesOrderService.php
    - app/Services/CashRegisterShiftService.php
    - config/logging.php

key-decisions:
  - "Kept mixed transaction ownership: deductForOrder runs inside caller's transaction, deductForTransfer opens its own"
  - "Used enum instances for model-side status comparisons in CashRegisterShiftService; ->value only for DB writes and raw input"
  - "Truncated existing 310MB browser.log once; daily rotation prevents unbounded regrowth"

patterns-established:
  - "All FIFO logic lives in FifoStockDeductionService; BatchService is a one-line delegate"
  - "recalculateStock() call sites use ProductVariant::find() with an InvalidArgumentException null check, never firstOrFail()"
  - "Cash register shift transitions route through a private TRANSITION_MAP + validateTransition()"

requirements-completed:
  - FIX-11
  - FIX-12
  - FIX-21
  - FIX-22

# Metrics
duration: 40 min
completed: 2026-06-22
status: complete
---

# Phase 01 Plan 02: Backend Core Service Fixes Summary

**Consolidated FIFO stock deduction into `FifoStockDeductionService`, added a `TRANSITION_MAP` state machine to `CashRegisterShiftService`, fixed `recalculateStock()` call sites to avoid `firstOrFail()`, and configured daily log rotation with a bounded `browser` channel.**

## Performance

- **Duration:** 40 min
- **Started:** 2026-06-22T00:43:00Z
- **Completed:** 2026-06-22T01:23:44Z
- **Tasks:** 3
- **Files modified:** 8

## Accomplishments

- `FifoStockDeductionService` now provides both `deductForOrder()` (caller-owned transaction, auto-closes batches at zero) and `deductForTransfer()` (self-contained transaction, increments `transferred_quantity`, auto-closes at zero); both throw `InvalidArgumentException` on insufficient stock.
- `BatchService` no longer contains duplicate FIFO logic: `deductFIFO()` and `getAvailableBatches()` are removed, and `deductFIFOForTransfer()` is a one-line delegate.
- All `recalculateStock()` call sites across the six touched services use `ProductVariant::find()` with an `InvalidArgumentException` null check instead of `firstOrFail()`.
- `CashRegisterShiftService` defines a `TRANSITION_MAP` (`open => [closed, forced_close]`, `closed => []`, `forced_close => []`) and `validateTransition()`; `closeShift()`, `forceCloseShift()`, and `addMovement()` call it.
- `config/logging.php` default `stack` channel now uses `daily`, a `browser` daily channel is configured, and the existing 310MB `storage/logs/browser.log` was truncated.

## Task Commits

Each task was committed atomically:

1. **Task 1: Consolidate FIFO and fix recalculateStock call sites (FIX-11 / FIX-21)** - `3378421` (fix)
2. **Task 2: Centralize cash-register shift transitions (FIX-12)** - `c41361d` (fix)
3. **Task 3: Configure daily log rotation (FIX-22)** - `64e1849` (fix)

## Files Created/Modified

- `app/Services/FifoStockDeductionService.php` - Added `deductForTransfer()`, auto-close-at-zero in both methods, `find()` + null check for recalculation
- `app/Services/BatchService.php` - Removed dead `deductFIFO()` / `getAvailableBatches()`; `deductFIFOForTransfer()` delegates to `FifoStockDeductionService`
- `app/Services/StockAdjustmentService.php` - `recalculateStock()` now uses `find()` with null check; `variantId` cast to int
- `app/Services/StockTransferService.php` - `recalculateStock()` now uses `find()` with null check; variant IDs mapped to int
- `app/Services/ReceptionOrderService.php` - `recalculateStock()` now uses `find()` with null check; added eager loads for measurement unit and catalog entry unit
- `app/Services/SalesOrderService.php` - Added eager loads for `items` and `items.productVariant.product`
- `app/Services/CashRegisterShiftService.php` - Added `TRANSITION_MAP` + `validateTransition()`; enum instance comparisons on model side
- `config/logging.php` - Default stack uses `daily`; added `browser` daily channel with 7-day retention

## Decisions Made

- **Mixed transaction ownership preserved:** `deductForOrder()` remains inside the caller's `DB::transaction()` (used by `SalesOrderService::create()`/`transitionStatus()`), while `deductForTransfer()` opens its own transaction to match the previous `BatchService` contract and avoid changing `StockTransferService`.
- **Enum comparisons on the model side:** `CashRegisterShiftService` now compares `$register->status !== CashRegisterStatus::ACTIVE` and writes enum-backed values to the DB via `CashRegisterShiftStatus::OPEN`, etc.
- **Log bounding strategy:** Configured a dedicated `browser` daily channel so the Laravel Boost browser logger writes to a rotated file instead of a single unbounded `storage/logs/browser.log`.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Cast `product_variant_id` from request data to int before `ProductVariant::find()`**
- **Found during:** Task 1 (`recalculateStock()` call site fixes)
- **Issue:** `ProductVariant::find($data['product_variant_id'])` with a `mixed` array value caused PHPStan to infer `ProductVariant|Collection<int, ProductVariant>` (union with collection), making the `recalculateStock()` method call fail static analysis. The plan only required switching from `firstOrFail()` to `find()` + null check.
- **Fix:** Cast the incoming variant ID to `int` in `StockAdjustmentService::apply()` and mapped plucked IDs to `int` in `StockTransferService::completeTransfer()` and `ReceptionOrderService::complete()` before calling `ProductVariant::find()`.
- **Files modified:** `app/Services/StockAdjustmentService.php`, `app/Services/StockTransferService.php`, `app/Services/ReceptionOrderService.php`
- **Verification:** `vendor/bin/phpstan analyse` passes on all touched services with no errors
- **Committed in:** `3378421` (Task 1 commit)

**2. [Rule 3 - Blocking] Removed null-safe operator on left side of `??` in `FifoStockDeductionService`**
- **Found during:** Task 1 PHPStan verification
- **Issue:** `ProductVariant::find(...)?->identifier ?? "ID ..."` triggered PHPStan `nullsafe.neverNull` because the null-safe short-circuit is unnecessary on the left side of a coalesce.
- **Fix:** Refactored to a local nullable variable followed by a ternary fallback.
- **Files modified:** `app/Services/FifoStockDeductionService.php`
- **Verification:** `vendor/bin/phpstan analyse app/Services/FifoStockDeductionService.php` passes
- **Committed in:** `3378421` (Task 1 commit)

---

**Total deviations:** 2 auto-fixed (1 missing critical, 1 blocking)
**Impact on plan:** Both auto-fixes were necessary for PHPStan level 8 compliance and type-safe `find()` behavior. No scope creep.

## Issues Encountered

- **PHPStan type inference for `Model::find()` with mixed IDs:** Without an explicit `int` cast, PHPStan inferred a union with `Collection`, breaking the `recalculateStock()` method call. Casting the IDs to `int` resolved the inference. This was auto-fixed as a Rule 2/3 deviation.
- **Laravel Boost `browser.log` at 310MB:** The file contained repetitive Vue warnings and Vite polling messages. It was truncated once; the new `browser` daily channel will rotate automatically and prevent unbounded growth.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Backend core service fixes are complete and `composer lint` passes.
- Wave 0 Pest stubs still report pending (`todo()`) and run without fatal errors.
- Ready for 01-03 plan to continue Phase 1 convention/security fixes.

## Self-Check: PASSED

- [x] `app/Services/FifoStockDeductionService.php` contains `deductForTransfer()` and `deductForOrder()` with auto-close-at-zero
- [x] `app/Services/BatchService.php` no longer contains `deductFIFO()` or `getAvailableBatches()`; `deductFIFOForTransfer()` is a one-line delegate
- [x] `RuntimeException` count in `FifoStockDeductionService.php` and `BatchService.php` is 0
- [x] `app/Services/CashRegisterShiftService.php` contains `TRANSITION_MAP` and 4+ `validateTransition()` calls
- [x] `config/logging.php` default stack uses `daily` and a `browser` channel exists
- [x] `composer lint` passes (Pint + PHPStan level 8)
- [x] Targeted Pest stubs (`FifoStockDeductionServiceTest`, `CashRegisterShiftTransitionsTest`) report todos, no fatal errors
- [x] Commits `3378421`, `c41361d`, `64e1849` exist on current branch

---
*Phase: 01-critical-fixes-refactor*
*Completed: 2026-06-22*
