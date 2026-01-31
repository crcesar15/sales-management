# Phase 3: Procurement & Stock In

**Goal**: Allow adding stock through purchase orders from vendors.

## 1. Database Schema
- **vendors**: `id`, `name`, `email`, `phone`, `address`, `tax_number`.
- **purchases**:
    - `id`, `reference_no` (PO-0001), `vendor_id`, `store_id` (destination).
    - `date`, `status` (Pending, Ordered, Received).
    - `total_amount`, `notes`.
    - `user_id` (created by).
- **purchase_items**:
    - `purchase_id`, `product_id`.
    - `quantity`, `unit_cost`, `subtotal`.

## 2. Backend Implementation
### Logic
- **Stock Increment**:
    - When Purchase status changes to `Received`, increment `stocks.quantity` for the specific `product_id` and `store_id`.
    - Use Database Transactions (`DB::transaction`) to ensure data integrity.
- **Average Cost**: (Optional) Update product `cost_price` based on weighted average if needed.

### Controllers
- **VendorController**: Standard CRUD.
- **PurchaseController**:
    - `store()`: Create PO.
    - `update()`: Modify PO.
    - `markAsReceived()`: Trigger stock update.

## 3. Frontend Implementation
### Components
- **ProductSearch**: Autocomplete input to find products by Name/SKU to add to Purchase Order.

### Pages
- **Purchases**:
    - `Create`: Header (Vendor, Store, Date) + Dynamic Items Table.
    - Items Table handles: Product Search, Quantity Input, Cost Input, Row Total Calculation.
    - Validation: Ensure at least one item.

## 4. Deliverables
- [ ] Vendor Database.
- [ ] Purchase Order creation flow.
- [ ] "Receive" action which actually increases the Inventory count in the selected store.
