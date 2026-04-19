# Backend — Product Variant Units

## Key Files to Create

```
app/
├── Http/Controllers/Products/ProductVariantUnitController.php
├── Http/Requests/Products/
│   ├── StoreProductVariantUnitRequest.php
│   └── UpdateProductVariantUnitRequest.php
├── Http/Resources/Products/ProductVariantUnitResource.php
├── Models/ProductVariantUnit.php
└── Services/Products/ProductVariantUnitService.php
```

## Implementation Steps

1. **`ProductVariantUnit` model** — `$fillable`, `LogsActivity`, `BelongsTo` variant; no soft deletes (hard delete is appropriate)
2. **`ProductVariantUnitService`** — `list()`, `create()`, `update()`, `delete()`; filter by `type` parameter
3. **`StoreProductVariantUnitRequest`** — unique name scoped to variant + type; `price` required when `type=sale`, nullable when `type=purchase`
4. **`ProductVariantUnitController`** — receives `Product` and `ProductVariant` via nested route model binding; delegates to service; returns JSON or `back()`
5. **`ProductVariantUnitResource`** — exposes `id`, `type`, `name`, `conversion_factor`, `price`, `status`, `sort_order`
6. **Load units in variant show** — eager-load `$variant->units` (or `saleUnits`/`purchaseUnits`) in `ProductVariantController@show`

## Key Patterns

**Unique name scoped per variant + type (in Form Request):**
```php
public function rules(): array {
    $variantId = $this->route('variant')->id;
    $type = $this->input('type');
    return [
        'type'              => ['required', 'in:sale,purchase'],
        'name'              => ['required', 'string', 'max:100',
                                Rule::unique('product_variant_units', 'name')
                                    ->where('product_variant_id', $variantId)
                                    ->where('type', $type)],
        'conversion_factor' => ['required', 'integer', 'min:1'],
        'price'             => ['nullable', 'numeric', 'min:0', Rule::requiredIf($type === 'sale')],
        'status'            => ['required', 'in:active,inactive'],
        'sort_order'        => ['nullable', 'integer', 'min:0'],
    ];
}
```

**Update — ignore self in unique check:**
```php
Rule::unique('product_variant_units', 'name')
    ->where('product_variant_id', $variantId)
    ->where('type', $this->input('type'))
    ->ignore($this->route('unit'))
```

**Service (simple — no complex guards):**
```php
public function list(ProductVariant $variant, string $type): Collection {
    return $variant->units()->where('type', $type)->orderBy('sort_order')->get();
}

public function create(ProductVariant $variant, array $data): ProductVariantUnit {
    return $variant->units()->create($data);
}

public function update(ProductVariantUnit $unit, array $data): ProductVariantUnit {
    $unit->update($data);
    return $unit->fresh();
}

public function delete(ProductVariantUnit $unit): void {
    $unit->delete();
}
```

**Routes (nested):**
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('products/{product}/variants/{variant}/units')
         ->name('products.variants.units.')
         ->group(function () {
             Route::get('/',             [ProductVariantUnitController::class, 'index'])->name('index');
             Route::post('/',            [ProductVariantUnitController::class, 'store'])->name('store');
             Route::put('/{unit}',       [ProductVariantUnitController::class, 'update'])->name('update');
             Route::delete('/{unit}',    [ProductVariantUnitController::class, 'destroy'])->name('destroy');
         });
});
```

**Eager-load in variant show:**
```php
// ProductVariantController@show
$variant->load(['optionValues', 'saleUnits', 'purchaseUnits', 'product.measurementUnit']);
return Inertia::render('Products/VariantShow', [
    'product' => new ProductResource($variant->product),
    'variant' => new ProductVariantResource($variant),  // includes sale_units + purchase_units
]);
```

## Model Setup
```php
// ProductVariantUnit model
protected $fillable = [
    'product_variant_id',
    'type',
    'name',
    'conversion_factor',
    'price',
    'status',
    'sort_order',
];

protected function casts(): array
{
    return [
        'conversion_factor' => 'integer',
        'price'             => 'decimal:2',
        'sort_order'        => 'integer',
    ];
}
```

## Packages Used
- `spatie/laravel-activitylog` — `LogsActivity` on `ProductVariantUnit`
- `spatie/laravel-permission` — authorization via `ProductPolicy` (reuse `products.manage` check)

## Gotchas
- `conversion_factor` is `INT UNSIGNED` — the `min:1` validation rule prevents zero/negative values
- Scoped uniqueness requires `Rule::unique(...)->where(...)` for both variant AND type — simple `unique:table,column` does not scope
- Hard delete is preferred here (no `deleted_at`) — units that are no longer wanted should be deleted; use `status = 'inactive'` to hide from POS without deleting the record
- If a `ProductVariant` is deleted, `cascadeOnDelete()` removes all its units automatically
- `price` on sale units is the price for that packaging — it is unrelated to `product_variants.price` (base unit price)
- `price` must be `NULL` for purchase type — vendor cost goes on the `catalog` table in phase-4
- The `type` field should not be changeable after creation (a sale unit cannot become a purchase unit) — omit `type` from update validation or reject changes
