---
phase: 01-critical-fixes-refactor
plan: 05
subsystem: api
tags: [laravel, controllers, exception-handling, dead-code, policies, enum]
requires:
  - phase: 01-03
    provides: Service delete guards that throw InvalidArgumentException
  - phase: 01-04
    provides: API resource fixes in Api controllers
provides:
  - Narrowed web-controller exception handling to InvalidArgumentException (10 controllers)
  - Dead StockAdjustment policy mapping removed from AuthServiceProvider
  - Dead $request->validated() calls removed from Api Role/MeasurementUnit controllers
  - Verified status enum comparisons in touched files (no violations found)
affects: [01-06, 01-07]
tech-stack:
  added: []
  patterns:
    - "Web controllers catch InvalidArgumentException only; all other exceptions propagate to the global handler"
    - "Standalone $request->validated(); is dead code — FormRequest lifecycle already authorizes and validates; only capture the return when used"
key-files:
  created: []
  modified:
    - app/Http/Controllers/BrandController.php
    - app/Http/Controllers/CategoryController.php
    - app/Http/Controllers/CustomerController.php
    - app/Http/Controllers/MeasurementUnitController.php
    - app/Http/Controllers/VendorsController.php
    - app/Http/Controllers/CatalogController.php
    - app/Http/Controllers/ProductController.php
    - app/Http/Controllers/ProductVariantController.php
    - app/Http/Controllers/ProductOptionController.php
    - app/Http/Controllers/OptionValueController.php
    - app/Providers/AuthServiceProvider.php
    - app/Http/Controllers/Api/RoleController.php
    - app/Http/Controllers/Api/MeasurementUnitController.php
key-decisions:
  - "Narrowed 10 web controllers (not 12): ReceptionOrderController was already using InvalidArgumentException in its 4 try blocks, so it needed no change. Source inspection is authoritative over the REQUIREMENTS.md estimate."
  - "FIX-06 required no code changes: ReceptionOrder->status is a raw DB string enum with no PHP enum/cast, so '!== \"pending\"' is already string-to-string; Api MeasurementUnitController compares raw request input via ->value(), which the rule explicitly permits."
  - "Kept captured '$validated = $request->validated();' in Api MeasurementUnitController store/update (result is used in DB transactions); only removed standalone dead calls whose return value was discarded."
patterns-established:
  - "Business-rule exceptions in web controllers: catch InvalidArgumentException only"
  - "FormRequest calls validated() only when the return array is consumed; never as a standalone statement"
requirements-completed: [FIX-06, FIX-14, FIX-15, FIX-17]
duration: 6min
completed: 2026-06-22
status: complete
---

# Phase 01 Plan 05: Critical Fixes & Refactor — Web Controller Cleanup Summary

**Narrowed 10 web-controller catches to InvalidArgumentException, removed the dead StockAdjustment policy mapping, and dropped 4 dead `$request->validated();` calls — PHPStan level 8 clean.**

## Performance

- **Duration:** 6 min
- **Started:** 2026-06-22T01:57:45Z
- **Completed:** 2026-06-22T02:03:53Z
- **Tasks:** 2
- **Files modified:** 13

## Accomplishments

- Narrowed `catch (Exception $e)` to `catch (InvalidArgumentException $e)` in 10 non-API web controllers (Brand, Category, Customer, MeasurementUnit, Vendors, Catalog, Product, ProductVariant, ProductOption, OptionValue) and swapped the `use Exception;` import for `use InvalidArgumentException;`.
- Removed the dead `StockAdjustment::class => StockAdjustmentPolicy::class` mapping plus its two `use` imports from `AuthServiceProvider` (Laravel auto-discovers policies).
- Removed 4 dead standalone `$request->validated();` calls: 3 from `Api\RoleController` (index/store/update), 1 from `Api\MeasurementUnitController` (index). Kept the 2 captured `$validated = $request->validated();` in MeasurementUnitController store/update.
- Audited FIX-06 status enum comparisons in all touched files — no violations; documented the raw-DB-string-enum reasoning for ReceptionOrder.
- `composer lint` (Pint + PHPStan level 8, 397 files) fully clean.

## Task Commits

Each task was committed atomically:

1. **Task 1: Narrow web-controller exception catches** — `1232d96` (fix) — 10 controllers
2. **Task 2: AuthServiceProvider cleanup, dead validated() removal, FIX-06 audit** — `a47e2fe` (fix) — 3 files

## Files Created/Modified

- `app/Http/Controllers/BrandController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/CategoryController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/CustomerController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/MeasurementUnitController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/VendorsController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/CatalogController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/ProductController.php` — catch narrowed to InvalidArgumentException
- `app/Http/Controllers/ProductVariantController.php` — 2 catches narrowed to InvalidArgumentException
- `app/Http/Controllers/ProductOptionController.php` — 2 catches narrowed to InvalidArgumentException
- `app/Http/Controllers/OptionValueController.php` — catch narrowed to InvalidArgumentException
- `app/Providers/AuthServiceProvider.php` — dead StockAdjustment policy mapping + imports removed
- `app/Http/Controllers/Api/RoleController.php` — 3 dead `$request->validated();` calls removed
- `app/Http/Controllers/Api/MeasurementUnitController.php` — 1 dead `$request->validated();` call removed

## Decisions Made

- **Scope corrected to 10 controllers, not 11 or 12.** Source inspection found `ReceptionOrderController` already used `catch (InvalidArgumentException $e)` in all 4 of its try blocks (store/update/complete/cancel) — it had no broad `catch (Exception` and no `use Exception;` import. REQUIREMENTS.md's "12 controllers" and the plan's "11 controllers" were both stale estimates. The plan's `must_haves.truths` already flagged this discrepancy ("source inspection found 11 controllers, not 12"). Actual broad-catch count: 10. Narrowing was applied to those 10.
- **FIX-06 required no code changes.** Two status comparisons exist in touched files: (a) `ReceptionOrderController` line 147 `$receptionOrder->status !== 'pending'` — the `ReceptionOrder` model has no PHP enum and no cast for `status` (the column is a raw DB `enum('status', ['pending','completed','cancelled'])`), so the model attribute is already a string and the comparison is string-to-string (correct per FIX-06's "raw input" allowance); (b) `Api\MeasurementUnitController` line 30 `$request->string('status')->value() === 'archived'` compares raw request input, which FIX-06 explicitly permits (`->value` only for DB writes and raw input). No enum-to-enum violations.
- **Captured `validated()` calls retained.** The 2 `$validated = $request->validated();` statements in `Api\MeasurementUnitController` store/update are NOT dead — the `$validated` array is consumed by the DB transaction closures. Only standalone calls whose return value was discarded were removed.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Acceptance criterion #3 ("each of the 11 controllers has exactly one catch(InvalidArgumentException)") was unsatisfiable as written**
- **Found during:** Task 1 (acceptance-criteria verification)
- **Issue:** `ReceptionOrderController` already has 4 `catch (InvalidArgumentException $e)` blocks (store/update/complete/cancel) — one per mutating operation — and was never broad. Forcing it to "exactly one" would have collapsed four distinct error-handling paths into one, breaking per-operation error keys (`items` vs `status`). The "11 controllers" count was a stale estimate (the plan's own `must_haves` flagged a 11-vs-12 discrepancy; actual broad-catch count was 10).
- **Fix:** Narrowed the 10 controllers that actually had broad `catch (Exception $e)` blocks. Left `ReceptionOrderController` untouched (it was already narrow). Adjusted AC3 verification to "sum of narrowed catches in the 10 modified controllers equals 13" (7 single-catch + 3 double-catch controllers) plus 4 in ReceptionOrderController = 17 total narrowed catches across the 11 listed files.
- **Files modified:** (none — ReceptionOrderController intentionally not modified)
- **Verification:** `grep -R "catch (Exception" app/Http/Controllers/*.php` returns 0 in non-API/non-Auth/non-Pos web controllers; PHPStan clean on all 11 files.
- **Committed in:** 1232d96 (Task 1 commit) — the deviation is the *scope* of the fix, not a separate code change.

---

**Total deviations:** 1 auto-fixed (1 bug — unsatisfiable acceptance criterion, scope corrected via source inspection)
**Impact on plan:** No scope creep. The deviation only reconciled an inaccurate count estimate with ground truth; all 4 requirements (FIX-06, FIX-14, FIX-15, FIX-17) are fully satisfied.

## Issues Encountered

- The plan's `<verify>` for AC2 used `grep -c "request->validated();"` which matches both the dead standalone form (`$request->validated();`) and the legitimate captured form (`$validated = $request->validated();`). The literal grep returns count 2 (the two captured calls in MeasurementUnitController store/update), which would falsely report FAIL. Verified with the precise standalone pattern `^\s+\$request->validated\(\);$` → 0 matches → PASS. No code change needed; the captured calls are correct.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Wave 4 web-controller cleanup complete; all FIX-14/15/17 requirements satisfied and FIX-06 audited.
- `composer lint` (Pint + PHPStan level 8) fully clean.
- Ready for remaining plans in Phase 01 (01-06, 01-07) and subsequent verification (`/gsd-verify-work`).

## Self-Check: PASSED

- **Files exist on disk:** All 14 modified files + SUMMARY.md verified via `[ -f ]`.
- **Commits exist:** `1232d96` (Task 1) and `a47e2fe` (Task 2) both present in `git log --all`.
- **AC: no `catch (Exception` in non-API/non-Auth/non-Pos web controllers:** PASS (count=0).
- **AC: `AuthServiceProvider` has no `StockAdjustment` references:** PASS.
- **AC: no dead standalone `$request->validated();` in Api Role/MeasurementUnitController:** PASS.
- **`composer lint` (Pint + PHPStan level 8, 397 files):** PASS — no errors.

---
*Phase: 01-critical-fixes-refactor*
*Completed: 2026-06-22*