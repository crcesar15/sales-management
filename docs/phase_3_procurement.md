# Phase 3: Procurement & Stock In

**Goal**: Allow adding stock through purchase orders from vendors, with partial receiving and payment tracking.

## 1. Database Schema
### Vendors
- **vendors**: `id`, `name`, `email`, `phone`, `tax_number`, `contact_person`.

### Purchasing
- **purchases**:
    - `id`, `reference_no` (PO-0001), `vendor_id`, `store_id`, `user_id`.
    - `status` (pending, ordered, partial_received, received, cancelled).
    - `total_amount`, `tax_amount`, `paid_amount`, `payment_status`.
- **purchase_items**:
    - `purchase_id`, `product_id`, `variant_id`.
    - `quantity_ordered`, `quantity_received`.
    - `unit_cost`, `tax_amount`, `subtotal`.

### Receiving (GRN)
- **reception_orders**: `id`, `reference_no` (GRN-0001), `purchase_id`.
- **reception_items**: `reception_id`, `product_id`, `variant_id`, `quantity_received`.

### Payments
- **payments**:
    - `payable_type` (Purchase), `payable_id`.
    - `amount`, `method`, `date`, `reference_no`.

## 2. Backend Implementation
- **Logic**:
    - **Stock Increment**: Only happens when a Reception Order is created.
    - **Status Workflow**: PO Status updates automatically based on `qty_received` vs `qty_ordered`.
    - **Payment Validation**: Cannot pay more than the PO total.

## 3. Frontend Implementation
- **Purchase Form**: Dynamic items table with Variant support.
- **Reception Modal**: "Receive Items" UI to input quantities arriving today.
- **Payment Modal**: Record outgoing payments.

## 4. Deliverables
- [ ] Vendor CRUD.
- [ ] Purchase Order Management (Create/Edit/Print).
- [ ] Reception Flow (Partial/Full).
- [ ] Vendor Payment Tracking.
