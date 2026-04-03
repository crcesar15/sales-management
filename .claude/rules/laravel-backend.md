# Laravel Backend Rules

## Controllers

- Web controllers render Inertia pages (`Inertia::render()`) and redirect after mutations (`redirect()->route('users')`)
- API controllers return Eloquent Resources with proper status codes (`->setStatusCode(201)`)
- Controllers must NOT contain business logic — delegate to service classes
- Authorize using `$this->authorize(PermissionsEnum::USERS_VIEW, auth()->user())` in Web controllers
- Mark controller classes as `final`

### Web Controller Pattern

```php
final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_VIEW, auth()->user());
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
- Web requests: `app/Requests/{Module}/`
- API requests: `app/Http/Requests/Api/{Module}/`

## Eloquent Resources

- Single resources extend `JsonResource`, collections extend `ResourceCollection`
- Use `whenLoaded()` for conditional relationship inclusion
- Collections include pagination metadata:

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
