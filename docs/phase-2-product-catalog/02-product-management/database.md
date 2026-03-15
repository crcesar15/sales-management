# Database — Product Management

## Tables

### `products`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `brand_id` | BIGINT UNSIGNED NULL | FK → `brands.id` `nullOnDelete` |
| `measurement_unit_id` | BIGINT UNSIGNED NULL | FK → `measurement_units.id` `nullOnDelete` |
| `name` | VARCHAR(255) | Not unique at DB level |
| `description` | TEXT NULL | Max 350 chars enforced at app layer |
| `status` | ENUM('active','inactive','archived') | Default `'active'` |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

### `media` (Spatie — package-managed)
| Key Column | Notes |
|---|---|
| `model_type` | `App\Models\Product` |
| `model_id` | Product `id` |
| `collection_name` | `'images'` |
| `uuid` | Unique UUID per file |
| `file_name` | Original filename |

## Key Indexes
- `INDEX (brand_id)`, `INDEX (measurement_unit_id)`, `INDEX (status)`, `INDEX (name)`, `INDEX (deleted_at)`

## Relationships
```
products ——→ brands            (BelongsTo, nullable, withTrashed)
products ——→ measurement_units (BelongsTo, nullable, withTrashed)
products ←→  categories        (BelongsToMany via category_product)
products ——→ product_variants  (HasMany — Task 03)
products ——→ media             (HasMedia via Spatie InteractsWithMedia)
```

## Migration Snippet
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
    $table->foreignId('measurement_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
    $table->softDeletes();
    $table->timestamps();
    $table->index(['brand_id', 'status', 'name']);
});
```

## Query Patterns

**Filtered paginated list (no N+1):**
```php
Product::query()
    ->with(['brand', 'measurementUnit', 'categories', 'media'])
    ->withCount('variants')
    ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
    ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
    ->when($categoryId, fn($q) => $q->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId)))
    ->when($status, fn($q) => $q->where('status', $status))
    ->when($trashed, fn($q) => $q->onlyTrashed())
    ->orderBy('name')->paginate(20);
```

**Sync categories:** `$product->categories()->sync($categoryIds)`

**Get image URLs:**
```php
$product->getFirstMediaUrl('images', 'thumb');
$product->getMedia('images')->map(fn($m) => ['id' => $m->id, 'url' => $m->getUrl(), 'thumb' => $m->getUrl('thumb')]);
```

## Pending Media Workflow
1. `POST /products/media/pending` — upload file → stored at `pending_media/{uuid}/filename`, returns `{ uuid, url }`
2. Product store/update payload includes `pending_media_uuids: [uuid1, ...]`
3. Service iterates UUIDs → `$product->addMedia($path)->toMediaCollection('images')` → deletes temp file
4. `remove_media_ids` in payload → `$product->deleteMedia($id)` per entry
5. Scheduled command purges `pending_media/` directories older than 24h

## Enum: `ProductStatus`
```php
enum ProductStatus: string {
    case Active   = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
```
Cast in model: `protected $casts = ['status' => ProductStatus::class];`
