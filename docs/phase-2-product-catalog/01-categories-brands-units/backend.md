# Backend — Categories, Brands & Measurement Units

## Key Files to Create

```
app/
├── Http/Controllers/Catalog/
│   ├── CategoryController.php
│   ├── BrandController.php
│   └── MeasurementUnitController.php
├── Http/Requests/Catalog/
│   ├── StoreCategoryRequest.php  / UpdateCategoryRequest.php
│   ├── StoreBrandRequest.php     / UpdateBrandRequest.php
│   └── StoreMeasurementUnitRequest.php / UpdateMeasurementUnitRequest.php
├── Http/Resources/Catalog/
│   ├── CategoryResource.php
│   ├── BrandResource.php
│   └── MeasurementUnitResource.php
├── Models/  Category.php  Brand.php  MeasurementUnit.php
├── Policies/ CategoryPolicy.php  BrandPolicy.php  MeasurementUnitPolicy.php
└── Services/Catalog/
    ├── CategoryService.php
    ├── BrandService.php
    └── MeasurementUnitService.php
```

## Implementation Steps

1. **Models** — add `SoftDeletes`, `LogsActivity` traits; define `$fillable`; declare relationships; add `hasActiveProducts(): bool` helper
2. **Policies** — all methods check `$user->can('products.manage')`; register in `AuthServiceProvider::$policies`
3. **Form Requests** — `authorize()` delegates to policy; `rules()` includes `Rule::unique(...)->ignore($id)` on update
4. **Services** — thin service classes for list/create/update/delete/restore; business rule violations throw `HttpResponseException` (422)
5. **API Resources** — expose only needed fields; `deleted_at` formatted with `->toISOString()`
6. **Controllers** — call `$this->authorize()`, delegate to service, return `Inertia::render()` or JSON response
7. **Routes** — group under `prefix('catalog/setup')`, apply `['auth', 'verified']` middleware

## Key Patterns

**Model traits:**
```php
class Category extends Model {
    use SoftDeletes, LogsActivity;
    protected $fillable = ['name'];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()->logOnly(['name'])->logOnlyDirty()->useLogName('category');
    }
    public function hasActiveProducts(): bool {
        return $this->products()->whereNull('products.deleted_at')->exists();
    }
}
```

**Service deletion guard:**
```php
public function delete(Category $category): void {
    if ($category->hasActiveProducts()) {
        throw new HttpResponseException(response()->json([
            'message' => 'Cannot delete category: it is assigned to one or more active products.'
        ], 422));
    }
    $category->delete();
}
```

**Restore (must bypass soft-delete scope):**
```php
public function restore(int $id): Category {
    $category = Category::withTrashed()->findOrFail($id);
    $category->restore();
    return $category;
}
```

**Unique rule ignoring self on update:**
```php
Rule::unique('categories', 'name')->ignore($this->route('category'))
```

## Packages Used
- `spatie/laravel-permission` — `$user->can('products.manage')`
- `spatie/laravel-activitylog` — `LogsActivity` trait + `LogOptions`

## Gotchas
- Route model binding **excludes soft-deleted** records — restore endpoints must use raw `{id}` + `withTrashed()`
- `logOnlyDirty()` prevents empty activity log entries on no-op updates
- Brand/unit FKs on `products` use `nullOnDelete()` — deleting brand nullifies `products.brand_id`, does not cascade delete products
- All three controllers render the **same Inertia component** (`CatalogSetup/Index`) — pass `activeTab` prop to drive tab selection
