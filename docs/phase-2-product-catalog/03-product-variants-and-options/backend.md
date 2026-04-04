# Backend — Product Variants & Options

> **Inertia-only module.** No API layer. All controllers redirect back after mutations. Variant management UI lives on the product show/edit page — actions are Inertia form submissions or AJAX calls from that page.

## Current State

| Component | Status |
|-----------|--------|
| `ProductOption` model | Exists — complete |
| `ProductOptionValue` model | Exists — complete |
| `ProductVariant` model | Exists — has `values()` relationship |
| Migrations | All tables exist |
| `ProductVariantService` | Does not exist yet |
| Variant/options controllers | Do not exist yet |

## Files to Create

```
app/
├── Http/Controllers/Products/
│   ├── ProductOptionController.php
│   ├── OptionValueController.php
│   └── ProductVariantController.php
├── Http/Requests/Products/
│   ├── StoreProductOptionRequest.php
│   ├── StoreOptionValueRequest.php
│   ├── GenerateVariantsRequest.php
│   ├── StoreVariantRequest.php
│   ├── UpdateVariantRequest.php
│   └── SyncVariantImagesRequest.php
├── Services/Products/
│   └── ProductVariantService.php

routes/web.php                                             # MODIFY: add nested product variant routes
```

No separate policies — variant actions use the product permissions (`PRODUCTS_EDIT`, `PRODUCTS_DELETE`).

## Implementation Order

ProductVariantService → Form Requests → Controllers → Routes

## Key Patterns

### ProductVariantService Methods

| Method | Notes |
|--------|-------|
| `generateVariants(Product $product, array $optionsData)` | Create options + values + all Cartesian combinations. Wrap in `DB::transaction()`. Blocked if variants exist beyond the default with no option values. |
| `storeManual(Product $product, array $data)` | Create single variant with option values. Check duplicate combinations first. |
| `update(ProductVariant $variant, array $data)` | Update identifier, price, status. Stock is read-only in edit. |
| `destroy(ProductVariant $variant)` | Hard delete. Cascade removes pivot rows. |
| `isDuplicateCombination(Product $product, array $valueIds, ?int $excludeVariantId)` | Check if same set of option value IDs exists on another variant of the same product. |
| `syncVariantImages(ProductVariant $variant, array $mediaIds)` | Sync `media_product_variant` pivot. |
| `getVariantImages(ProductVariant $variant)` | Return associated media, or fall back to all product media if no associations exist. |

### "Keep & Edit" Transition Flow

When `generateVariants()` is called on a product with exactly one variant (the default, no option values):

1. Service creates `ProductOption` and `ProductOptionValue` records
2. Builds value-ID arrays per option and computes Cartesian product
3. The **first combination** is assigned to the existing default variant — populates its `product_variant_option_values` pivot rows (preserves variant ID)
4. Remaining combinations are created as new variants
5. All wrapped in `DB::transaction()`

If the product has multiple variants or options with assigned values, generation is blocked — throw `\Exception`.

### Cartesian Product Helper

Private method in `ProductVariantService`. Given arrays of value IDs per option, returns all combinations. Each combination is an array of `ProductOptionValue` IDs.

### Duplicate Combination Check

Before creating any variant, verify no existing variant on the same product has the exact same set of `product_option_value_id`s. Check via the `values()` relationship — matching all provided IDs AND not matching any IDs not provided.

### Option Value Deletion Guard

Before deleting a `ProductOptionValue`, check if any `ProductVariantOptionValue` row references it. If so, throw `\Exception` — controller catches and redirects back with flash error.

### Image-Variant Association

- `$variant->images()->sync($mediaIds)` — manages the `media_product_variant` pivot only
- `getVariantImages()`: if `$variant->images()->exists()` is false, return `$variant->product->getMedia('images')`; otherwise return `$variant->images`
- Media IDs must belong to the same product — validate in `SyncVariantImagesRequest`

### Controller Actions

All actions are nested under the product and redirect back to the product page.

| Action | Method | Notes |
|--------|--------|-------|
| Store option | POST `/products/{product}/options` | Create option + values; redirect back |
| Update option | PUT `/products/{product}/options/{option}` | Rename option; redirect back |
| Delete option | DELETE `/products/{product}/options/{option}` | Cascade deletes values; redirect back |
| Store value | POST `/products/{product}/options/{option}/values` | Add value to option; redirect back |
| Delete value | DELETE `/products/{product}/options/{option}/values/{value}` | Guard: blocked if used by variant; redirect back |
| Generate variants | POST `/products/{product}/variants/generate` | Auto-generate from options; redirect back |
| Store variant | POST `/products/{product}/variants` | Manual add; redirect back |
| Update variant | PUT `/products/{product}/variants/{variant}` | Edit fields; redirect back |
| Delete variant | DELETE `/products/{product}/variants/{variant}` | Hard delete; redirect back |
| Sync images | PUT `/products/{product}/variants/{variant}/images` | Sync media IDs; redirect back |

All mutations redirect back with `->with('success', ...)` or catch `\Exception` and redirect `->back()->with('error', ...)`.

### Form Request Rules

| Request | Key Rules |
|---------|-----------|
| `StoreProductOptionRequest` | `name` required max 150; `values` required array min 1; `values.*` required string max 50 |
| `StoreOptionValueRequest` | `value` required string max 50 |
| `GenerateVariantsRequest` | `options` required array min 1; `options.*.name` required max 150; `options.*.values` required array min 1; `options.*.values.*` required max 50 |
| `StoreVariantRequest` | `identifier` nullable unique where not null max 50; `price` required numeric min 0; `stock` required integer min 0; `status` required in:active,inactive,archived; `option_value_ids` required array; `option_value_ids.*` exists:product_option_values,id |
| `UpdateVariantRequest` | `identifier` nullable max 50; `price` sometimes numeric min 0; `status` sometimes in:active,inactive,archived |
| `SyncVariantImagesRequest` | `media_ids` required array; `media_ids.*` exists:media,id |

Authorization for all: `$this->user()?->can(PermissionsEnum::PRODUCTS_EDIT->value)`.
Delete variant uses `PRODUCTS_DELETE`.

### Routes

Nested under the `products` group, inside existing `['auth']` middleware.

```
POST   /products/{product}/options                              → option.store
PUT    /products/{product}/options/{option}                     → option.update
DELETE /products/{product}/options/{option}                     → option.destroy
POST   /products/{product}/options/{option}/values              → value.store
DELETE /products/{product}/options/{option}/values/{value}      → value.destroy
POST   /products/{product}/variants/generate                    → variant.generate
POST   /products/{product}/variants                             → variant.store
PUT    /products/{product}/variants/{variant}                   → variant.update
DELETE /products/{product}/variants/{variant}                   → variant.destroy
PUT    /products/{product}/variants/{variant}/images            → variant.images.sync
```

> `variants/generate` must be defined **before** `variants/{variant}` to prevent `{variant}` absorbing "generate".

## Gotchas

1. **No soft deletes on variants.** Use `status = 'archived'` to hide. Hard delete removes pivot rows via cascade.
2. **`identifier` uniqueness.** MySQL allows multiple NULLs in a unique column. The `unique` validation rule ignores NULL by default — no special handling needed.
3. **`withoutTimestamps()` on pivot.** `product_variant_option_values` has no timestamps — use `->withTimestamps()` only on the `media_product_variant` relationship (which does have timestamps).
4. **Delete guard = plain `\Exception`.** Service throws, controller catches and flashes error. Follows Task 01 pattern.
5. **Trait-based logging.** Add `LogsActivity` to `ProductVariant` — `logFillable()` + `logOnlyDirty()`. No manual `activity()` calls.
6. **Stock is read-only in edit.** `UpdateVariantRequest` does not accept `stock`. Phase 3 inventory adjustments are the only way to modify it after creation.
7. **"Keep & edit" preserves variant ID.** The default variant's `product_variant_option_values` rows are populated — the variant record itself is not replaced. Any downstream reference (orders, vendor catalog) remains valid.
8. **Media IDs must belong to the product.** Validate in `SyncVariantImagesRequest` that each `media_id` belongs to the same product as the variant.
9. **Cartesian wrap in `DB::transaction()`.** Partial failures during batch creation would leave orphaned options/values without it.
