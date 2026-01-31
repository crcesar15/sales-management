# Phase 3: Detailed Developer Tasks

## 1. Database Schema

### Task 1.1: Vendors Table
- **Description**: Suppliers info.
- **Steps**:
    1.  Migration `create_vendors_table`: `name`, `email`, `phone`, `tax_number`, `address`.
- **Acceptance Criteria**: Table exists.

### Task 1.2: Purchases Tables
- **Description**: Headers and Lines for Purchase Orders.
- **Steps**:
    1.  `create_purchases_table`: `reference_no`, `vendor_id`, `store_id` (receiving store), `status` (pending/received), `date`, `total_amount`.
    2.  `create_purchase_items_table`: `purchase_id`, `product_id`, `quantity`, `unit_cost`, `subtotal`.
- **Acceptance Criteria**: Master-Detail schema established.

## 2. Backend Implementation

### Task 2.1: Purchase Logic & Transactions
- **Description**: The core logic for receiving stock.
- **Steps**:
    1.  Create Action `ReceivePurchaseAction`.
    2.  Logic:
        - Wrap in `DB::transaction`.
        - Update `purchase.status` to 'received'.
        - Iterate `purchase_items`.
        - For each item, `Increment` `stock.quantity` where `store_id` = `purchase.store_id`.
- **Acceptance Criteria**: Unit test proving stock increases after execute.

### Task 2.2: Purchase Controller
- **Description**: CRUD for POs.
- **Steps**:
    1.  `store()`: Save header and items. Validation is key (must have items).
    2.  `show()`: Return details for view.
    3.  `update()`: Allow editing only if status is 'pending'.
- **Acceptance Criteria**: API safeguards against editing already received orders.

## 3. Frontend Implementation

### Task 3.1: Vendor Management
- **Description**: Basic CRUD.
- **Steps**:
    1.  `Vendors/Index.vue` & `Create.vue`.
- **Acceptance Criteria**: Can create a Vendor.

### Task 3.2: Purchase Form (Dynamic)
- **Description**: Complex form with line items.
- **Steps**:
    1.  `Purchases/Create.vue`.
    2.  **Header**: Select Vendor (Dropdown), Select Store (Dropdown), Date.
    3.  **Items Table**:
        - Button "Add Item".
        - Row inputs: Product (Search), Quantity, Cost.
        - Computeds: Row Subtotal, Grand Total.
- **Acceptance Criteria**: Can save a multi-line purchase order.

### Task 3.3: Receiving Interface
- **Description**: Button to finalize the order.
- **Steps**:
    1.  In `Purchases/Show.vue` or `Edit.vue`.
    2.  Button "Mark as Received".
    3.  Confirmation Modal.
    4.  Calls API endpoint.
- **Acceptance Criteria**: User receives visual feedback (Success toast) and status updates to 'Received'.
