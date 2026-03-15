# Frontend — Receipts

## Pages & Layouts
| File | Purpose |
|---|---|
| `Pages/Receipts/Show.vue` | Public receipt display page |
| `Layouts/ReceiptLayout.vue` | Minimal layout — no sidebar, no auth nav |

## Components to Create
| Component | Purpose |
|---|---|
| `Receipts/ReceiptHeader.vue` | Store logo, name, address, phone |
| `Receipts/ReceiptItemsTable.vue` | Line items: name, qty, unit, price, total |
| `Receipts/ReceiptTotals.vue` | Subtotal, discount, tax, grand total |
| `Receipts/ReceiptFooter.vue` | Custom footer text + payment method |

## PrimeVue Components Used
| PrimeVue | Usage |
|---|---|
| `Button` | Print button (hidden on print) |
| `Divider` | Section separators |
| `DataTable` | Items list (simple, no pagination) |

## Print Stylesheet Pattern
```css
/* In ReceiptLayout.vue or receipt.css */
@media print {
  .no-print { display: none !important; }
  body { font-size: 12pt; }
  .receipt-container { width: 80mm; margin: 0 auto; }
}
```

```vue
<!-- Print button — hidden when printing -->
<Button class="no-print" label="Print Receipt" @click="window.print()" />
```

## Layout Structure
```
ReceiptLayout (no sidebar)
└── Show.vue
    ├── ReceiptHeader        ← store logo, name, address
    ├── receipt_header text  ← from settings
    ├── Order meta           ← date, order #, cashier, customer
    ├── ReceiptItemsTable    ← line items
    ├── ReceiptTotals        ← subtotal → discount → tax → total
    ├── receipt_footer text  ← from settings
    ├── payment method
    └── Print button (no-print class)
```

## Key Patterns

**Setting Receipt Layout**
```js
// Show.vue
defineOptions({ layout: ReceiptLayout })
```

**Share URL Display**
```vue
<span class="no-print text-sm text-gray-400">
  {{ $page.url }}  <!-- or pass explicit receipt URL from props -->
</span>
```

## Notes
- Receipt page must work without JavaScript (CSS-only print is sufficient)
- Mobile responsive — customers may open via phone
- No PrimeVue DataTable pagination needed — receipts have ≤ 50 items typically
- Logo: render with `<img>` tag using `store.logo_url` prop; hide if null
