# Task 02: Vendor Catalog — Backend

## Implementation Steps

1. **Migration** — `add_purchasing_columns_to_catalog_table`: add `purchase_unit_id`, `conversion_factor`, `minimum_order_quantity`, `lead_time_days`
2. **Model** — update `App\Models\Catalog` (or `VendorCatalog`); add `belongsTo` for `purchaseUnit` (via `measurement_units`); add scope `active()`
3. **Unique Constraint** — ensure `unique(['vendor_id', 'product_variant_id'])` exists on migration
4. **Form Request** — `StoreCatalogRequest` / `UpdateCatalogRequest`; validate `conversion_factor >= 1`, unique vendor+variant (ignore on update), `status` enum
5. **Resource** — `CatalogResource` with nested vendor, product_variant, and purchase_unit
6. **Controller** — `VendorCatalogController`; nested under vendor route or flat `/catalog`
7. **Scope for PO Builder** — `Catalog::active()->where('vendor_id', $vendorId)->with(['productVariant', 'purchaseUnit'])`
8. **Routes** — nested resource `vendors/{vendor}/catalog` + flat `catalog/{entry}` for update/delete

## Key Classes / Files

| File                                                   | Purpose                          |
|--------------------------------------------------------|----------------------------------|
| `database/migrations/..._add_purchasing_columns_...`  | Schema update                    |
| `app/Models/Catalog.php`                               | Updated model                    |
| `app/Http/Controllers/VendorCatalogController.php`     | CRUD controller                  |
| `app/Http/Requests/StoreCatalogRequest.php`            | Validation                       |
| `app/Http/Resources/CatalogResource.php`               | API transformer                  |

## Important Patterns

```php
// Active scope
public function scopeActive(Builder $query): Builder
{
    return $query->where('status', 'active');
}

// Unique validation (store)
Rule::unique('catalog')->where(fn ($q) =>
    $q->where('vendor_id', $this->input('vendor_id'))
)
```

## Packages
- `spatie/laravel-permission` — gate with `vendors.manage`

## Gotchas
- `conversion_factor` defaults to `1` in migration but must be validated as ≥ 1 in requests
- When `purchase_unit_id` is null, `conversion_factor` should be forced to 1 in the service/request
- Price in catalog is a float snapshot reference — currency rounding should be consistent app-wide
