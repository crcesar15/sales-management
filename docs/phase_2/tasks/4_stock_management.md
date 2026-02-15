# Task: Stock Management

## Goal
Manage inventory levels across multiple stores, including manual adjustments.

## Technical Implementation

### 1. Database Schema
- [ ] **stores**: `id`, `name`, `address`, `active` (bool).
- [ ] **stocks**:
    -   `store_id` (FK), `product_id` (FK), `variant_id` (nullable FK).
    -   `quantity` (decimal, default 0).
    -   Unique: `[store_id, product_id, variant_id]`.
- [ ] **stock_adjustments**:
    -   `id`, `reference_no` (SA-0001), `store_id`, `user_id`, `reason` (enum: damage, correction, gift), `notes`.
- [ ] **stock_adjustment_items**:
    -   `stock_adjustment_id`, `product_id`, `variant_id`.
    -   `quantity` (decimal, can be negative).

### 2. Backend Logic
- [ ] **StockService**:
    -   `adjust(storeId, productId, variantId, qty, type)`:
        -   `increment` or `decrement` `stocks.quantity`.
        -   Do not allow negative stock if configured in settings.
- [ ] **StockAdjustmentController**:
    -   `store()`:
        -   Validate items.
        -   Create Adjustment Record.
        -   Loop items -> Call `StockService`.
        -   Log Activity.

### 3. Frontend Implementation
- [ ] **Stock Overview**:
    -   Table showing Product Name, Variant Name, Store Name, Current Qty.
- [ ] **Adjustment Page**:
    -   Select Store.
    -   Search Product (Autocomplete). Select Variant if applicable.
    -   Input Qty (use "Type: Add/Subtract" or +/- numbers).
    -   Save.

### 4. Verification
-   Product A has 0 stock.
-   Create Adjustment: Add 10 to Phase 1 Store.
-   Verify Stock table shows 10.
-   Create Adjustment: Subtract 2 (Damage).
-   Verify Stock table shows 8.
