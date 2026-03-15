# Backend — Sale Units

## Key Files to Create

```
app/
├── Http/Controllers/Products/SaleUnitController.php
├── Http/Requests/Products/
│   ├── StoreSaleUnitRequest.php
│   └── UpdateSaleUnitRequest.php
├── Http/Resources/Products/SaleUnitResource.php
├── Models/ProductVariantSaleUnit.php
└── Services/Products/SaleUnitService.php
```

## Implementation Steps

1. **`ProductVariantSaleUnit` model** — `$fillable`, `LogsActivity`, `BelongsTo` variant; no soft deletes (hard delete is appropriate)
2. **`SaleUnitService`** — `list()`, `create()`, `update()`, `delete()`; no complex guards needed
3. **`StoreSaleUnitRequest`** — unique name scoped to variant: `Rule::unique('product_variant_sale_units', 'name')->where('product_variant_id', $variantId)`
4. **`SaleUnitController`** — receives `Product` and `ProductVariant` via nested route model binding; delegates to service; returns JSON or `back()`
5. **`SaleUnitResource`** — exposes `id`, `name`, `conversion_factor`, `price`, `status`
6. **Load sale units in variant show** — eager-load `$variant->saleUnits` in `ProductVariantController@show`

## Key Patterns

**Unique name scoped per variant (in Form Request):**
```php
public function rules(): array {
    $variantId = $this->route('variant')->id;
    return [
        'name'              => ['required', 'string', 'max:100',
                                Rule::unique('product_variant_sale_units', 'name')
                                    ->where('product_variant_id', $variantId)],
        'conversion_factor' => ['required', 'integer', 'min:1'],
        'price'             => ['required', 'numeric', 'min:0'],
        'status'            => ['required', 'in:active,inactive'],
    ];
}
```

**Update — ignore self in unique check:**
```php
Rule::unique('product_variant_sale_units', 'name')
    ->where('product_variant_id', $variantId)
    ->ignore($this->route('saleUnit'))
```

**Service (simple — no complex guards):**
```php
public function create(ProductVariant $variant, array $data): ProductVariantSaleUnit {
    return $variant->saleUnits()->create($data);
}
public function update(ProductVariantSaleUnit $saleUnit, array $data): ProductVariantSaleUnit {
    $saleUnit->update($data);
    return $saleUnit->fresh();
}
public function delete(ProductVariantSaleUnit $saleUnit): void {
    $saleUnit->delete();
}
```

**Routes (nested):**
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('products/{product}/variants/{variant}/sale-units')
         ->name('products.variants.sale-units.')
         ->group(function () {
             Route::post('/',            [SaleUnitController::class, 'store'])->name('store');
             Route::put('/{saleUnit}',   [SaleUnitController::class, 'update'])->name('update');
             Route::delete('/{saleUnit}',[SaleUnitController::class, 'destroy'])->name('destroy');
         });
});
```

**Eager-load in variant show:**
```php
// ProductVariantController@show
$variant->load(['optionValues', 'saleUnits', 'product.measurementUnit']);
return Inertia::render('Products/VariantShow', [
    'product' => new ProductResource($variant->product),
    'variant' => new ProductVariantResource($variant),  // includes sale_units
]);
```

## Packages Used
- `spatie/laravel-activitylog` — `LogsActivity` on `ProductVariantSaleUnit`
- `spatie/laravel-permission` — authorization via `ProductPolicy` (reuse `products.manage` check)

## Gotchas
- `conversion_factor` is `INT UNSIGNED` — the `min:1` validation rule prevents zero/negative values
- Scoped uniqueness requires `Rule::unique(...)->where(...)` — simple `unique:table,column` does not scope by variant
- Hard delete is preferred here (no `deleted_at`) — sale units that are no longer wanted should be deleted, not soft-deleted; use `status = 'inactive'` to hide from POS without deleting the record
- If a `ProductVariant` is deleted, `cascadeOnDelete()` removes all its sale units automatically
- `price` on sale unit is the price for that packaging — it is unrelated to `product_variants.price` (base unit price)
