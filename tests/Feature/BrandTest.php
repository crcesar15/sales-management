<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

it('admin user can list brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->get(route('brands'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.v1.brands'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.v1.brands.show', $newBrand->id))
        ->assertStatus(200);
});

it('admin user can create brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.v1.brands.store'), $newBrand)
        ->assertStatus(201);

    $latestBrand = Brand::latest('id')->firstOrFail();

    expect($latestBrand->name)->toBe($newBrand['name']);
});

it('creating brands with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->post(route('api.v1.brands.store'), [], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can update brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    $updatedDataBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->put(route('api.v1.brands.update', $newBrand->id), $updatedDataBrand)
        ->assertStatus(200);

    $updatedBrand = Brand::findOrFail($newBrand->id);

    expect($updatedBrand->name)->toBe($updatedDataBrand['name']);
});

it('updating brands with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->put(route('api.v1.brands.update', $newBrand->id), [], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can delete brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->delete(route('api.v1.brands.destroy', $newBrand->id))
        ->assertStatus(204);

    assertSoftDeleted($newBrand);
});

it('admin user can restore brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();
    $brand->delete();

    assertSoftDeleted($brand);

    actingAs($user)
        ->put(route('api.v1.brands.restore', $brand->id))
        ->assertStatus(204);

    expect(Brand::find($brand->id))->not->toBeNull();
});

it('admin user can list archived brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();
    $brand->delete();

    actingAs($user)
        ->get(route('api.v1.brands', ['status' => 'archived']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('non-admin user cannot list brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->get(route('brands'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.v1.brands'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.v1.brands.show', $newBrand->id))
        ->assertStatus(403);
});

it('non-admin user cannot create brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.v1.brands.store'), $newBrand)
        ->assertStatus(403);
});

it('non-admin user cannot update brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = Brand::factory()->create();

    $updatedDataBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->put(route('api.v1.brands.update', $newBrand->id), $updatedDataBrand)
        ->assertStatus(403);
});

it('non-admin user cannot delete brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->delete(route('api.v1.brands.destroy', $newBrand->id))
        ->assertStatus(403);
});

it('non-admin user cannot restore brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $brand = Brand::factory()->create();
    $brand->delete();

    actingAs($user)
        ->put(route('api.v1.brands.restore', $brand->id))
        ->assertStatus(403);
});
