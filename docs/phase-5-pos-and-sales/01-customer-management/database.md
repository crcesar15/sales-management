# Database — Customer Management

## Table: `customers`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `first_name` | `string` nullable | |
| `last_name` | `string` nullable | |
| `email` | `string` unique nullable | Nullable-unique index |
| `phone` | `string` nullable | |
| `tax_id` | `string` nullable | VAT / tax registration number |
| `tax_id_name` | `string` nullable | Business name for tax ID |
| `created_at` / `updated_at` | `timestamps` | |

## Migration Notes
- New migration: `create_customers_table`
- Use `$table->string('email')->nullable()->unique()` — Laravel handles nullable unique correctly (multiple NULLs)
- No `softDeletes` column

## Key Indexes
| Index | Column(s) | Reason |
|---|---|---|
| Unique | `email` | Prevent duplicate email registrations |
| Index | `phone` | Fast POS lookup by phone |
| Index | `first_name`, `last_name` | Composite for name search (optional, prefer full-text or LIKE) |

## Relationships
```
Customer hasMany SalesOrder
SalesOrder belongsTo Customer (nullable)
```

## Deletion Guard Pattern
```php
// In CustomerService or CustomerController before delete:
if ($customer->salesOrders()->exists()) {
    throw new \Exception('Cannot delete customer with existing sales orders.');
}
```

## Notable Patterns
- No FK to `users` — customers are external entities
- POS search uses `LIKE %term%` on `first_name`, `last_name`, `email`, `phone` via a scoped query
