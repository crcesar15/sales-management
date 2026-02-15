# Task: Vendor Payments

## Goal
Track payments made to vendors against POs.

## Technical Implementation

### 1. Database Schema
- [ ] **payments**:
    -   `id`, `reference_no` (PAY-0001).
    -   `payable_type` (Morph: Purchase), `payable_id`.
    -   `amount` (decimal), `payment_method` (Cash, Bank, Check).
    -   `date`, `notes`, `user_id`.
- [ ] Add `paid_amount` and `payment_status` (Pending, Partial, Paid) to `purchases` table.

### 2. Backend Logic
- [ ] **PaymentController**:
    -   `store(Request $request)`:
        -   Validate `amount` <= `purchase.grand_total` - `purchase.paid_amount`.
        -   Create Payment.
        -   Update Purchase `paid_amount` and `payment_status`.

### 3. Frontend Implementation
- [ ] **Purchase View**:
    -   Show "Payments" tab/section.
    -   "Add Payment" Button -> Modal (Amount, Method, Date).
    -   Show list of past payments.

### 4. Verification
-   PO Total is $1000.
-   Add Payment of $400.
-   Verify Purchase Status is "Partial".
-   Add Payment of $600.
-   Verify Purchase Status is "Paid".
