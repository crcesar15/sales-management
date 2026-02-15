# Task: Purchase Orders (PO)

## Goal
Create and Manage Purchase Orders (ordering stock from vendors).

## Technical Implementation

### 1. Database Schema
- [ ] **purchases**:
    -   `id`, `reference_no` (PO-0001), `vendor_id` (FK), `store_id` (FK - destination), `user_id` (FK).
    -   `status` (enum: pending, ordered, partial_received, received, cancelled).
    -   `date`, `expected_date`.
    -   `total_amount` (decimal), `tax_amount` (decimal), `discount_amount` (decimal).
    -   `notes`.
- [ ] **purchase_items**:
    -   `purchase_id` (FK), `product_id` (FK), `variant_id` (nullable FK).
    -   `quantity_ordered` (decimal).
    -   `quantity_received` (decimal, default 0).
    -   `unit_cost` (decimal), `tax_amount` (decimal), `subtotal` (decimal).

### 2. Backend Logic
- [ ] **PurchaseController**:
    -   `store()`: Create Header & Items. Status = Pending.
    -   `update()`: Check if status is editable (not received).
- [ ] **Calculations**: Logic to sum `(qty * cost) + tax` for totals.

### 3. Frontend Implementation
- [ ] **PO Form**:
    -   Select Vendor, Store.
    -   **Dynamic Item Table**: Search Product -> Select Variant -> Input Qty & Cost & Tax.
    -   Auto-calculate row/grand totals.

### 4. Verification
-   Create PO for "Acme Corp" ordering 10 T-Shirts (Red).
-   Verify status is Pending.
