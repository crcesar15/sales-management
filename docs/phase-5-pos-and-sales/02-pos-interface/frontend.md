# Frontend — POS Interface

## Pages & Components

| File | Purpose |
|---|---|
| `Pages/Pos/Index.vue` | Main POS screen (full-screen layout) |
| `Components/Pos/ProductSearch.vue` | Debounced search + barcode scan input |
| `Components/Pos/CartTable.vue` | Cart item list with qty controls |
| `Components/Pos/CartSummary.vue` | Subtotal, discount, tax, total display |
| `Components/Pos/CustomerSelect.vue` | AutoComplete for customer or walk-in |
| `Components/Pos/PaymentPanel.vue` | Payment method selector + checkout button |
| `stores/posCart.js` | Pinia store — cart state |

## PrimeVue Components Used
| PrimeVue | Usage |
|---|---|
| `AutoComplete` | Product search + customer select |
| `InputNumber` | Quantity input per cart item |
| `Select` (Dropdown) | Sale unit selector per item, payment method |
| `DataTable` | Cart items list |
| `Button` | Add, remove, checkout |
| `InputGroup` | Discount type toggle + amount |
| `Badge` | Cart item count |
| `Toast` | Checkout success / error |

## Pinia Cart Store (`stores/posCart.js`)
```js
// State
{ items: [], customerId: null, discountType: 'flat', discountValue: 0 }

// Getters
subtotal, discountAmount, taxAmount, total

// Actions
addItem(variant, saleUnit, qty, price)
updateQty(index, qty)
removeItem(index)
clearCart()
```

## Barcode Scanner Pattern
```js
// ProductSearch.vue — listen for rapid keystrokes ending in Enter
const barcodeBuffer = ref('')
onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})
function handleKeydown(e) {
  if (e.key === 'Enter' && barcodeBuffer.value.length > 2) {
    searchByBarcode(barcodeBuffer.value)
    barcodeBuffer.value = ''
  } else {
    barcodeBuffer.value += e.key
    // Reset buffer after 500ms inactivity
  }
}
```

## Layout Notes
- POS uses a **custom layout** (no sidebar/navbar) — set `layout: false` or use `PosLayout.vue`
- Left panel: product search + cart
- Right panel: summary + customer + payment
- Tablet: stack panels vertically

## Key Patterns
- Cart totals computed in Pinia getters (reactive, no manual recalc)
- Unit price locked to selected `sale_unit` price on add; user cannot edit price
- On checkout success: clear cart, redirect to receipt page via `router.visit()`
