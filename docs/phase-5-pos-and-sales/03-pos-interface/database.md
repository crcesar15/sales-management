# Database — POS Interface

## Tables Read (No New Migrations)
| Table | Usage |
|---|---|
| `products` | Search by name |
| `product_variants` | Search by SKU/barcode, price source |
| `product_variant_units` | Available sale units per variant (`type='sale'`) |
| `batches` | FIFO stock deduction on checkout (note: table name is `batches`, not `inventory_batches`) |
| `customers` | Optional customer lookup |
| `settings` | Tax rate (`tax_rate` in group `tax`), store info |
| `cash_registers` | Register info for shift gate |
| `cash_register_shifts` | Verify open shift before checkout |

## Tables Written on Checkout
| Table | Action |
|---|---|
| `sales_orders` | INSERT new order (with `cash_register_shift_id`) |
| `sales_order_items` | INSERT per cart item |
| `sales_order_payments` | INSERT per payment method (split payments) |
| `batches` | UPDATE `remaining_quantity` and `sold_quantity` (FIFO deduction) |

## FIFO Deduction Pattern
```sql
-- Select batches in FIFO order with lock
SELECT * FROM batches
  WHERE product_variant_id = ? AND remaining_quantity > 0 AND status = 'active'
  ORDER BY created_at ASC
  FOR UPDATE;
```
- Deduct from oldest batch first
- If a batch hits 0, continue to next batch
- Entire checkout wrapped in `DB::transaction()`
- After deduction, call `ProductVariant::recalculateStock()` for each affected variant

## Settings Keys Used
| Key | Group | Type |
|---|---|---|
| `tax_rate` | `tax` | decimal (e.g. `0.07` for 7%) |
| `store_name` | `general` | string |

## Key Indexes (Existing — Verify Present)
| Table | Column(s) | Reason |
|---|---|---|
| `product_variants` | `sku` | Barcode/SKU fast lookup |
| `product_variants` | `barcode` | Barcode scan lookup |
| `batches` | `product_variant_id, status, created_at` | FIFO ordering |
| `cash_register_shifts` | `cash_register_id, status` | Find open shift quickly |

## Table Name Clarifications
Several tables are referenced differently between these docs and the actual codebase:

| Doc Reference | Actual Table Name | Actual Model |
|---|---|---|
| `inventory_batches` | `batches` | `App\Models\Batch` |
| `sale_units` | `product_variant_units` (where `type='sale'`) | `App\Models\ProductVariantUnit` |

## Cash Register Shift Integration
The POS must verify an open shift before allowing checkout:

1. On POS page load, call `GET /api/v1/cash-registers/{id}/open-shift` to check for an open shift
2. If no open shift, display "Open Shift" dialog (select register, enter opening balance)
3. If shift exists, display shift info in POS header (register name, cashier, opening balance, time opened)
4. On checkout, `cash_register_shift_id` is stored on the `sales_order` record
5. Cash payments from this order are included in the shift's `expected_closing` calculation

## Held Orders Storage
Held orders use the `sales_orders` table with `status = 'held'`:

- Items are stored in `sales_order_items` (same schema as normal orders)
- No stock deduction occurs on hold
- No payment records are created on hold
- `cash_register_shift_id` is set to the current shift
- On resume, status transitions from `held` to `draft`, cart is populated from items