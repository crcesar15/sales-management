# Database — Categories, Brands & Measurement Units

## Tables

### `categories`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(50) | Unique constraint |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `brands`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(50) | Indexed for search |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `measurement_units`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(100) | e.g. "Kilogram" |
| `abbreviation` | VARCHAR(10) | e.g. "kg" |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `category_product` (pivot)
| Column | Type | Notes |
|---|---|---|
| `category_id` | BIGINT UNSIGNED | FK → `categories.id` CASCADE DELETE |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |

> Pure join table — no `id`, no timestamps. Composite PK on `(category_id, product_id)`.

## Key Indexes
- `categories`: `UNIQUE (name)`
- `brands`: `INDEX (name)`, `INDEX (deleted_at)`
- `measurement_units`: `INDEX (name)`, `INDEX (abbreviation)`
- `category_product`: `PRIMARY (category_id, product_id)`, `INDEX (product_id)`

## Relationships
```
categories   ←——many-to-many——→  products  (via category_product)
brands        ——one-to-many——→   products  (brand_id nullable)
measurement_units ——one-to-many→ products  (measurement_unit_id nullable)
```

Key Eloquent declarations:
```php
// Category model
public function products(): BelongsToMany {
    return $this->belongsToMany(Product::class, 'category_product');
}

// Product model — use withTrashed() to avoid null on deleted brand/unit
public function brand(): BelongsTo {
    return $this->belongsTo(Brand::class)->withTrashed();
}
```

## Notable Patterns
- **Soft-delete guard**: before deleting, check `$entity->products()->whereNull('deleted_at')->exists()`
- **Restore**: `Category::withTrashed()->findOrFail($id)->restore()` — route model binding excludes trashed by default
- **Sync categories**: `$product->categories()->sync($ids)` replaces all; use `attach()` to append
- **Pagination with search**:
```php
Category::query()
    ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
    ->orderBy('name')->paginate(15);
```

## Migration Bug — `measurement_units` down()
```php
// BUGGY (in existing migration)
Schema::dropIfExists('measure_units');   // wrong table name

// CORRECT
Schema::dropIfExists('measurement_units');
```
Impact: rollback silently skips dropping the table; `migrate:fresh` may fail on FK constraints.
