# Store Management — Backend

## Implementation Steps

### 1. Model

```
app/Models/Store.php
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Store extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'code',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // ─── Media Library ────────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(100)
             ->height(100)
             ->nonQueued();
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo') ?: null;
    }

    public function getLogoThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo', 'thumb') ?: null;
    }
}
```

---

### 2. Controller

```
app/Http/Controllers/StoreController.php
```

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Requests\Store\AssignUserToStoreRequest;
use App\Http\Requests\Store\UpdateStoreStatusRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreService $service
    ) {}

    public function index(Request $request): Response
    {
        $stores = Store::query()
            ->when($request->search, fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            )
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stores/Index', [
            'stores'  => StoreResource::collection($stores),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Stores/Create');
    }

    public function store(CreateStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        $store = $this->service->create($request->validated(), $request->file('logo'));

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store created successfully.');
    }

    public function show(Store $store): Response
    {
        $store->load(['users.roles']);

        return Inertia::render('Stores/Show', [
            'store' => new StoreResource($store),
        ]);
    }

    public function edit(Store $store): Response
    {
        return Inertia::render('Stores/Edit', [
            'store' => new StoreResource($store),
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): \Illuminate\Http\RedirectResponse
    {
        $this->service->update($store, $request->validated(), $request->file('logo'));

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'Store updated successfully.');
    }

    public function updateStatus(UpdateStoreStatusRequest $request, Store $store): \Illuminate\Http\RedirectResponse
    {
        $store->update(['status' => $request->validated('status')]);

        activity('stores')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $request->validated('status')])
            ->log('status_changed');

        return redirect()
            ->back()
            ->with('success', 'Store status updated successfully.');
    }

    public function removeLogo(Store $store): \Illuminate\Http\RedirectResponse
    {
        $store->clearMediaCollection('logo');

        return redirect()
            ->back()
            ->with('success', 'Logo removed successfully.');
    }

    public function assignUser(AssignUserToStoreRequest $request, Store $store): \Illuminate\Http\RedirectResponse
    {
        $this->service->assignUser($store, $request->validated());

        return redirect()
            ->back()
            ->with('success', 'User assigned to store successfully.');
    }

    public function updateUserRole(Request $request, Store $store, User $user): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['role_id' => ['required', 'exists:roles,id']]);
        $store->users()->updateExistingPivot($user->id, ['role_id' => $request->role_id]);

        return redirect()
            ->back()
            ->with('success', 'User role updated successfully.');
    }

    public function removeUser(Store $store, User $user): \Illuminate\Http\RedirectResponse
    {
        $store->users()->detach($user->id);

        activity('stores')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $user->id])
            ->log('user_removed');

        return redirect()
            ->back()
            ->with('success', 'User removed from store successfully.');
    }
}
```

---

### 3. Service Class

```
app/Services/StoreService.php
```

```php
<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public function create(array $data, ?UploadedFile $logo = null): Store
    {
        return DB::transaction(function () use ($data, $logo) {
            $store = Store::create([
                'name'    => $data['name'],
                'code'    => strtoupper($data['code']),
                'address' => $data['address'] ?? null,
                'status'  => $data['status'] ?? 'active',
            ]);

            if ($logo) {
                $store->addMedia($logo)
                      ->toMediaCollection('logo');
            }

            activity('stores')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->log('created');

            return $store;
        });
    }

    public function update(Store $store, array $data, ?UploadedFile $logo = null): Store
    {
        return DB::transaction(function () use ($store, $data, $logo) {
            $store->update([
                'name'    => $data['name'],
                'code'    => strtoupper($data['code']),
                'address' => $data['address'] ?? null,
                'status'  => $data['status'],
            ]);

            if ($logo) {
                $store->addMedia($logo)
                      ->toMediaCollection('logo'); // singleFile() replaces old logo
            }

            activity('stores')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->withProperties($data)
                ->log('updated');

            return $store->fresh();
        });
    }

    public function assignUser(Store $store, array $data): void
    {
        $userId = $data['user_id'];
        $roleId = $data['role_id'];

        if ($store->users()->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'This user is already assigned to this store.',
            ]);
        }

        $store->users()->attach($userId, ['role_id' => $roleId]);

        activity('stores')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $userId, 'role_id' => $roleId])
            ->log('user_assigned');
    }
}
```

---

### 4. Form Requests

```
app/Http/Requests/Store/CreateStoreRequest.php
```

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stores.manage');
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'code'    => ['required', 'string', 'max:20', 'unique:stores,code', 'alpha_num'],
            'address' => ['nullable', 'string', 'max:500'],
            'status'  => ['required', 'in:active,inactive'],
            'logo'    => ['nullable', 'image', 'max:2048'], // 2MB max
        ];
    }
}
```

```
app/Http/Requests/Store/UpdateStoreRequest.php
```

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stores.manage');
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'code'    => [
                'required', 'string', 'max:20', 'alpha_num',
                Rule::unique('stores', 'code')->ignore($this->route('store')),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'status'  => ['required', 'in:active,inactive'],
            'logo'    => ['nullable', 'image', 'max:2048'],
        ];
    }
}
```

```
app/Http/Requests/Store/AssignUserToStoreRequest.php
```

```php
<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserToStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stores.manage');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }
}
```

---

### 5. API Resource

```
app/Http/Resources/StoreResource.php
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'code'           => $this->code,
            'address'        => $this->address,
            'status'         => $this->status,
            'logo_url'       => $this->logo_url,
            'logo_thumb_url' => $this->logo_thumb_url,
            'users_count'    => $this->whenCounted('users'),
            'users'          => $this->whenLoaded('users', fn () =>
                $this->users->map(fn ($user) => [
                    'id'        => $user->id,
                    'full_name' => $user->full_name,
                    'email'     => $user->email,
                    'role'      => optional($user->roles->first())->name,
                ])
            ),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
```

---

### 6. Policy

```
app/Policies/StorePolicy.php
```

```php
<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stores.manage');
    }

    public function view(User $user, Store $store): bool
    {
        return $user->can('stores.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('stores.manage');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->can('stores.manage');
    }

    public function delete(User $user, Store $store): bool
    {
        return false; // Stores cannot be deleted, only deactivated
    }
}
```

---

### 7. Routes

```php
// routes/web.php
use App\Http\Controllers\StoreController;

Route::middleware(['auth', 'can:stores.manage'])->group(function () {
    Route::resource('stores', StoreController::class)->except(['destroy']);
    Route::patch('stores/{store}/status', [StoreController::class, 'updateStatus'])
         ->name('stores.status');
    Route::delete('stores/{store}/logo', [StoreController::class, 'removeLogo'])
         ->name('stores.logo.remove');
    Route::post('stores/{store}/users', [StoreController::class, 'assignUser'])
         ->name('stores.users.assign');
    Route::patch('stores/{store}/users/{user}', [StoreController::class, 'updateUserRole'])
         ->name('stores.users.role');
    Route::delete('stores/{store}/users/{user}', [StoreController::class, 'removeUser'])
         ->name('stores.users.remove');
});
```

---

### 8. Activity Logging

Log all store management actions:

```php
activity('stores')
    ->performedOn($store)
    ->causedBy(auth()->user())
    ->withProperties(['name' => $store->name, 'code' => $store->code])
    ->log('created');
```

Log events: `created`, `updated`, `status_changed`, `user_assigned`, `user_removed`, `logo_removed`

---

## Good Practices
- Normalize `code` to uppercase in the service (do not rely on the form to send uppercase)
- Use `singleFile()` on the media collection — uploading a new logo automatically removes the old one
- Return `StoreResource` for all responses — never expose raw Eloquent data
- Wrap create and update in `DB::transaction()` since media upload happens after the model is saved
- Eager load `users.roles` when showing the store detail page to avoid N+1 queries
- Store logo `max:2048` validation (2MB) should be enforced at both form request and frontend level
