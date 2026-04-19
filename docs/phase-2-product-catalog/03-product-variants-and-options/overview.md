# Task 03 — Product Variants & Options

## What

The variant system gives products multiple sellable configurations.

- **Options** (e.g., "Color") with **Option Values** (e.g., "Red", "Blue") define the dimension axes
- **Variants** are the sellable instances (e.g., "Red / Large"), each with its own SKU, price, stock, and status
- Every product always has at least one variant — the **default variant** created automatically by Task 02

**Two creation modes for additional variants:**
1. **Auto-generate** — define options + values → system creates every combination (2 colors × 3 sizes = 6 variants)
2. **Manual** — add one variant at a time, selecting specific option values

**"Keep & edit" transition**: when a user adds options to a simple product (which has only the default variant), the existing default variant is preserved and assigned option values during the setup flow. It is not archived or replaced.

**Image-variant association**: product images (managed by Task 02) can be logically linked to specific variants via the `media_product_variant` pivot without duplicating files. Variants with no explicit associations inherit all product images.

## Why

Retail products exist in multiple configurations tracked independently for pricing and inventory. A single "T-Shirt" product may have 12 variants — each with a different price and stock level. The default variant ensures every product is sellable from creation. The "keep & edit" approach preserves existing variant references (e.g., in draft orders or vendor catalogs) during the simple-to-configurable transition.

## Requirements

### Options and Option Values

- **`product_options`**: `id`, `product_id` (FK, cascade delete), `name` (VARCHAR 150), timestamps
- **`product_option_values`**: `id`, `product_option_id` (FK, cascade delete), `value` (VARCHAR 50), timestamps
- Admin can add, rename, and delete options. Deleting an option cascades to its values.
- Admin can add and delete option values. **Deletion blocked** if any variant uses the value (pivot row exists in `product_variant_option_values`).

### product_variant_option_values Pivot

- Schema: `id`, `product_variant_id` (FK, cascade delete), `product_option_value_id` (FK, cascade delete)
- Note: this is a full Eloquent Model (`ProductVariantOptionValue` with auto-increment `id`), not a pure pivot. Access it via the `BelongsToMany` relationship on `ProductVariant::values()`.
- No timestamps on the pivot

### Multi-Variant Generation (Auto-Generate)

- Define options + values for a product, then compute the Cartesian product of all value sets
- Each combination becomes a variant with: `price = 0.00`, `stock = 0`, `status = 'active'`, `identifier = null`
- **Blocked if variants already exist** on the product (prevents overwrite of existing variants)
- Preview shows combination count before confirmation
- All creation wrapped in `DB::transaction()`

### "Keep & Edit" Transition Flow (Simple → Configurable)

When a user adds options to a product that currently has only the default variant:

1. Product has exactly 1 variant (the default) with no option values
2. User adds options and their values (e.g., Color: Red, Blue)
3. User triggers "generate variants" or manually adds variants
4. **The existing default variant is kept.** The user assigns option values to it (e.g., Color: Red) via the edit UI
5. The remaining combinations (e.g., Color: Blue) are created as new variants
6. Result: the default variant's `id` is preserved; its `product_variant_option_values` pivot rows are populated. No data loss, no orphan references

**Auto-generate availability rules:**
- Auto-generate is allowed when the product has exactly one variant (the default) with zero option values
- If the product already has multiple variants or options with assigned values, auto-generate is blocked
- Manual variant addition is always allowed (subject to duplicate combination check)

### Manual Variant Addition

- Add one variant at a time with specific option values
- Fields: `identifier` (nullable, unique where not null), `price` (decimal 10,2, required), `stock` (int ≥ 0, required), `status` (enum), `option_value_ids` (array of existing option value IDs)
- **Duplicate combination prevention**: before creating, verify no existing variant on the same product has the exact same set of option value IDs

### Image-Variant Association Management

- Uses the `media_product_variant` pivot table (created in Task 02)
- On the variant management UI, each variant can have a subset of the product's images assigned
- **Assignment**: sync media IDs to a variant — `$variant->images()->sync($mediaIds)`
- **Inheritance**: if a variant has zero rows in `media_product_variant`, it inherits ALL product images. If it has one or more rows, only those explicitly associated images are shown
- Relationship on `ProductVariant`: `images()` returns `BelongsToMany` to `Media` via `media_product_variant`
- The product's image gallery (Task 02) is the source of truth. This task only manages the logical link

### Variant Editing

- Editable fields: `identifier`, `price`, `stock`, `status`
- `stock` is displayed as read-only in the edit UI with a note "Managed by inventory" — Phase 3 controls stock adjustments. Stock can be set during variant creation only.
- `identifier` uniqueness: enforced at DB level. MySQL allows multiple NULLs in a unique column, so the current `->unique()->nullable()` migration works correctly.
- Status transitions: `active` ↔ `inactive` ↔ `archived` (no enforced transition rules)

### ProductVariantService

- Located at `app/Services/Products/ProductVariantService.php`
- Methods:
  - `generateVariants(Product $product, array $optionsData): array` — creates options, values, and all combination variants. Throws if variants already exist beyond the default with no option values
  - `storeManual(Product $product, array $data): ProductVariant` — creates a single variant with option values. Checks for duplicate combinations
  - `update(ProductVariant $variant, array $data): ProductVariant` — updates editable fields
  - `destroy(ProductVariant $variant): void` — deletes a variant (hard delete, cascade removes pivot rows)
  - `isDuplicateCombination(Product $product, array $optionValueIds, ?int $excludeVariantId): bool` — checks if the same set of option values already exists on another variant
  - `syncVariantImages(ProductVariant $variant, array $mediaIds): void` — syncs the `media_product_variant` pivot
  - `getVariantImages(ProductVariant $variant): Collection` — returns associated media, or all product media if no associations exist
- `cartesian(array $sets): array` — private helper for computing all combinations
- All write operations wrapped in `DB::transaction()`

### Rules and Guards

- Block deletion of option values used by any variant (pivot row exists)
- Prevent duplicate option value combinations per product
- Auto-generate blocked if variants already exist (beyond the default variant with no option values)
- `identifier` must be unique among non-null values
- Each variant should have at most one value per option (enforced at application level)

## Acceptance Criteria

- [ ] Admin can view all options, values, and variants for a product
- [ ] Admin can add/edit/delete options and their values
- [ ] Deleting an option value is blocked if any variant uses it (returns 422)
- [ ] Admin can auto-generate variants from option combinations; preview shows combination count before creation
- [ ] Auto-generate is blocked if variants already exist (beyond the default variant with no option values)
- [ ] Admin can manually add a variant with selected option values
- [ ] Duplicate option value combinations per product are prevented (returns 422)
- [ ] Admin can edit variant identifier, price, status; stock is display-only in edit
- [ ] "Keep & edit" transition: when adding options to a simple product, the default variant is preserved and assigned option values
- [ ] Admin can assign product images to specific variants via the `media_product_variant` pivot
- [ ] Variants with no image associations inherit all product images
- [ ] `identifier` uniqueness enforced (multiple NULLs allowed)
- [ ] All CUD operations are permission-gated (product.view/create/edit/delete) and activity-logged

## Dependencies

| Dependency | Reason |
|---|---|
| Task 02 (Products) | Variants scoped to a product; default variant created there; `HasMedia` on Product; `media_product_variant` pivot |
| Task 05 (Product Units) | Variant units reference `product_variant_id` |
| Task 01 (Measurement Units) | Base unit label derived from `product.measurement_unit` |
| Phase 3 (Inventory) | Stock column written by inventory adjustments |

## Notes

- `stock` is the live inventory count — Phase 3 writes to this column via adjustment records; do NOT update it manually outside of inventory workflows (display as read-only in edit UI, settable only during creation)
- Each variant should have exactly one value per option defined on the product (enforced at application level)
- The product **Show** page is the container for this UI
- `identifier` unique constraint: MySQL allows multiple NULLs in a unique column, so the existing `->unique()->nullable()` migration is correct. No partial index needed.
- **"Keep & edit"**: the default variant created by Task 02 keeps its `id` when the product becomes configurable. Only its `product_variant_option_values` pivot rows are populated. Any downstream reference to this variant ID (orders, vendor catalog, etc.) remains valid.
- The `ProductVariantOptionValue` model has an auto-increment `id` (it's a full Eloquent Model, not a pure pivot)
- **Image inheritance**: the `getVariantImages()` service method checks: if `$variant->images()->exists()` returns false, return `$variant->product->getMedia('images')`. Otherwise, return `$variant->images`.
- Variant deletion is a **hard delete** (cascade removes pivot rows). Soft deletes are NOT used on variants — use `status = 'archived'` to hide a variant while preserving data integrity.
