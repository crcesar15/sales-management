# Phase 1: Critical Fixes & Refactor - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-21
**Phase:** 1-Critical Fixes & Refactor
**Areas discussed:** FIFO consolidation approach (FIX-11)

---

## FIFO consolidation approach (FIX-11)

Four questions were asked in sequence about the FIFO consolidation. The alternatives considered for each:

### Q1 — Consolidation structure

| Option | Description | Selected |
|--------|-------------|----------|
| Delegate to FifoStockDeductionService | `BatchService::deductFIFO*` delegates to `FifoStockDeductionService`; add a mode/sibling method for sale-vs-transfer counter increment. One service owns FIFO; `BatchService` keeps the public method for backward compat. | ✓ |
| Extract shared FIFO helper | Trait or static class both services call. Keeps both services thin; helper reusable by future stock-deducting code (e.g. PosService in Phase 4). More moving parts, symmetric ownership. | |
| Minimal — fix behaviors only | Delete the unused `deductFIFO()` sale variant (no callers), keep `deductFIFOForTransfer()` as-is, standardize exception + close-at-zero independently. Less churn; logic stays duplicated. | |

**User's choice:** Delegate to FifoStockDeductionService
**Notes:** None — user selected the recommended option (matches CONCERNS.md fix approach: "Have `BatchService::deductFIFO*` delegate to `FifoStockDeductionService`").

---

### Q2 — Public method surface

| Option | Description | Selected |
|--------|-------------|----------|
| Two public methods (order/transfer) | `deductForOrder(SalesOrder)` (existing, sale, increments `sold_quantity`) + `deductForTransfer(int $variantId, int $storeId, int $quantity)` (new, transfer, increments `transferred_quantity`, opens own txn). `BatchService::deductFIFOForTransfer()` becomes a thin delegate. `deductFIFO()` deleted. Each auto-closes at zero. | ✓ |
| Single method + options array | One generic `deductFIFO(int $variantId, int $storeId, int $quantity, array $options = [])` with options for counter ('sold'\|'transferred'), txn ownership, close-at-zero. Flexible but param-heavy. | |
| Model-based (StockTransfer param) | Keep `deductForOrder(SalesOrder)` for sales; add `deductForStockTransfer(StockTransfer)` that iterates `$transfer->items` internally — symmetric design, but changes `StockTransferService` to pass a model instead of per-variant IDs. | |

**User's choice:** Two public methods (order/transfer)
**Notes:** None.

---

### Q3 — Transaction ownership

| Option | Description | Selected |
|--------|-------------|----------|
| Mixed (order=caller, transfer=self) | `deductForOrder()` keeps "caller owns the txn" contract (`SalesOrderService` wraps it). `deductForTransfer()` opens its own `DB::transaction()` (matching old `BatchService` behavior, since `StockTransferService` may not wrap each per-variant call). Document both in PHPDoc. | ✓ |
| Both caller-owned | Both assume caller owns the txn; update `StockTransferService` to wrap its per-variant loop in a single `DB::transaction`. More consistent contract, but touches `StockTransferService`'s txn structure. | |
| Both self-contained | Both open their own txn; update `SalesOrderService` to not wrap (or rely on nested savepoints). Fully self-contained but muddies the intent of `SalesOrderService::create`'s wrapper. | |

**User's choice:** Mixed (order=caller, transfer=self)
**Notes:** None — least churn, preserves existing caller contracts on both sides.

---

### Q4 — Batch auto-close at zero

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-close in both methods | Both `deductForOrder()` and `deductForTransfer()` auto-close the batch when `remaining_quantity` hits 0 (the `BatchService` behavior). Makes ROADMAP success criterion #3 true for BOTH the sale path and the transfer path in one consolidation; fixes the `deductForOrder()` gap. | ✓ |
| Separate close step | Deduction only updates `remaining_quantity` + counter; `BatchService::closeBatch()` (or a scheduled job) closes batches. Single-responsibility, but requires a separate call after every deduction and breaks the transfer path's current behavior. | |

**User's choice:** Auto-close in both methods
**Notes:** None — matches ROADMAP success criterion #3 exactly.

---

## the agent's Discretion

The user selected only the FIFO consolidation area for discussion. All other FIX-01..FIX-22 items (mass-assignment, missing authorize, broken resources, status comparison, tax frontend, CSRF header, raw model return, ApiCollection meta, TRANSITION_MAP, SORT_COLUMN_MAP, narrow catch, AuthServiceProvider, claimed-quantities extraction, dead validated calls, Setting cache, N+1 fixes, recalculateStock re-query, log rotation) were **not** selected — the user deferred them to the researcher/planner's discretion, guided by `.planning/codebase/CONCERNS.md` (which documents the fix approach for each) and `.claude/rules/*.md` (the convention target). CONTEXT.md's "the agent's Discretion" section captures the recommended approach for each so downstream agents can act without re-asking.

## Deferred Ideas

None — discussion stayed within phase scope. No scope-creep ideas were raised.