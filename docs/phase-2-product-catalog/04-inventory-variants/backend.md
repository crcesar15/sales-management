# Backend — Inventory Variants Module

## Key Files to Create

```
app/
├── Http/Controllers/InventoryVariantController.php
├── Http/Requests/Inventory/
│   └── UpdateVariantDetailRequest.php
├── Http/Resources/Inventory/
│   └── InventoryVariantCollection.php
```

## Permissions

**File:** `app/Enums/PermissionsEnum.php`
```php
case INVENTORY_VIEW = 'inventory.view';
case INVENTORY_EDIT = 'inventory.edit';
```

Seed via `php artisan db:seed --class=PermissionSeeder`.

## Routes

**File:** `routes/web.php`
```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Inventory Variants
    Route::get('/inventory/variants', [InventoryVariantController::class, 'index'])
        ->name('inventory.variants');
    Route::get('/products/{product}/variants/{variant}', [InventoryVariantController::class, 'show'])
        ->name('inventory.variants.show');
});
```

## Controller

**New file:** `app/Http/Controllers/InventoryVariantController.php`

```php
final class InventoryVariantController extends Controller
{
    public function __construct(
        private readonly VariantService $variantService
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW, auth()->user());

        $variants = $this->variantService->listAllVariants(
            status: $request->string('status', 'all')->toString(),
            filter: $request->string('filter')->toString(),
            orderBy: $request->string('order_by', 'created_at')->toString(),
            orderDirection: $request->string('order_direction', 'desc')->toString(),
            perPage: $request->integer('per_page', 15),
        );

        return Inertia::render('Inventory/Variants/Index', [
            'variants' => new InventoryVariantCollection($variants),
            'filters' => [
                'status' => $request->string('status', 'all')->toString(),
                'filter' => $request->string('filter')->toString(),
            ],
        ]);
    }

    public function show(Product $product, ProductVariant $variant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT, auth()->user());

        $variant->load([
            'values.option',
            'images',
            'saleUnits' => fn ($q) => $q->orderBy('sort_order'),
            'purchaseUnits' => fn ($q) => $q->orderBy('sort_order'),
            'product.brand',
            'product.categories',
            'product.measurementUnit',
            'product.media',
        ]);

        return Inertia::render('Inventory/Variants/Show/Index', [
            'product' => new ProductResource($variant->product),
            'variant' => new ProductVariantResource($variant),
        ]);
    }
}
```

## Service

**Existing file:** `app/Services/VariantService.php`

Add new method for cross-product variant listing:
```php
public function listAllVariants(
    string $status = 'all',
    string $filter = '',
    string $orderBy = 'created_at',
    string $orderDirection = 'desc',
    int $perPage = 15,
): LengthAwarePaginator {
    return ProductVariant::query()
        ->with(['product.brand', 'product.categories', 'values.option', 'images'])
        ->when($status !== 'all', fn ($q) => $q->where('status', $status))
        ->when($filter, fn ($q) => $q->whereHas(
            'product',
            fn ($pq) => $pq->where('name', 'like', "%{$filter}%")
        ))
        ->orderBy($orderBy, $orderDirection)
        ->paginate($perPage)
        ->withQueryString();
}
```

## Resource

**New file:** `app/Http/Resources/Inventory/InventoryVariantCollection.php`

```php
final class InventoryVariantCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn ($variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product?->name,
                'brand_name' => $variant->product?->brand?->name,
                'name' => $variant->name,  // computed accessor from option values
                'identifier' => $variant->identifier,
                'barcode' => $variant->barcode,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'status' => $variant->status,
                'is_default' => $variant->values->isEmpty(),
                'values' => $variant->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'option_name' => $v->option?->name,
                ]),
                'images' => $variant->images->map(fn ($img) => [
                    'id' => $img->id,
                    'thumb_url' => $img->thumb_url,
                    'full_url' => $img->full_url,
                ]),
                'created_at' => $variant->created_at->toISOString(),
            ]),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
```

## Form Request

**New file:** `app/Http/Requests/Inventory/UpdateVariantDetailRequest.php`

```php
final class UpdateVariantDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::INVENTORY_EDIT->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['nullable', 'string', 'max:50'],
            'barcode'    => ['nullable', 'string', 'max:100'],
            'price'      => ['required', 'numeric', 'min:0'],
            'stock'      => ['required', 'integer', 'min:0'],
            'status'     => ['required', 'in:active,inactive,archived'],
        ];
    }
}
```

## Sidebar Menu

**File:** `resources/js/Layouts/Composables/useMenuItems.ts`

Add to inventory items array:
```typescript
{
  key: "inventory-variants",
  label: t("Variants"),
  icon: "fa fa-boxes-stacked",
  to: "inventory.variants",
  can: "inventory.view",
  routeUrl: route("inventory.variants"),
},
```

## Gotchas
- The variant list includes ALL variants (simple product defaults too) — don't filter by `values` count
- `is_default` is computed from `$variant->values->isEmpty()`, not a DB column
- The variant detail page eager-loads `saleUnits` and `purchaseUnits` with type scopes on the model
- Route model binding: the `{product}/{variant}` URL ensures the variant belongs to the product
- The variant update route reuses the existing `variant.update` route but with a new form request that also allows `barcode` and `stock`
