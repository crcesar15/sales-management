<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Category;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('admin user can list categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newCategory = Category::factory()->create();

    actingAs($user)
        ->get(route('categories'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.categories'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.categories.show', $newCategory->id))
        ->assertStatus(200);
});

it('admin user can create categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newCategory = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.categories.store'), $newCategory)
        ->assertStatus(201);

    $latestCategory = Category::latest('id')->firstOrFail();

    expect($latestCategory->name)->toBe($newCategory['name']);
});

it('creating categories with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->post(route('api.categories.store'), [], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can update categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();

    $updatedCategoryData = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->put(route('api.categories.update', $category->id), $updatedCategoryData)
        ->assertStatus(200);

    $updatedCategory = Category::findOrFail($category->id);

    expect($updatedCategory->name)->toBe($updatedCategoryData['name']);
});

it('updating categories with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();

    actingAs($user)
        ->put(route('api.categories.update', $category->id), [], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can delete categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newCategory = Category::factory()->create();

    actingAs($user)
        ->delete(route('api.categories.destroy', $newCategory->id))
        ->assertStatus(204);
});

it('non-admin user cannot list categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newCategory = Category::factory()->create();

    actingAs($user)
        ->get(route('categories'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.categories'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.categories.show', $newCategory->id))
        ->assertStatus(403);
});

it('non-admin user cannot create categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newCategory = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.categories.store'), $newCategory)
        ->assertStatus(403);
});

it('non-admin user cannot update categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $category = Category::factory()->create();

    $updatedCategoryData = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->put(route('api.categories.update', $category->id), $updatedCategoryData)
        ->assertStatus(403);
});

it('non-admin user cannot delete categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newCategory = Category::factory()->create();

    actingAs($user)
        ->delete(route('api.categories.destroy', $newCategory->id))
        ->assertStatus(403);
});
