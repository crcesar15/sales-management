# Database — Product Variants & Options

## Tables

### `product_options`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |
| `name` | VARCHAR(150) | e.g. "Color", "Size" |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `product_option_values`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_option_id` | BIGINT UNSIGNED | FK → `product_options.id` CASCADE DELETE |
| `value` | VARCHAR(50) | e.g. "Red", "Large" |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

> Deletion blocked if any variant uses the value (pivot row exists in `product_variant_option_values`).

### `product_variant_option_values` (pivot)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Full Eloquent Model, not a pure pivot |
| `product_variant_id` | BIGINT UNSIGNED | FK → `product_variants.id` CASCADE DELETE |
| `product_option_value_id` | BIGINT UNSIGNED | FK → `product_option_values.id` CASCADE DELETE |

> No timestamps. Access via `BelongsToMany` on `ProductVariant::values()`.
> Despite having an `id`, treat this as a relationship table — never update rows directly.

### `product_variants`
> Owned by Task 02. See [Task 02 database.md](../02-product-management/database.md) for full schema.
>
> This task adds multi-variant management: option values via the pivot above, image associations via `media_product_variant` (also in Task 02).

### `media_product_variant` (pivot)
> Owned by Task 02. See [Task 02 database.md](../02-product-management/database.md) for full schema.
>
> This task manages the logical link: sync media IDs to variants, enforce inheritance rule (no rows = inherit all product images).

## Key Indexes
- `product_options`: `INDEX (product_id)`
- `product_option_values`: `INDEX (product_option_id)`
- `product_variant_option_values`: `INDEX (product_variant_id)`, `INDEX (product_option_value_id)`

## Relationships
```
product_options         ——→  products               (BelongsTo)
product_options         ——→  product_option_values  (HasMany)
product_variants       ←→   product_option_values   (BelongsToMany via product_variant_option_values)
product_variants       ←→   media                   (BelongsToMany via media_product_variant)
```

## Auto-Generate Logic

Computes the Cartesian product of all option value sets to create every combination as a variant.

**Blocked** if the product has variants beyond the default variant with zero option values.

**"Keep & edit" flow**: when the product has only the default variant, the default variant is preserved. Its `product_variant_option_values` rows are populated with the user-assigned option values. The remaining combinations are created as new variants.

**Rules:**
- Each new variant gets `price = 0.00`, `stock = 0`, `status = 'active'`, `identifier = null`
- Each variant must have exactly one value per option defined on the product (enforced at application level)
- Duplicate option value combinations per product are prevented

## Notable Patterns
- **Duplicate combination check**: before creating a variant, verify no existing variant on the same product has the exact same set of `product_option_value_id`s
- **Option value deletion guard**: check `ProductVariantOptionValue::where('product_option_value_id', $id)->exists()` before allowing deletion
- **Variant deletion**: hard delete (cascade removes pivot rows). Use `status = 'archived'` to hide instead of soft delete
- **Image sync**: `$variant->images()->sync($mediaIds)` — only manages the `media_product_variant` pivot, never moves or copies files
