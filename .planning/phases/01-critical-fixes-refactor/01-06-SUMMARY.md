---
phase: 01-critical-fixes-refactor
plan: 06
subsystem: api
tags: [laravel, services, security, sql-injection, sort-whitelist, phpstan]

# Dependency graph
requires:
  - phase: 01-05
    provides: Web-controller cleanup and dead-code removal completed before finalizing service-layer sort contracts.
provides:
  - "Per-service SORT_COLUMN_MAP whitelist on 11 services covering every list()-style method"
  - "orderDirection validated to asc/desc only in every touched service"
  - "CatalogService sort selection unified on SORT_COLUMN_MAP (match() removed)"
  - "VariantService sort map expanded to cover getVariants(), listAllVariants(), getVendorCatalog()"
affects: [02-frontend-cleanup, pos-module, catalog-module, inventory-module]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Per-service SORT_COLUMN_MAP const (mirrors StockService) — not a shared trait"
    - "orderDirection normalized via in_array(mb_strtolower(...), ['asc','desc'], true) ?: 'asc'/'desc'"

key-files:
  created: []
  modified:
    - "app/Services/RoleService.php"
    - "app/Services/CategoryService.php"
    - "app/Services/VariantService.php"
    - "app/Services/VendorService.php"
    - "app/Services/StoreService.php"
    - "app/Services/UserService.php"
    - "app/Services/MeasurementUnitService.php"
    - "app/Services/BrandService.php"
    - "app/Services/CustomerService.php"
    - "app/Services/ProductService.php"
    - "app/Services/CatalogService.php"
    - ".planning/phases/01-critical-fixes-refactor/deferred-items.md"

key-decisions:
  - "Used a per-service private const SORT_COLUMN_MAP (not a shared trait) to match the canonical StockService pattern and the FIX-13 requirement."
  - "Unknown orderBy keys fall back to created_at (safe, indexed default) rather than the service's primary column, so an attacker probing for sensitive columns gets a harmless sort."
  - "orderDirection defaults to 'asc' (or 'desc' where the service default was already desc, e.g. CatalogService::list) when input is not exactly asc/desc."
  - "FIX-06 enum-to-enum requirement verified as already satisfied: none of the 11 touched models cast status to a backed enum, so the raw-input string comparisons used for status filtering ('all'/'archived'/'active'/...) are the correct FIX-06 pattern. No enum-to-enum changes were needed in this plan."

patterns-established:
  - "SORT_COLUMN_MAP const + ?? safe-default + in_array(mb_strtolower(...),['asc','desc'],true) validation is now the universal list() sort contract for all services."

requirements-completed: [FIX-06, FIX-13]

# Metrics
duration: 19 min
completed: 2026-06-22
status: complete
---

# Phase 01 Plan 06: Service sort-column whitelists Summary

**Per-service SORT_COLUMN_MAP whitelists + asc/desc direction validation across 11 services, mirroring StockService and replacing CatalogService/VariantService match() expressions**

## Performance

- **Duration:** 19 min
- **Started:** 2026-06-22T02:09:57Z
- **Completed:** 2026-06-22T02:29:04Z
- **Tasks:** 1
- **Files modified:** 11 service files + 1 deferred-items note

## Accomplishments
- Added a `private const SORT_COLUMN_MAP` to all 11 FIX-13 services (RoleService, CategoryService, VariantService, VendorService, StoreService, UserService, MeasurementUnitService, BrandService, CustomerService, ProductService, CatalogService), mapping every user-facing sort key to a real DB column.
- Unknown `orderBy` values now fall back to a safe default column (`created_at` or the table's `created_at`) instead of being passed raw to `->orderBy()`, closing the column-name injection / side-channel-sorting vector.
- `orderDirection` is validated to `asc`/`desc` only (case-insensitive, normalized via `mb_strtolower`) in every touched `list()`-style method; any other value defaults to a safe direction.
- Expanded `VariantService`'s pre-existing 2-entry map to a 12-entry map covering `getVariants()`, `listAllVariants()`, and `getVendorCatalog()`; replaced the `match()` whitelist in `getVendorCatalog()` with the const lookup.
- Replaced all `match($orderBy)` / inline orderBy expressions in `CatalogService::list()`, `listGroupedByProduct()`, and `listVariants()` with a single `SORT_COLUMN_MAP` const, satisfying the plan's "no longer relies solely on a match() expression" requirement.
- Verified FIX-06 status: none of the 11 touched models cast `status` to a backed enum, so the existing raw-input string comparisons for status filtering are the correct FIX-06 pattern — no enum-to-enum changes were required in this plan.
- `composer lint` (Pint --dirty + PHPStan level 8 across the whole app) passes with no errors.

## Task Commits

Each task was committed atomically:

1. **Task 1: Add per-service SORT_COLUMN_MAP whitelists** — `a78aaf9` (fix)

**Plan metadata:** pending (deferred-items.md + SUMMARY.md committed in the docs commit)

## Files Created/Modified
- `app/Services/RoleService.php` — added SORT_COLUMN_MAP (name, created_at, updated_at); list() whitelists orderBy + validates direction.
- `app/Services/CategoryService.php` — added SORT_COLUMN_MAP (name, created_at, updated_at); list() whitelisted.
- `app/Services/VariantService.php` — expanded SORT_COLUMN_MAP to 12 entries (product/brand/variant/catalog columns); applied to getVariants(), listAllVariants(), getVendorCatalog(); match() in getVendorCatalog replaced with const lookup; direction validated in all three.
- `app/Services/VendorService.php` — added SORT_COLUMN_MAP (fullname, email, status, timestamps); list() whitelisted.
- `app/Services/StoreService.php` — added SORT_COLUMN_MAP (name, code, status, timestamps); list() whitelisted.
- `app/Services/UserService.php` — added SORT_COLUMN_MAP (first_name, last_name, email, username, status, timestamps); list() whitelisted.
- `app/Services/MeasurementUnitService.php` — added SORT_COLUMN_MAP (name, abbreviation, timestamps); list() whitelisted.
- `app/Services/BrandService.php` — added SORT_COLUMN_MAP (name, timestamps); list() whitelisted.
- `app/Services/CustomerService.php` — added SORT_COLUMN_MAP (first_name, last_name, email, tax_id, status, timestamps); list() whitelisted.
- `app/Services/ProductService.php` — added SORT_COLUMN_MAP (name, status, timestamps); list() whitelisted.
- `app/Services/CatalogService.php` — added SORT_COLUMN_MAP (product_name, brand_name, name, identifier, vendor_count, price, status, timestamps, id); replaced match() in listVariants() and the inline `$needsProductJoin ? 'products.name' : $orderBy` logic in list()/listGroupedByProduct() with const lookups; direction validated in all three.
- `.planning/phases/01-critical-fixes-refactor/deferred-items.md` — logged out-of-scope discoveries (pre-existing trashed-filter test failures, VariantService filter_by injection, dead CatalogService::listGroupedByProduct method).

## Decisions Made
- **Per-service const, not a shared trait** — matches StockService and the explicit FIX-13 wording ("per-service SORT_COLUMN_MAP, not a shared trait"). Each service's whitelist reflects its own table columns and join keys.
- **Safe default = `created_at`** — for unknown orderBy keys. `created_at` exists on every table, is non-sensitive, and is indexed. This prevents an attacker from probing column names by observing sort order.
- **Direction default preserves each service's original default** — services that defaulted to `asc` keep `asc` on invalid input; CatalogService::list() (which defaulted to `desc`) keeps `desc` on invalid input. This avoids changing observed behavior for legitimate callers.
- **`mb_strtolower` for direction normalization** — enforced by the project's Pint `mb_str_functions` rule; applied to all direction checks for consistency.
- **FIX-06 requires no changes in this plan** — verified via model casts() inspection that none of the 11 touched models cast `status` to a backed enum. The raw-input string comparisons (`'all'`, `'archived'`, `'active'`, `'inactive'`) used for status filtering are explicitly permitted by FIX-06 ("`->value` only for DB writes / raw input"). Documented in SUMMARY rather than code changes.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Pint mb_str_functions / spacing fixers**
- **Found during:** Task 1 (post-edit `vendor/bin/pint --test`)
- **Issue:** Initial edits used `strtolower` and plain ternary spacing; the project Pint config (`mb_str_functions`, `unary_operator_spaces`, `not_operator_with_successor_space`) flagged all 11 files.
- **Fix:** Ran `vendor/bin/pint` on the 11 files to auto-apply `mb_strtolower` and the spacing fixers.
- **Files modified:** all 11 service files
- **Verification:** `vendor/bin/pint --test` on the 11 files returns `{"result":"pass"}`; `composer lint` (Pint --dirty + PHPStan) passes with no errors.
- **Committed in:** `a78aaf9` (part of Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking — Pint style conformance)
**Impact on plan:** No scope creep. The Pint fixes are mandatory style conformance per AGENTS.md/commands.md and were applied to the same files the task already modifies.

## Issues Encountered
- **Pre-existing feature-test failures (out of scope):** While running related feature tests to sanity-check the refactor, `tests/Feature/BrandTest.php` ("soft-deleted records excluded from default list"), `tests/Feature/MeasurementUnitTest.php`, and `tests/Feature/CategoryTest.php` failed on `meta.total` assertions. Verified pre-existing by stashing plan 01-06's changes and re-running on the original `app/Services/BrandService.php` — the same 3 failures occur. Root cause is the `status='all'` default + `->when($status === 'all', fn ($q) => $q->withTrashed())` behavior, which returns both active and soft-deleted records when no `status` param is supplied; the tests expect the no-param default to exclude soft-deleted records. Plan 01-06 only changed orderBy/orderDirection handling and preserved the withTrashed/onlyTrashed lines verbatim. This is a separate filter-semantics decision and is logged in `deferred-items.md` for a future phase.

## User Setup Required
None — no external service configuration required. This is a pure service-layer refactor.

## Threat Flags
None. The changes implement the threat-model mitigations from the plan (T-01-06-S1 sort-column tampering, T-01-06-S2 status enum comparisons) and introduce no new trust-boundary surface:
- T-01-06-S1 (mitigate): every user-supplied `orderBy` now passes through `SORT_COLUMN_MAP[$orderBy] ?? <safe default>` before reaching `->orderBy()`, and `orderDirection` is validated to `asc`/`desc`. Mitigation complete.
- T-01-06-S2 (mitigate): verified no enum-cast status columns in the 11 touched models, so no string-vs-enum comparisons exist to fix. Mitigation already satisfied; documented for the audit.

## Next Phase Readiness
- All 11 FIX-13 services now have the canonical sort-whitelist contract. Future services should mirror this pattern (the laravel-backend rules already document it).
- `composer lint` passes cleanly, so the phase is ready for the verifier.
- Out-of-scope items logged in `deferred-items.md` for future planning:
  1. Standardize list `status` default semantics (`all` = with-trashed vs. default = `active`) across services/controllers/Vue filters + align feature tests.
  2. Whitelist `filter_by` in `VariantService::getVariants()` (sibling column-injection vector).
  3. Remove or wire up dead `CatalogService::listGroupedByProduct()`.
- No blockers for advancing to the next plan/phase.

## Self-Check: PASSED

- `01-06-SUMMARY.md` — FOUND on disk
- `deferred-items.md` — FOUND on disk
- Task commit `a78aaf9` — FOUND in `git log --oneline --all`
- `private const SORT_COLUMN_MAP` present in 11/11 touched service files
- `match ($orderBy)` in `CatalogService.php` — 0 occurrences (removed)
- `vendor/bin/phpstan analyse` on the 11 services — `[OK] No errors`
- `composer lint` (Pint --dirty + PHPStan whole app) — pass
- Post-commit deletion check — no tracked files deleted by the task commit

---
*Phase: 01-critical-fixes-refactor*
*Completed: 2026-06-22*