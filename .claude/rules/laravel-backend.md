# Laravel Backend Rules

## Controllers

- Web controllers render Inertia pages (`Inertia::render()`) and redirect after mutations (`redirect()->route('users')`)
- API controllers return Eloquent Resources with proper status codes (`->setStatusCode(201)`)
- Controllers must NOT contain business logic — delegate to service classes
- Authorize using `$this->authorize(PermissionsEnum::USERS_VIEW)` in Web controllers (no user param needed — uses authenticated user automatically)
- API controllers authorize differently: `$this->authorize(PermissionsEnum::USERS_VIEW->value, auth()->user())` (requires `->value` and explicit user)
- Mark controller classes as `final`
- Use `request()->string('param', 'default')->value()` for type-safe query parameter extraction
- Use `request()->integer('param', default)` for integer parameters like `per_page`
- Wrap service calls that may throw `InvalidArgumentException` in try/catch, redirecting back with errors:

```php
try {
    $this->service->delete($model);
} catch (Exception $e) {
    return redirect()->back()->with('error', $e->getMessage());
}
```

### Web Controller Pattern

```php
final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_VIEW);
        $users = $this->userService->list();

        return Inertia::render('Users/Index', [
            'users' => new UserCollection($users),
            'filters' => ['filter' => null, 'status' => 'all'],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());
        return redirect()->route('users');
    }
}
```

### API Controller Pattern

```php
final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
```

## Service Classes

- Located in `app/Services/`
- Handle all business logic, DB transactions, and activity logging
- Mark as `final` class
- Use constructor property promotion for dependency injection
- Wrap critical operations in `DB::transaction()`
- Use `when()` for conditional query building
- Always eager load relationships needed by the caller (`->load(['roles', 'stores'])`)

```php
final class UserService
{
    public function __construct(private readonly UserService $userService) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create($data);
            // ... assign roles, etc.
            return $user->load(['roles', 'stores']);
        });
    }

    public function list(
        string $status = 'all',
        string $orderBy = 'first_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return User::query()
            ->with(['roles'])
            ->when($filter, fn ($q) => $q->where(/* ... */))
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }
}
```

### List Method Convention

The `list()` method uses a consistent signature across services:
```php
public function list(
    string $status = 'all',
    ?string $filter = null,
    string $orderBy = 'name',
    string $orderDirection = 'asc',
    int $perPage = 20,
): LengthAwarePaginator
```

Status filtering follows this convention:
- `'all'` — returns all records including soft-deleted (`withTrashed()`)
- `'archived'` — returns only soft-deleted (`onlyTrashed()`)
- `'active'` / `'inactive'` / specific status — filters by status column

Always call `->withQueryString()` on paginated results to preserve filter parameters.

Use `withCount()`, `withSum()`, `withMin()`, `withMax()` for aggregate eager loading instead of loading entire relations.

### State Machine Transitions

Services managing entities with status transitions define a `TRANSITION_MAP` constant:

```php
private const TRANSITION_MAP = [
    'draft' => ['sent', 'paid', 'cancelled'],
    'sent' => ['paid', 'cancelled'],
    'paid' => ['cancelled'],
    'cancelled' => [],
];
```

With a private `validateTransition()` method that throws `InvalidArgumentException` for invalid transitions.

### Business Rule Violations

- Throw `InvalidArgumentException` for business rule violations (not custom exception classes)
- Example: `throw new InvalidArgumentException('Only draft orders can be updated.');`
- Controllers catch these and redirect back with error messages

### Activity Logging in Services

Use the `activity()` helper for explicit activity logging:

```php
activity('sales_order')
    ->performedOn($order)
    ->causedBy(auth()->user())
    ->withProperties(['status' => $order->status->value])
    ->log("Sales order {$order->id} created");
```

### Settings Pattern

- Use `Setting::get('key', $default)` which wraps `Cache::rememberForever()` for cached retrieval
- Use `Setting::set('key', $value)` which updates and flushes the cache

### Critical Operations

- Use `lockForUpdate()` inside transactions for stock/concurrency-sensitive operations
- Always call `ProductVariant::recalculateStock()` after batch deductions

## Form Requests

- Always use Form Request classes for validation — never inline in controllers
- Use array-format validation rules (not pipe-delimited strings)
- Authorization uses `PermissionsEnum` values:

```php
public function authorize(): bool
{
    return $this->user()?->can(PermissionsEnum::USERS_CREATE->value) ?? false;
}
```

- Use `Rule::unique()->ignore()` for unique validation on updates
- Use `withValidator()` for cross-field validation (not `after()`)
- Web requests: `app/Http/Requests/{Module}/`
- API requests: `app/Http/Requests/Api/{Module}/`

## Eloquent Resources

- Single resources extend `JsonResource`, collections extend `ResourceCollection`
- Organized in subdirectories by module: `Resources/SalesOrder/SalesOrderResource.php`
- Use `whenLoaded()` for conditional relationship inclusion
- Collections include pagination metadata by overriding `paginationInformation()` to return empty array, then manually including `meta`:

```php
final class UserCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
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

- Format dates as ISO strings in resources: `$this->created_at->toISOString()`
- Cast decimal/money fields to `(float)`: `'total' => (float) $this->total`
- Extract enum values with `->value`: `'status' => $this->status->value`

## Models

- Use `casts()` method (not `$casts` property) following existing model conventions
- Document relationship return types with PHPDoc generics:

```php
/** @return HasMany<PurchaseOrder, $this> */
public function purchaseOrders(): HasMany
{
    return $this->hasMany(PurchaseOrder::class);
}
```

- Use Laravel Attributes for accessors/mutators
- Soft-deleted models use route binding with `withTrashed()` in `boot()`

### LogsActivity Trait

ALL models use the `LogsActivity` trait with standardized options:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logFillable()
        ->logOnlyDirty()
        ->useLogName('snake_case_model_name')
        ->dontSubmitEmptyLogs();
}
```

Exception: User model adds `->logExcept(['password'])` to exclude sensitive fields.

### Fillable, Hidden, and Appends

- Use `$fillable` only (never `$guarded`)
- Use `$hidden` for sensitive fields: `['password', 'remember_token']`
- Use `$appends` for computed attributes: `['full_name']`, `['name']`, `['expiry_status']`

### Local Scopes

Common scope patterns:
- `scopeSearch(Builder $query, string $term)` — multi-field LIKE search
- `scopeActive(Builder $query)` — active status filter
- `scopeExpiringSoon(Builder $query, int $days)` — date-based filter

### Model Events

- Use `booted()` or `boot()` for model-level event registration
- UUID generation in `booted()` via `self::creating(fn ($model) => $model->token = Str::uuid())`
- Route model binding with `withTrashed()` in `boot()` for soft-deleted models:

```php
protected static function boot(): void
{
    parent::boot();
    Route::bind('brand', fn ($value) => Brand::withTrashed()->findOrFail($value));
}
```

### Enum and Decimal Casting

```php
protected function casts(): array
{
    return [
        'status' => SalesOrderStatus::class,     // Backed enum casting
        'discount_value' => 'decimal:2',           // Money fields
        'created_at' => 'datetime:Y-m-d H:i',     // Formatted datetime
        'expiry_date' => 'date',                   // Date-only fields
    ];
}
```

### InteractsWithMedia

Models with media implement `HasMedia` interface and use `InteractsWithMedia` trait:

```php
final class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10);
    }
}
```

- `CustomPathGeneratorService` hashes media paths using `md5($media->id . config('app.key'))`
- Two-phase upload via `PendingMediaService`: temp upload → commit to product on save

### Business Logic Methods

Models contain business query methods:
- `hasActiveVariants(): bool` — checks for active related records
- `hasSalesOrders(): bool` — checks for dependent records before deletion
- `recalculateStock()` — recalculates stock from batches

## Migrations

- Anonymous class format: `return new class extends Migration`
- Foreign keys: `$table->foreignId('column')->constrained()->cascadeOnDelete()` or `->nullOnDelete()`
- Nullable foreign keys: `$table->foreignId('column')->nullable()->constrained()->nullOnDelete()`
- Explicit table name when column doesn't follow convention: `$table->foreignId('from_store_id')->constrained('stores')`
- Status columns: `$table->enum('status', ['draft', 'sent', 'paid'])->default('draft')`
- Soft deletes: `$table->softDeletes()` on trashable models
- Indexes: composite on frequently filtered columns (`$table->index(['store_id', 'status'])`), single on foreign keys
- Permission migration includes `category` column for grouping

## Factories

- All factories are `final class`
- PHPDoc: `@extends Factory<Model>`
- Enum values in factories use `->value`: `'status' => SalesOrderStatus::DRAFT->value`
- Nested factory calls for required relationships: `'customer_id' => Customer::factory()`
- Factory states for specific statuses (e.g., `inactive()`)

## Policies

- All policies are `final class`
- Use `PermissionsEnum` for authorization (no ownership-based logic except `UserPolicy`)
- Standard CRUD methods: `viewAny`, `view`, `create`, `update`, `delete`, `restore`
- Auto-discovered (not registered in `AuthServiceProvider`)
- Each method checks `$user->can(PermissionsEnum::X->value)`

## Enums

All enums are backed string enums (`enum FooEnum: string`):
- `PermissionsEnum`: dot notation values (`'brand.view'`, `'products.create'`)
- `RolesEnum`: descriptive values (`'super administrator'`, `'salesman'`)
- Domain enums: snake_case values (`'draft'`, `'flat'`, `'cash_in'`)
- Enum values must match database enum column values exactly