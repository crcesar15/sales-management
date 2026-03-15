# Database — Product Variants & Options

## Tables

### `product_options`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |
| `name` | VARCHAR(100) | e.g. "Color", "Size" |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `product_option_values`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_option_id` | BIGINT UNSIGNED | FK → `product_options.id` CASCADE DELETE |
| `value` | VARCHAR(100) | e.g. "Red", "Large" |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `product_variants`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |
| `identifier` | VARCHAR(100) NULL | SKU / barcode — partial unique index |
| `price` | DECIMAL(10,2) | Default `0.00` |
| `stock` | INT UNSIGNED | Default `0` — live inventory count |
| `status` | ENUM('active','inactive','archived') | Default `'active'` |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `product_variant_option_values` (pivot)
| Column | Type | Notes |
|---|---|---|
| `product_variant_id` | BIGINT UNSIGNED | FK → `product_variants.id` CASCADE DELETE |
| `product_option_value_id` | BIGINT UNSIGNED | FK → `product_option_values.id` CASCADE DELETE |

> No `id`, no timestamps. Composite PK `(product_variant_id, product_option_value_id)`.

## Key Indexes
- `product_options`: `INDEX (product_id)`
- `product_option_values`: `INDEX (product_option_id)`
- `product_variants`: `INDEX (product_id)`, `INDEX (status)`
- `product_variants.identifier`: partial unique index — `UNIQUE WHERE identifier IS NOT NULL`
  ```sql
  CREATE UNIQUE INDEX product_variants_identifier_unique ON product_variants (identifier)
  WHERE identifier IS NOT NULL;
  ```
  > In Laravel migration, use a raw statement or DB::statement() — Blueprint doesn't support partial indexes natively.

## Relationships
```
product_options         ——→  product_option_values  (HasMany)
product_options         ——→  products               (BelongsTo)
product_variants        ——→  products               (BelongsTo)
product_variants       ←→   product_option_values   (BelongsToMany via product_variant_option_values)
```

Key Eloquent declarations:
```php
// ProductVariant model
public function optionValues(): BelongsToMany {
    return $this->belongsToMany(ProductOptionValue::class, 'product_variant_option_values',
        'product_variant_id', 'product_option_value_id')->withoutTimestamps();
}

// Product model
public function options(): HasMany {
    return $this->hasMany(ProductOption::class);
}
public function variants(): HasMany {
    return $this->hasMany(ProductVariant::class);
}
```

## Cartesian Product Logic (Auto-generate)
```php
// Given: [['Red','Blue'], ['S','M','L']]
// Output combinations: [['Red','S'],['Red','M'],['Red','L'],['Blue','S'],...]
function cartesian(array $sets): array {
    return array_reduce($sets, fn($carry, $set) =>
        array_merge(...array_map(fn($c) => array_map(fn($s) => array_merge($c, [$s]), $set), $carry)),
    [[]]
    );
}
```
