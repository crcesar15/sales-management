# Database — Product Variant Units

## New Table

### `product_variant_units`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_variant_id` | BIGINT UNSIGNED | FK → `product_variants.id` CASCADE DELETE |
| `type` | ENUM('sale','purchase') | Discriminator — sale units for POS, purchase units for purchasing |
| `name` | VARCHAR(100) | e.g. "6-Pack", "Crate of 24", "Small Box" — unique per variant per type |
| `conversion_factor` | INT UNSIGNED | Minimum 1; base unit multiplier |
| `price` | DECIMAL(10,2) NULLABLE | Sale price for sale type; NULL for purchase type (vendor cost on catalog) |
| `status` | ENUM('active','inactive') | Default `'active'` |
| `sort_order` | INT UNSIGNED | Display ordering; default 0 |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

## Key Indexes
- `PRIMARY KEY (id)`
- `INDEX (product_variant_id)` — list all units for a variant
- `INDEX (type)` — filter by sale/purchase
- `UNIQUE (product_variant_id, type, name)` — unique name per variant per type (allows "Box" as both sale and purchase unit)

## Migration Snippet
```php
Schema::create('product_variant_units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_variant_id')
          ->constrained('product_variants')
          ->cascadeOnDelete();
    $table->enum('type', ['sale', 'purchase']);
    $table->string('name', 100);
    $table->unsignedInteger('conversion_factor')->default(1);
    $table->decimal('price', 10, 2)->nullable();
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index('product_variant_id');
    $table->index('type');
    $table->unique(['product_variant_id', 'type', 'name']);
});
```

## Relationships
```
product_variant_units ——→ product_variants (BelongsTo)
product_variants       ——→ product_variant_units (HasMany, scoped by type)
```

Eloquent declarations:
```php
// ProductVariant model
public function units(): HasMany {
    return $this->hasMany(ProductVariantUnit::class, 'product_variant_id');
}
public function saleUnits(): HasMany {
    return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
                ->where('type', 'sale');
}
public function activeSaleUnits(): HasMany {
    return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
                ->where('type', 'sale')
                ->where('status', 'active')
                ->orderBy('sort_order');
}
public function purchaseUnits(): HasMany {
    return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
                ->where('type', 'purchase');
}
public function activePurchaseUnits(): HasMany {
    return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
                ->where('type', 'purchase')
                ->where('status', 'active')
                ->orderBy('sort_order');
}

// ProductVariantUnit model
public function variant(): BelongsTo {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
}
```

## Stock Conversion Formulas

**Selling (POS):**
```
stock_deducted = qty_sold × conversion_factor

Example:
  Variant stock = 24 (base units = pieces)
  Customer buys 2 × "6-Pack" (conversion_factor = 6)
  stock_deducted = 2 × 6 = 12
  Remaining stock = 24 - 12 = 12
```

**Purchasing (Phase-4 PO):**
```
stock_added = qty_received × conversion_factor

Example:
  Buy 10 × "Small Box" (conversion_factor = 6) from vendor
  stock_added = 10 × 6 = 60 pieces
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

## Phase-4 Catalog Reference
When the vendor catalog is implemented, purchase units are referenced from the `catalog` table:
```php
// catalog table gains: unit_id FK → product_variant_units.id (where type='purchase')
// Each catalog entry links a vendor to a variant with a specific purchase unit + vendor price
// Multiple catalog rows per vendor-variant (one per purchase unit option)
```

## Base Unit & Measurement Unit
`measurement_unit_id` on `products` defines the **base unit** (atomic stock unit):
- All conversion factors are relative to this base
- Products with a single presentation don't need any variant unit records
- The base unit is always available dynamically (see POS Display above)
- No FK from `product_variant_units` to `measurement_units` — the relationship is implicit through the product

| Product | Base Unit | Variant Unit Examples |
|---|---|---|
| Mineral Water 500ml | Piece (pc) | 6-Pack (×6), Crate (×24) |
| Rice | Kilogram (kg) | 25kg Sack (×25) |
| Notebook | Piece (pc) | *(none — single presentation)* |

## Notable Patterns
- Sale unit `price` is independent of `product_variants.price` — different pack sizes have different per-pack prices
- Purchase units have no `price` — vendor cost is stored on `catalog` table (phase-4)
- `conversion_factor = 1` is allowed (explicit base-unit entry, if desired alongside the dynamic one)
- Deleting a variant cascades and removes all its units automatically
- Name uniqueness is scoped by type — "Box" can exist as both a sale unit and a purchase unit for the same variant
- `sort_order` controls display ordering; same value is allowed (tie-broken by insertion order)
