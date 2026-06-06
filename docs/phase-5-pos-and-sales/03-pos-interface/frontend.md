# Frontend — POS Interface

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `Pos/Index` | `resources/js/Pages/Pos/Index.vue` | Main POS screen (full-screen layout) |

## Components to Create
| Component | Purpose |
|---|---|
| `Pos/ProductSearch.vue` | Debounced search + barcode scanner input |
| `Pos/CartTable.vue` | Cart item list with quantity controls, remove button |
| `Pos/CartSummary.vue` | Subtotal, discount input, tax, total display |
| `Pos/CustomerSelect.vue` | AutoComplete for customer or walk-in |
| `Pos/PaymentPanel.vue` | Payment method rows for split payments, checkout button |
| `Pos/ShiftInfo.vue` | Current shift status, register name, cashier, opening balance, close button |
| `Pos/HeldOrdersDrawer.vue` | Slide-out panel listing held orders for current shift |

## Layout
POS uses a custom full-screen layout with no sidebar or standard navigation bar:
```
┌────────────────────────────────────────────────────────────────────┐
│ [Store Name]  [Register: REG-01]  [Shift: Open • $500]  [Close]   │ ← ShiftInfo bar
├──────────────────────────────┬─────────────────────────────────────┤
│                              │                                     │
│  ProductSearch               │  CartSummary                        │
│  ┌────────────────────────┐  │  ┌─────────────────────────────────┐ │
│  │ 🔍 Search / Barcode   │  │  │ Subtotal:        $150.00       │ │
│  └────────────────────────┘  │  │ Discount:         -$15.00      │ │
│                              │  │ Tax (7%):          $9.45       │ │
│  CartTable                   │  │ ──────────────────────────────  │ │
│  ┌────────────────────────┐  │  │ Total:           $144.45       │ │
│  │ Product  Qty  Price    │  │  └─────────────────────────────────┘ │
│  │ Apple    3x   $25.00   │  │                                     │
│  │ Banana   2x   $12.50   │  │  CustomerSelect                     │
│  └────────────────────────┘  │  ┌─────────────────────────────────┐ │
│                              │  │ 👤 Walk-in / Search customer...  │ │
│                              │  └─────────────────────────────────┘ │
│                              │                                     │
│  [⏸ Hold] [🗑 Clear Cart]   │  PaymentPanel                       │
│                              │  ┌─────────────────────────────────┐ │
│                              │  │ 💵 Cash     $94.45              │ │
│                              │  │ 💳 Card     $50.00    [✕]      │ │
│                              │  │ [+ Add Payment]                │ │
│                              │  │ ─────────────────────           │ │
│                              │  │ Total: $144.45  Payments: $144  │ │
│                              │  │ [✓ Checkout]                    │ │
│                              │  └─────────────────────────────────┘ │
│                              │                                     │
│                              │  [📋 Resume Held Order]              │
└──────────────────────────────┴─────────────────────────────────────┘
```

**Tablet (768px+)**: Stack vertically — search and cart on top, summary and payment below.

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `AutoComplete` | Product search, customer search |
| `DataTable` | Cart items display |
| `InputNumber` | Quantity, discount amount, payment amounts |
| `Select` / `Dropdown` | Sale unit selector, payment method, discount type |
| `Button` | Add to cart, checkout, hold, clear cart |
| `InputGroup` | Discount input (flat/percentage toggle) |
| `Tag` | Product status, stock availability |
| `Toast` | Success/error notifications |
| `Dialog` | Shift open dialog |
| `Drawer` | Held orders slide-out panel |
| `Badge` | Cart item count |

## Pinia Store
File: `resources/js/stores/posCart.ts`

```typescript
// State
items: CartItem[]
  // { product_variant_id, sale_unit_id, name, sku, barcode,
  //   quantity, unit_price, conversion_factor, line_total,
  //   stock_available (from batches) }
customerId: number | null
customerName: string
discountType: 'flat' | 'percentage'
discountValue: number
cashRegisterShiftId: number | null

// Getters
subtotal: number             // sum of line_total
discountAmount: number       // computed from discountType and discountValue
taxableAmount: number        // max(0, subtotal - discountAmount)
taxAmount: number            // taxableAmount * taxRate (from settings prop)
total: number                // taxableAmount + taxAmount

// Actions
addItem(variant, saleUnit, quantity, price)   // Add or increment quantity
updateQuantity(index, quantity)                 // Change item quantity
removeItem(index)                              // Remove item from cart
setCustomer(customerId, customerName)          // Set selected customer
clearCustomer()                                // Reset to walk-in
setDiscount(type, value)                      // Set discount type and value
setShift(shiftId)                              // Set current shift
clearCart()                                    // Reset all state
populateFromHeldOrder(order)                  // Load items from a held order into cart
```

## Shift Gate Flow
1. POS page loads → fetch registers for user's store and check for open shift
2. If no open shift → show `Dialog` with "Open Shift" form (register selector, opening balance)
3. On shift open → store `cashRegisterShiftId` in Pinia, enable POS functionality
4. Display shift info in header bar: register name, cashier name, opening balance, time opened
5. "Close Shift" button in header (requires `shift.close` permission, only for own shift)

## Barcode Scanner Pattern (in `ProductSearch.vue`)
```typescript
const barcodeBuffer = ref('')
let bufferTimeout: ReturnType<typeof setTimeout> | null = null

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && barcodeBuffer.value.length > 2) {
    searchByBarcode(barcodeBuffer.value)
    barcodeBuffer.value = ''
    if (bufferTimeout) clearTimeout(bufferTimeout)
  } else {
    barcodeBuffer.value += e.key
    if (bufferTimeout) clearTimeout(bufferTimeout)
    bufferTimeout = setTimeout(() => { barcodeBuffer.value = '' }, 500)
  }
}
```
- Buffer rapid keystrokes, auto-submit on Enter
- If buffer length > 2 on Enter, search by barcode (exact match first, then LIKE fallback)
- 500ms timeout to clear buffer (distinguishes scanner from manual typing)

## Hold/Resume Flow
1. Cashier clicks "Hold" button → `POST /api/v1/pos/hold` with current cart state
2. Backend creates `sales_order` with `status='held'`, items stored, no stock deducted
3. On success → Pinia cart is cleared, toast "Order held"
4. Cashier clicks "Resume" → `HeldOrdersDrawer` slides out showing held orders for current shift
5. Selecting a held order → `POST /api/v1/pos/resume/{id}` → status transitions from `held` to `draft`
6. Cart is populated from the resumed order's items via `populateFromHeldOrder()`
7. Cashier completes checkout normally → `POST /api/v1/pos/checkout` → status transitions to `paid`, FIFO deduction fires

## Split Payment Flow (in `PaymentPanel.vue`)
- Multiple payment rows, each with:
  - `payment_method` (Select: cash, credit_card, qr, transfer)
  - `amount` (InputNumber, mode currency)
  - `reference` (InputText, optional — for card last 4 digits, transfer ref number)
  - "Remove" button (must keep at least one payment row)
- "Add Payment" button adds a new row
- Real-time validation: display the difference between payment sum and total
  - Green when `sum === total`
  - Red when `sum !== total`
- On checkout, all payment rows are sent in the `payments` array
- Default: single "cash" payment row with amount pre-filled to total

## Key Patterns

**Debounced Product Search**
```typescript
const searchQuery = ref('')
watchDebounced(searchQuery, (val) => {
  axios.get(route('api.v1.pos.products.search'), { params: { q: val } })
    .then(({ data }) => results.value = data)
}, { debounce: 300 })
```

**Cart Totals (Reactive via Pinia Getters)**
All totals (subtotal, discount, tax, total) are computed in Pinia getters, ensuring reactivity when items, discount type, or discount value changes.

**Unit Price Lock**
When an item is added to cart, the `unit_price` is locked to the selected `sale_unit` price. The user cannot edit the price in the POS.

**Checkout via API**
```typescript
await axios.post(route('api.v1.pos.checkout'), {
  customer_id: posCart.customerId,
  cash_register_shift_id: posCart.cashRegisterShiftId,
  discount_type: posCart.discountType,
  discount_value: posCart.discountValue,
  notes: notes.value,
  items: posCart.items.map(item => ({
    product_variant_id: item.product_variant_id,
    sale_unit_id: item.sale_unit_id,
    quantity: item.quantity,
    unit_price: item.unit_price,
  })),
  payments: payments.value.map(p => ({
    payment_method: p.payment_method,
    amount: p.amount,
    reference: p.reference,
  })),
})
```

## Notes
- POS page uses `defineOptions({ layout: PosLayout })` — a minimal layout with just a top bar (no sidebar)
- Cart state is ephemeral (Pinia) — not persisted to DB until checkout or hold
- On successful checkout, clear cart and show receipt token
- On successful hold, clear cart and show toast notification
- The `taxRate` is passed as a prop from the server (from `Setting::get('tax_rate')`)
- `inventory_batches` in code is actually `batches`; `sale_units` is `product_variant_units` with `type='sale'`