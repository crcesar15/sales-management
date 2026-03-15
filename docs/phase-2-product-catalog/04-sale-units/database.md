# Database — Sale Units

## New Table

### `product_variant_sale_units`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_variant_id` | BIGINT UNSIGNED | FK → `product_variants.id` CASCADE DELETE |
| `name` | VARCHAR(100) | e.g. "6-Pack", "Crate of 24" — unique per variant |
| `conversion_factor` | INT UNSIGNED | Minimum 1; base unit multiplier |
| `price` | DECIMAL(10,2) | Sale price for this pack size |
| `status` | ENUM('active','inactive') | Default `'active'` |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

## Key Indexes
- `PRIMARY KEY (id)`
- `INDEX (product_variant_id)` — list all sale units for a variant
- `UNIQUE (product_variant_id, name)` — prevent duplicate sale unit names per variant

## Migration Snippet
```php
Schema::create('product_variant_sale_units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_variant_id')
          ->constrained('product_variants')
          ->cascadeOnDelete();
    $table->string('name', 100);
    $table->unsignedInteger('conversion_factor')->default(1);
    $table->decimal('price', 10, 2);
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();

    $table->index('product_variant_id');
    $table->unique(['product_variant_id', 'name']);
});
```

## Relationships
```
product_variant_sale_units ——→ product_variants (BelongsTo)
product_variants           ——→ product_variant_sale_units (HasMany)
```

Eloquent declarations:
```php
// ProductVariant model
public function saleUnits(): HasMany {
    return $this->hasMany(ProductVariantSaleUnit::class, 'product_variant_id');
}
public function activeSaleUnits(): HasMany {
    return $this->hasMany(ProductVariantSaleUnit::class, 'product_variant_id')
                ->where('status', 'active');
}

// ProductVariantSaleUnit model
public function variant(): BelongsTo {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
}
```

## Stock Deduction Formula
```
stock_deducted = qty_sold × conversion_factor

Example:
  Variant stock = 24 (base units = pieces)
  Customer buys 2 × "6-Pack" (conversion_factor = 6)
  stock_deducted = 2 × 6 = 12
  Remaining stock = 24 - 12 = 12
```

## POS Display (derived, not stored)
```php
// Base unit — constructed dynamically, not from this table
$baseUnit = [
    'name'              => $product->measurementUnit?->name ?? 'Unit',
    'abbreviation'      => $product->measurementUnit?->abbreviation ?? 'pc',
    'conversion_factor' => 1,
    'price'             => $variant->price,
];

// All selectable options in POS
$saleOptions = [$baseUnit, ...$variant->activeSaleUnits->toArray()];
```

## Notable Patterns
- Sale unit `price` is independent of `product_variants.price` — different pack sizes have different per-pack prices
- `conversion_factor = 1` is allowed (explicit base-unit sale unit entry, if desired alongside the dynamic one)
- Deleting a variant cascades and removes all its sale units automatically
