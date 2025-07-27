<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

it('admin user can list users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.users'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.users.show', $user->id))
        ->assertStatus(200);
});

it('admin user can create users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(200);

    $newUser = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => fake()->email,
        'username' => fake()->userName,
        'phone' => fake()->phoneNumber,
        'status' => 'active',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest123#',
        'password_confirmation' => 'ThisIsATest123#',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->post(route('api.users.store'), $newUser)
        ->assertStatus(201);

    $latestUser = User::with('roles')->latest('id')->firstOrFail();

    expect($latestUser->first_name)->toBe($newUser['first_name']);
    expect($latestUser->last_name)->toBe($newUser['last_name']);
    expect($latestUser->email)->toBe($newUser['email']);
    expect($latestUser->username)->toBe($newUser['username']);
    expect($latestUser->status)->toBe($newUser['status']);
    expect($latestUser->date_of_birth)->toBe($newUser['date_of_birth']);
    expect($latestUser->roles[0]?->id)->toBe($newUser['roles'][0]);
});

it('creating users with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(200);

    $newUser = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'status' => 'ACTIVE',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest',
        'password_confirmation' => 'ThisIsATest123',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->post(route('api.users.store'), $newUser, ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can update users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newUser = User::factory()->create();
    $newUser->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(200);

    $updatedUserData = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => fake()->email,
        'username' => fake()->userName,
        'phone' => fake()->phoneNumber,
        'status' => 'active',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest123#',
        'password_confirmation' => 'ThisIsATest123#',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->put(route('api.users.update', $newUser->id), $updatedUserData)
        ->assertStatus(200);

    $updatedUser = User::with('roles')->findOrFail($newUser->id);

    expect($updatedUser->first_name)->toBe($updatedUserData['first_name']);
    expect($updatedUser->last_name)->toBe($updatedUserData['last_name']);
    expect($updatedUser->email)->toBe($updatedUserData['email']);
    expect($updatedUser->username)->toBe($updatedUserData['username']);
    expect($updatedUser->status)->toBe($updatedUserData['status']);
    expect($updatedUser->date_of_birth)->toBe($updatedUserData['date_of_birth']);
    expect($updatedUser->roles[0]?->id)->toBe($updatedUserData['roles'][0]);
});

it('updating users with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newUser = User::factory()->create();
    $newUser->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(200);

    $updatedUserData = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'status' => 'ACTIVE',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest',
        'password_confirmation' => 'ThisIsATest123',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->put(route('api.users.update', $newUser->id), $updatedUserData, ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can delete users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newUser = User::factory()->create();
    $newUser->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->delete(route('api.users.destroy', $newUser->id))
        ->assertStatus(204);

    /** @phpstan-ignore-next-line */
    $this->assertSoftDeleted($newUser);
});

it('non-admin user cannot list users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->get(route('users'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.users'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.users.show', $user->id))
        ->assertStatus(403);
});

it('non-admin user cannot create users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(403);

    $newUser = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => fake()->email,
        'username' => fake()->userName,
        'phone' => fake()->phoneNumber,
        'status' => 'active',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest123#',
        'password_confirmation' => 'ThisIsATest123#',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->post(route('api.users.store'), $newUser)
        ->assertStatus(403);
});

it('non-admin user cannot update users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newUser = User::factory()->create();
    $newUser->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('users.create'))
        ->assertStatus(403);

    $updatedUserData = [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'email' => fake()->email,
        'username' => fake()->userName,
        'phone' => fake()->phoneNumber,
        'status' => 'active',
        'date_of_birth' => fake()->date('Y-m-d'),
        'additional_properties' => null,
        'password' => 'ThisIsATest123#',
        'password_confirmation' => 'ThisIsATest123#',
        'roles' => [
            Role::where('name', RolesEnum::SALESMAN->value)->firstOrFail()->id,
        ],
    ];

    actingAs($user)
        ->put(route('api.users.update', $newUser->id), $updatedUserData)
        ->assertStatus(403);
});

it('non-admin user cannot delete users', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newUser = User::factory()->create();
    $newUser->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->delete(route('api.users.destroy', $newUser->id))
        ->assertStatus(403);
});
