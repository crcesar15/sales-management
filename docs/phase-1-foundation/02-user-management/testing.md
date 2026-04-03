# User Management — Testing

## Test File Location
```
tests/Feature/Users/UserManagementTest.php
```

## Test Cases

```php
it('lists users for admin', function () {
    $admin = User::factory()->create()->assignRole('admin');
    User::factory()->count(5)->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Users/Index')
            ->has('users.data', 5)
        );
});

it('denies user list to sales rep', function () {
    $rep = User::factory()->create()->assignRole('sales_rep');

    $this->actingAs($rep)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('creates a new user', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'username'   => 'janedoe',
            'password'   => 'password',
            'password_confirmation' => 'password',
            'status'     => 'active',
            'role'       => 'sales_rep',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('validates required fields on user creation', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('users.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'username', 'password', 'role']);
});

it('enforces unique email on creation', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $existing = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'email' => $existing->email,
            'username' => 'newusername',
            // ... other fields
        ])
        ->assertSessionHasErrors('email');
});

it('updates a user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), ['first_name' => 'Updated'])
        ->assertRedirect();

    expect($user->fresh()->first_name)->toBe('Updated');
});

it('changes a user status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($admin)
        ->patch(route('users.status', $user), ['status' => 'inactive'])
        ->assertOk();

    expect($user->fresh()->status)->toBe('inactive');
});

it('soft deletes a user', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertNoContent();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('prevents admin from deleting themselves', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertForbidden();
});

it('assigns a user to a store', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create();
    $store = Store::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.stores.assign', $user), [
            'store_id' => $store->id,
        ])
        ->assertOk();

    $this->assertDatabaseHas('store_user', [
        'user_id' => $user->id,
        'store_id' => $store->id,
    ]);
});

it('removes a user from a store', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id);

    $this->actingAs($admin)
        ->delete(route('users.stores.remove', [$user, $store]))
        ->assertNoContent();

    $this->assertDatabaseMissing('store_user', [
        'user_id' => $user->id,
        'store_id' => $store->id,
    ]);
});

it('filters users by status', function () {
    $admin = User::factory()->create()->assignRole('admin');
    User::factory()->create(['status' => 'active']);
    User::factory()->create(['status' => 'inactive']);

    $this->actingAs($admin)
        ->get(route('users.index', ['status' => 'inactive']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});
```

## Coverage Goals
- All CRUD operations tested
- Permission enforcement (admin vs sales rep)
- Validation rules
- Soft delete behavior
- Store assignment and removal
- Filter and search functionality
