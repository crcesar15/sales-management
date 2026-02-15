# Task: Stock Management

## Goal
Manage inventory levels across multiple stores, including manual adjustments.

## Technical Implementation

### 1. Database Schema
- [ ] **stores**: `id`, `name`, `address`, `active`.
- [ ] **stocks**:
    -   `store_id`, `product_id`, `variant_id` (nullable).
    -   `quantity` (decimal).
- [ ] **stock_adjustments**:
    -   `id`, `store_id`, `user_id`, `reason` (Damage, Correction, Gift).
    -   `date`.
- [ ] **stock_adjustment_items**:
    -   `adjustment_id`, `product_id`, `variant_id`.
    -   `quantity` (positive for add, negative for remove).

### 2. Backend Logic
- [ ] **Auto-Init**:
    -   When creating a Product/Variant, create `0` stock entries for all Active stores.
- [ ] **AdjustmentController**:
    -   `store()`: Transactional. Update `stocks` table based on adjustment items. Log activity.

### 3. Frontend Implementation
- [ ] **Stock List**:
    -   Filter by Store. Show current Qty.
- [ ] **Adjustment Form**:
    -   Select Store.
    -   Add Items (Product Search).
    -   Enter Quantity (+/-).
