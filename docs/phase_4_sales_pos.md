# Phase 4: Sales, POS & CRM

**Goal**: Sell products, decrement stock, and manage customers.

## 1. Database Schema
- **customers**: `id`, `name`, `email`, `phone`, `address`, `city`. (Include 'Walk-in Customer' as default).
- **orders**:
    - `id`, `invoice_no` (INV-0001), `customer_id`, `user_id` (cashier), `store_id`.
    - `total_amount`, `tax_amount`, `discount_amount`, `payable_amount`.
    - `payment_status` (Paid, Partial, Unpaid).
    - `payment_method` (Cash, Card, Transfer).
    - `created_at` (Sale Date).
- **order_items**:
    - `order_id`, `product_id`.
    - `quantity`, `unit_price`, `subtotal`.

## 2. Backend Implementation
### Logic
- **Stock Decrement**:
    - When Order is finalized, decrement `stocks.quantity`.
    - Check for **Insufficient Stock** before saving.
- **Invoice Generation**: Use `barryvdh/laravel-dompdf` to generate PDF receipt.

### Controllers
- **PosController**: Specialized endpoint for the POS interface to handle quick transactions.
- **OrderController**: For viewing history and details.

## 3. Frontend Implementation (POS Focused)
### State Management (Pinia)
- **CartStore**: Manage the current sale session.
    - `addItem(product)`, `removeItem`, `updateQty`.
    - `customer`, `discount`, `totals` (computed).

### POS Layout (Full Screen)
- **Left Panel**: Product Grid/List with Categories filter. Search bar (Barcode compatible).
- **Right Panel**: Cart summary. Customer selector. Pay Button.
- **Payment Modal**: Input amount tendered, calculate change, select payment method.

### CRM
- **Customers**: History of their purchases.

## 4. Deliverables
- [ ] Functional POS Interface.
- [ ] Cart calculation (Tax/Totals) works correctly.
- [ ] Checkout flow reduces stock inventory.
- [ ] Receipt/Invoice printing.
