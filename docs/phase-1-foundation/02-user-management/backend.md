# User Management — Backend

## Implementation Steps

### 1. Controller
```
app/Http/Controllers/UserController.php
```

Use a resource controller with the following methods:
- `index()` — paginated list with search/filters
- `store()` — create user, assign role and stores
- `show()` — single user details
- `update()` — update profile
- `destroy()` — soft delete

Additional action methods:
- `updateStatus()` — PATCH status only
- `assignStore()` — POST store assignment
- `removeStore()` — DELETE store assignment

---

### 2. Service Class
Encapsulate business logic in a service:

```
app/Services/UserService.php
```

```php
class UserService
{
    public function create(array $data): User
    {
        $user = User::create([...]);
        $user->assignRole($data['role']);
        $user->stores()->attach($data['store_ids']);
        return $user;
    }

    public function update(User $user, array $data): User { ... }

    public function updateStatus(User $user, string $status): User { ... }
}
```

---

### 3. Form Requests
```
app/Http/Requests/User/CreateUserRequest.php
app/Http/Requests/User/UpdateUserRequest.php
```

**CreateUserRequest rules:**
```php
return [
    'first_name' => 'required|string|max:50',
    'last_name'  => 'required|string|max:50',
    'email'      => 'required|email|max:100|unique:users,email',
    'username'   => 'required|string|max:50|unique:users,username',
    'password'   => 'required|string|min:8|confirmed',
    'status'     => 'required|in:active,inactive,archived',
    'role'       => 'required|exists:roles,name',
    'store_ids'  => 'nullable|array',
    'store_ids.*'=> 'exists:stores,id',
];
```

**UpdateUserRequest:** Same but `email`/`username` unique rules ignore current user ID.

---

### 4. Policy
```
app/Policies/UserPolicy.php
```

```php
public function viewAny(User $user): bool
{
    return $user->can('users.manage');
}

public function delete(User $user, User $target): bool
{
    return $user->can('users.manage') && $user->id !== $target->id;
}
```

---

### 5. Routes
```php
Route::middleware(['auth', 'can:users.manage'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus']);
    Route::post('users/{user}/stores', [UserController::class, 'assignStore']);
    Route::delete('users/{user}/stores/{store}', [UserController::class, 'removeStore']);
});
```

---

### 6. API Resource
```
app/Http/Resources/UserResource.php
```

```php
return [
    'id'         => $this->id,
    'first_name' => $this->first_name,
    'last_name'  => $this->last_name,
    'full_name'  => $this->full_name,
    'email'      => $this->email,
    'username'   => $this->username,
    'phone'      => $this->phone,
    'status'     => $this->status,
    'roles'      => $this->getRoleNames(),
    'stores'     => StoreResource::collection($this->whenLoaded('stores')),
    'created_at' => $this->created_at->toISOString(),
];
```

---

### 7. Activity Logging
Log critical user management actions:

```php
activity('users')
    ->performedOn($user)
    ->causedBy(auth()->user())
    ->withProperties(['status' => $newStatus])
    ->log('status_changed');
```

Log events: `created`, `updated`, `status_changed`, `deleted`, `store_assigned`, `store_removed`.

---

## Good Practices
- Use `UserResource` for all responses — never expose raw model data
- Use database transactions when creating a user + assigning roles + stores
- Eager load `stores` and `roles` to avoid N+1 queries
- Add a `full_name` accessor on the `User` model: `$this->first_name . ' ' . $this->last_name`
- Scope all queries with `withoutTrashed()` by default (SoftDeletes does this automatically)
