<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

it('admin user can list roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->get(route('roles'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.roles'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.roles.show', $role->id))
        ->assertStatus(200);
});

it('admin user can create roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->get(route('roles.create'))
        ->assertStatus(200);

    $newRole = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.roles.store'), $newRole)
        ->assertStatus(201);

    $latestRole = Role::latest('id')->firstOrFail();

    expect($latestRole->name)->toBe($newRole['name']);
});

it('admin user can update roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->get(route('roles.edit', $role->id))
        ->assertStatus(200);

    actingAs($user)
        ->put(route('api.roles.update', $role->id), [
            'name' => 'testing',
        ])
        ->assertStatus(200);

    $updatedRole = Role::findOrFail($role->id);

    expect($updatedRole->name)->toBe('testing');
});

it('admin user can delete roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->delete(route('api.roles.destroy', $role->id))
        ->assertStatus(204);

    $deletedRole = Role::find($role->id);

    expect($deletedRole)->toBeNull();
});

it('non-admin user cannot list roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->get(route('roles'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.roles'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.roles.show', $role->id))
        ->assertStatus(403);
});

it('non-admin user cannot create roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->get(route('roles.create'))
        ->assertStatus(403);
});

it('non-admin user cannot update roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->get(route('roles.edit', $role->id))
        ->assertStatus(403);

    actingAs($user)
        ->put(route('api.roles.update', $role->id), [
            'name' => 'testing',
        ])
        ->assertStatus(403);
});

it('non-admin user cannot delete roles', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $role = new Role(['name' => fake()->word()]);
    $role->save();

    actingAs($user)
        ->delete(route('api.roles.destroy', $role->id))
        ->assertStatus(403);
});

it('creating role fails with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->post(
            route('api.roles.store'),
            [],
            ['Accept' => 'application/json']
        )
        ->assertStatus(422);
});
