# Database — POS Interface

## Tables Read (No New Migrations)
| Table | Usage |
|---|---|
| `products` | Search by name |
| `product_variants` | Search by SKU/barcode, price source |
| `sale_units` | Available units per variant (base + bulk) |
| `inventory_batches` | FIFO stock deduction on checkout |
| `customers` | Optional customer lookup |
| `settings` | Tax rate (`tax_rate`), store info |

## Tables Written on Checkout
| Table | Action |
|---|---|
| `sales_orders` | INSERT new order |
| `sales_order_items` | INSERT per cart item |
| `inventory_batches` | UPDATE `quantity_remaining` (FIFO deduction) |

## FIFO Deduction Pattern
```sql
-- Select batches in FIFO order with lock
SELECT * FROM inventory_batches
  WHERE product_variant_id = ? AND quantity_remaining > 0
  ORDER BY created_at ASC
  FOR UPDATE;
```
- Deduct from oldest batch first
- If a batch hits 0, continue to next batch
- Entire checkout wrapped in `DB::transaction()`

## Settings Keys Used
| Key | Group | Type |
|---|---|---|
| `tax_rate` | `pos` | decimal (e.g. `0.07` for 7%) |
| `store_name` | `general` | string |

## Key Indexes (Existing — Verify Present)
| Table | Column | Reason |
|---|---|---|
| `product_variants` | `sku` | Barcode/SKU fast lookup |
| `product_variants` | `barcode` | Barcode scan lookup |
| `inventory_batches` | `product_variant_id, created_at` | FIFO ordering |
