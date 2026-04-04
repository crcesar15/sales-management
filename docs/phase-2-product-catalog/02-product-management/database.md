# Database — Product Management

## Tables

### `products`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `brand_id` | BIGINT UNSIGNED NULL | FK → `brands.id` `nullOnDelete` (migration needed — current FK is `NO ACTION`) |
| `measurement_unit_id` | BIGINT UNSIGNED NULL | FK → `measurement_units.id` `nullOnDelete` (migration needed — current FK is `NO ACTION`) |
| `name` | VARCHAR(255) | Not unique at DB level |
| `description` | VARCHAR(350) NULL | |
| `status` | ENUM('active','inactive','archived') | Default `'active'` |
| `deleted_at` | TIMESTAMP NULL | Soft delete |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

> `Product` model implements `HasMedia` (Spatie), uses `InteractsWithMedia`, `SoftDeletes`, `LogsActivity`.

### `category_product` (pivot)
| Column | Type | Notes |
|---|---|---|
| `category_id` | BIGINT UNSIGNED | FK → `categories.id` CASCADE DELETE |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |

> Pure join table — no `id`, no timestamps. Composite PK on `(category_id, product_id)`.
> Sync via `$product->categories()->sync($categoryIds)`.

### `product_variants`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `product_id` | BIGINT UNSIGNED | FK → `products.id` CASCADE DELETE |
| `identifier` | VARCHAR(50) NULL | SKU / barcode — unique where not null |
| `barcode` | VARCHAR(100) NULL | EAN/UPC — independent from internal SKU |
| `price` | DECIMAL(10,2) | User-provided on creation |
| `stock` | INT UNSIGNED | User-provided on creation; read-only after (Phase 3 manages adjustments) |
| `status` | ENUM('active','inactive','archived') | Default `'active'` |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

> Owned by this task for the **default variant**. One variant is auto-created per product in the same transaction. No soft deletes — use `status = 'archived'` to hide.
> `identifier` uses `->unique()->nullable()` — MySQL allows multiple NULLs in a unique column.

### `media` (Spatie — package-managed)
| Key Column | Notes |
|---|---|
| `model_type` | `App\Models\Product` |
| `model_id` | Product `id` |
| `collection_name` | `'images'` |
| `uuid` | Unique UUID per file |
| `file_name` | Original filename |

> HasMedia is on `Product` (not `ProductVariant`). Media conversions: `thumb` (368x232, sharpen 10).

### `pending_media_uploads` (NEW)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED NULL | FK → `users.id` nullOnDelete — scopes uploads to owner |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

> Lightweight model implementing `HasMedia`. Acts as a temporary container for images before the product exists.
> On product save, media is moved to the `Product` via Spatie's `move()` method, then the record is deleted.
> Cleanup cron deletes records (and their media) older than 24h.
> The existing `pending_media` table (UUID-token based) becomes obsolete. Drop it in the same migration batch as creating `pending_media_uploads`.

### `media_product_variant` (pivot — NEW)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `media_id` | BIGINT UNSIGNED | FK → `media.id` CASCADE DELETE |
| `product_variant_id` | BIGINT UNSIGNED | FK → `product_variants.id` CASCADE DELETE |
| `position` | INT UNSIGNED DEFAULT 0 | Sort order for variant images |
| `created_at` / `updated_at` | TIMESTAMP NULL | |

> Logical association between product images and specific variants. No file duplication.
> Unique constraint on `(media_id, product_variant_id)` prevents duplicate associations.
> **Inheritance rule**: variant with zero rows → inherits ALL product images. Variant with one or more rows → shows only explicitly associated images.

## Key Indexes
- `products`: `INDEX (brand_id)` (exists), `INDEX (measurement_unit_id)` (exists), **NEW** `INDEX (status)`, **NEW** `INDEX (name)`, **NEW** `INDEX (deleted_at)`
- `product_variants`: `INDEX (product_id)` (exists), `UNIQUE (identifier)` (exists), **NEW** `INDEX (status)`, **NEW** `INDEX (barcode)`
- `media_product_variant`: **NEW** `INDEX (media_id)`, **NEW** `INDEX (product_variant_id)`, **NEW** `UNIQUE (media_id, product_variant_id)`
- `pending_media_uploads`: **NEW** `INDEX (user_id)`, **NEW** `INDEX (created_at)`

## Relationships
```
products ——→ brands               (BelongsTo, nullable, withTrashed)
products ——→ measurement_units    (BelongsTo, nullable, withTrashed)
products ←→  categories           (BelongsToMany via category_product)
products ——→ product_variants     (HasMany — default variant created here)
products ——→ media                (HasMedia via Spatie InteractsWithMedia)
product_variants ←→ media         (BelongsToMany via media_product_variant)
pending_media_uploads ——→ media   (HasMedia — temporary container, media moved to Product on save)
```

## Pending Media Workflow (via PendingMediaUpload model)
1. `POST /products/media/pending` — create `PendingMediaUpload`, attach file via Spatie → returns `{ id, thumb_url, full_url }`
2. Frontend stores pending media IDs and displays real thumbnails immediately
3. Product store/update payload includes `pending_media_ids: [1, 2, ...]`
4. Service moves each media from `PendingMediaUpload` to `Product` via Spatie's `move()`, then deletes the `PendingMediaUpload` record
5. `remove_media_ids` in payload → `deleteMedia($id)` per entry
6. Scheduled command deletes `PendingMediaUpload` records (and their media) older than 24h

## Query Patterns

**Filtered paginated list (no N+1):** eager-load `brand`, `measurementUnit`, `categories`, `media`; `withCount('variants')`; filter by `name` (LIKE), `brand_id`, `category_id` (whereHas), `status`, `trashed` (onlyTrashed); order by `name`; paginate 20.

**Soft delete guard:** block if `variants()->whereNotIn('status', ['archived'])->exists()`.

**Image URLs:** `getFirstMediaUrl('images', 'thumb')` for list thumbnail; `getMedia('images')` for full gallery.

## Enum: `ProductStatus`
| Case | Value | Usage |
|---|---|---|
| `Active` | `'active'` | Visible in POS and lists |
| `Inactive` | `'inactive'` | Hidden from POS, editable |
| `Archived` | `'archived'` | Hidden from POS, preserved in order history |

Cast in model: `protected $casts = ['status' => ProductStatus::class];`

## Notable Patterns
- **Default variant creation**: wrapped in `DB::transaction()` alongside product creation; price and stock come from the product create form
- **Restore**: uses raw ID lookup (`Product::withTrashed()->findOrFail($id)->restore()`) — route model binding excludes trashed by default
- **Variant image inheritance**: check `$variant->images()->exists()` — if false, fall back to `$variant->product->getMedia('images')`
