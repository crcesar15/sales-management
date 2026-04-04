# Backend — Categories, Brands & Measurement Units

> **Inertia-only module.** No API layer. All controllers render Inertia pages or redirect. Frontend tabs submit forms via `useForm()` directly to web routes.

## Current State (already built)

| Component | Status |
|-----------|--------|
| Models (`Category`, `Brand`, `MeasurementUnit`) | Complete — SoftDeletes, LogsActivity, route binding with withTrashed |
| Controller stubs (`index()` only) | Need expansion |
| Routes (GET only in `web.php`) | Need expansion |
| Permissions (CATEGORIES/BRANDS/MEASUREMENT_UNITS with VIEW/CREATE/EDIT/DELETE/RESTORE) | Seeded |
| Factories | Complete |

## Files to Create or Modify

```
app/
├── Http/Controllers/
│   ├── CategoryController.php          # EXPAND: add store/update/destroy/restore
│   ├── BrandController.php             # EXPAND: add store/update/destroy/restore
│   └── MeasurementUnitController.php   # EXPAND: add store/update/destroy/restore
├── Http/Requests/Categories/           # NEW: Store + Update
├── Http/Requests/Brands/               # NEW: Store + Update
├── Http/Requests/MeasurementUnits/     # NEW: Store + Update
├── Http/Resources/{Category,Brand,MeasurementUnit}/  # NEW: Collections only
├── Models/{Category,Brand,MeasurementUnit}.php        # MODIFY: add hasActiveProducts()
├── Policies/{Category,Brand,MeasurementUnit}Policy.php # NEW
└── Services/{Category,Brand,MeasurementUnit}Service.php # NEW

routes/web.php                    # MODIFY: expand catalog routes
app/Providers/AuthServiceProvider.php  # MODIFY: register 3 policies
```

No single-resource (JsonResource) classes needed — create/edit happens in Dialog modals on the Index page.

## Implementation Order

Models → Policies → Form Requests → Collections → Services → Controllers → Routes → AuthServiceProvider

## Key Patterns

### Models — add `hasActiveProducts()`

Same method on all three models. The guard prevents deleting entities assigned to active products.

```php
public function hasActiveProducts(): bool
{
    return $this->products()->whereNull('products.deleted_at')->exists();
}
```

### Policies

Follow `UserPolicy` exactly: `final class`, each method checks `$user->can(PermissionsEnum::X->value)`. Methods: `viewAny`, `view`, `create`, `update`, `delete`, `restore`.

Register all three in `AuthServiceProvider::$policies`.

### Form Requests

Follow `Users/StoreUserRequest` pattern. Authorization via `$this->user()?->can(PermissionsEnum::X->value) ?? false`.

| Entity | Store rules | Update rules |
|--------|------------|-------------|
| Category | `name` required, max 50, unique `categories` | same + `Rule::unique()->ignore($this->route('category'))` |
| Brand | `name` required, max 50, unique `brands` | same + `Rule::unique()->ignore($this->route('brand'))` |
| MeasurementUnit | `name` max 100 unique + `abbreviation` max 10 unique | same + `Rule::unique()->ignore($this->route('measurementUnit'))` |

Route parameter names (`category`, `brand`, `measurementUnit`) must match the `Route::bind()` calls in each model's `boot()`.

### Collection Resources

Follow `UserCollection` exactly. Only Collection classes needed (no single JsonResource). Used as Inertia props for DataTable pagination.

### Services

Follow `UserService` pattern. Key differences for catalog:

- **No manual `activity()` calls** — the model's `LogsActivity` trait handles all CUD logging automatically
- **Delete guard** throws `\Exception` (not `HttpResponseException`) — controller catches and redirects with flash error
- `list()` includes `->withCount('products')` and `status = 'archived'` filter for trash view
- `restore()` receives resolved model directly (route binding handles `withTrashed`)

```php
public function delete(Category $category): void
{
    if ($category->hasActiveProducts()) {
        throw new \Exception('Cannot delete category: it is assigned to one or more active products.');
    }

    DB::transaction(fn () => $category->delete());
}
```

### Controllers

Follow `StoreController`. **No `create()` or `edit()` methods** — forms happen in Dialog modals.

- All three `index()` render `CatalogSetup/Index` with `activeTab` prop (`'categories'`, `'brands'`, `'units'`)
- `$this->authorize(PermissionsEnum::X)` — enum directly, no `->value`, no second arg
- `destroy()` wraps service call in try/catch, redirects back with `->with('error', ...)` on failure

```php
public function destroy(Category $category): RedirectResponse
{
    $this->authorize(PermissionsEnum::CATEGORIES_DELETE);

    try {
        $this->categoryService->delete($category);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }

    return redirect()->route('categories');
}
```

### Routes

Flat routes inside existing `['auth']` group. No prefix, no `verified` middleware. Restore routes use `->withTrashed()`.

```php
// Per entity (example for categories):
Route::get('/categories', ...)->name('categories');
Route::post('/categories', ...)->name('categories.store');
Route::put('/categories/{category}', ...)->name('categories.update');
Route::delete('/categories/{category}', ...)->name('categories.destroy');
Route::put('/categories/{category}/restore', ...)->name('categories.restore')->withTrashed();
```

## Gotchas

1. **No create/edit pages.** Controllers have NO `create()` or `edit()` methods — Dialog modals handle forms.
2. **Three routes, one component.** All three index routes render `CatalogSetup/Index` with different `activeTab` props.
3. **`Route::bind()` handles withTrashed.** Models resolve soft-deleted records in `boot()`. Services receive resolved instances — don't duplicate `withTrashed()` lookups.
4. **Trait-based logging only.** Models have `LogsActivity` with `logFillable()` + `logOnlyDirty()`. Adding manual `activity()` calls creates duplicate entries.
5. **Delete guard = plain Exception.** Service throws `\Exception`, controller catches and flashes error. Frontend reads via `$page.props.flash.error`.
6. **Form errors auto-shared by Inertia.** Failed FormRequests populate `$page.props.errors` → `useForm()` exposes as `form.errors.field`. No controller handling needed.
7. **Unique ignore uses route params.** `$this->route('category')`, `$this->route('brand')`, `$this->route('measurementUnit')` — must match model `Route::bind()` names.
