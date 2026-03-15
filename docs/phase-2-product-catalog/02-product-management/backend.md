# Backend — Product Management

## Key Files to Create

```
app/
├── Enums/ProductStatus.php
├── Http/Controllers/Products/
│   ├── ProductController.php
│   └── ProductMediaController.php
├── Http/Requests/Products/
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
├── Http/Resources/Products/ProductResource.php
├── Models/Product.php
├── Policies/ProductPolicy.php
└── Services/Products/
    ├── ProductService.php
    └── PendingMediaService.php
```

## Implementation Steps

1. **`ProductStatus` enum** — `active | inactive | archived`; cast in model via `$casts`
2. **`Product` model** — implements `HasMedia`, uses `SoftDeletes`, `LogsActivity`, `InteractsWithMedia`; register `'images'` collection + `thumb`/`medium` conversions; add `hasActiveVariants(): bool`
3. **`ProductPolicy`** — all methods check `$user->can('products.manage')`
4. **Form Requests** — `StoreProductRequest`: all required fields; `UpdateProductRequest`: use `sometimes` on `name`/`status`; both use `Rule::exists(...)->whereNull('deleted_at')` for FKs
5. **`PendingMediaService`** — `upload(UploadedFile, userId): array{uuid,url}` stores to `local` disk under `pending_media/{uuid}/`; `findByUuid()` and `delete()`
6. **`ProductService`** — `list()`, `create()`, `update()`, `delete()` (guard check), `restore()`, `commitPendingMedia()`
7. **`ProductController`** — thin; delegates to service; passes filter options (brands, categories) as Inertia props on index/create/edit
8. **`ProductMediaController`** — validates file (image, max 5MB); calls `PendingMediaService`; returns JSON

## Key Patterns

**Deletion guard:**
```php
public function hasActiveVariants(): bool {
    return $this->variants()->whereNotIn('status', ['archived'])->whereNull('deleted_at')->exists();
}
```

**`whenLoaded` in resource (prevents N+1 on list):**
```php
'brand' => $this->whenLoaded('brand', fn() => ['id' => $this->brand?->id, 'name' => $this->brand?->name]),
'variants_count' => $this->whenCounted('variants'),
```

**`array_key_exists` for nullable FK updates:**
```php
// Distinguish "not sent" from "explicitly null"
'brand_id' => array_key_exists('brand_id', $data) ? $data['brand_id'] : $product->brand_id,
```

**Route registration:**
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::patch('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::post('/products/media/pending', [ProductMediaController::class, 'store'])->name('products.media.pending.store');
    Route::delete('/products/media/pending/{uuid}', [ProductMediaController::class, 'destroy'])->name('products.media.pending.destroy');
});
```

> Note: `/products/media/pending` route must be defined **before** `Route::resource` to prevent `{product}` absorbing the "media" segment.

## Packages Used
- `spatie/laravel-medialibrary` — `HasMedia`, `InteractsWithMedia`, media conversions
- `spatie/laravel-permission` — policy checks
- `spatie/laravel-activitylog` — `LogsActivity` trait

## Gotchas
- Register `thumb` and `medium` conversions in `registerMediaConversions()` — not in a migration
- `nullOnDelete()` on both FK columns — brand/unit deletion nullifies the FK, does not delete the product
- `restore` endpoint takes raw `{id}` not route model binding (soft-deleted records excluded from binding)
- Pending media cleanup: schedule an artisan command to `Storage::deleteDirectory('pending_media/'.$uuid)` for entries older than 24h
- `withCount('variants')` + `$this->whenCounted('variants')` avoids loading the full variant collection on list
