# Task: Reception Orders (GRN) & Stock In

## Goal
Receive stock against a Purchase Order. Support partial receiving.

## Technical Implementation

### 1. Database Schema
- [ ] **reception_orders**:
    -   `id`, `reference_no` (GRN-0001), `purchase_id` (FK), `user_id` (FK).
    -   `date`, `notes`.
- [ ] **reception_items**:
    -   `reception_id`, `product_id`, `variant_id`.
    -   `quantity_received` (decimal).

### 2. Backend Logic
- [ ] **PurchaseService::receive(purchaseId, items[])**:
    -   Start Transaction.
    -   Create `ReceptionOrder`.
    -   Loop items:
        -   Validate `qty_received` <= `qty_ordered` - `qty_already_received`.
        -   Create `ReceptionItem`.
        -   Update `purchase_items.quantity_received`.
        -   **Increment Stock**: Call `StockService` to add stock to `purchase.store_id`.
    -   Update `purchase.status`:
        -   If `total_received` >= `total_ordered` -> `Received`.
        -   Else -> `Partial Received`.

### 3. Frontend Implementation
- [ ] **Receive Modal/Page**:
    -   Show Pending Items from PO.
    -   Input "Qty to Receive" (default to remaining qty).
    -   Submit.

### 4. Verification
-   Open PO (10 items ordered).
-   Receive 5 items.
-   Verify PO status is "Partial".
-   Verify Stock increased by 5.
-   Receive remaining 5.
-   Verify PO status is "Received".
