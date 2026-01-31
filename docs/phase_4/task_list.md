# Phase 4: Detailed Developer Tasks

## 1. Database Schema

### Task 1.1: Customers & Orders
- **Description**: Sales data structures.
- **Steps**:
    1.  `create_customers_table`.
    2.  `create_orders_table`: `invoice_no`, `customer_id`, `store_id`, `total`, `tax`, `discount`, `payment_method`.
    3.  `create_order_items_table`: `order_id`, `product_id`, `quantity`, `price`, `subtotal`.
- **Acceptance Criteria**: Tables exist.

## 2. Backend Implementation

### Task 2.1: Sales Logic
- **Description**: Decrementing stock.
- **Steps**:
    1.  Create Action `ProcessSaleAction`.
    2.  Logic:
        - Validate Stock Availability (`stock >= qty`). Throw exception if insufficient.
        - Create Order/Items.
        - Decrement `stock.quantity`.
- **Acceptance Criteria**: Test case fails if stock is too low.

### Task 2.2: Invoice Generation
- **Description**: PDF output.
- **Steps**:
    1.  Install `barryvdh/laravel-dompdf`.
    2.  Create blade view `resources/views/pdfs/invoice.blade.php`.
    3.  Controller method `OrderController@print($id)`.
- **Acceptance Criteria**: Endpoint downloads a PDF.

## 3. Frontend Implementation (POS)

### Task 3.1: Pinia Store (Cart)
- **Description**: Client-side state for the POS.
- **Steps**:
    1.  `stores/cart.ts`.
    2.  State: `items[]`, `customer`, `settings`.
    3.  Actions: `addToCart(product)`, `removeFromCart(index)`, `clearCart()`.
    4.  Getters: `subtotal`, `tax`, `total`.
- **Acceptance Criteria**: Adding same product twice increments quantity, not row count (unless desired otherwise).

### Task 3.2: POS Layout
- **Description**: Specialized Full-screen UI.
- **Steps**:
    1.  Remove Sidebar/Footer for this route.
    2.  **Product Grid**: Searchable list of products with images. Click to add to cart.
    3.  **Cart Sidebar**: List of added items. Quantity modification (+/-). Delete button.
- **Acceptance Criteria**: Smooth interaction, adding items is instant.

### Task 3.3: Checkout Flow
- **Description**: Payment and Finalization.
- **Steps**:
    1.  "Pay Now" button opens Modal.
    2.  Input "Amount Tendered". Display "Change".
    3.  Select Payment Method (Cash, Card).
    4.  Submit -> Call API.
    5.  On Success -> Clear Cart, Show "Print Receipt" option.
- **Acceptance Criteria**: Complete flow results in a new database Order and reduced Stock.
