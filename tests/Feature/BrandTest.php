<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('admin user can list brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->get(route('brands'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.brands'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.brands.show', $newBrand->id))
        ->assertStatus(200);
});

it('admin user can create brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.brands.store'), $newBrand)
        ->assertStatus(201);

    $latestBrand = Brand::latest('id')->firstOrFail();

    expect($latestBrand->name)->toBe($newBrand['name']);
});

it('creating brands with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    actingAs($user)
        ->post(route('api.brands.store'), [], ['Accept' => 'application/json'])
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
        ->put(route('api.brands.update', $newBrand->id), $updatedDataBrand)
        ->assertStatus(200);

    $updatedBrand = Brand::findOrFail($newBrand->id);

    expect($updatedBrand->name)->toBe($updatedDataBrand['name']);
});

it('updating brands with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->put(route('api.brands.update', $newBrand->id), [], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can delete brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->delete(route('api.brands.destroy', $newBrand->id))
        ->assertStatus(204);
});

it('non-admin user cannot list brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->get(route('brands'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.brands'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.brands.show', $newBrand->id))
        ->assertStatus(403);
});

it('non-admin user cannot create brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = [
        'name' => fake()->word,
    ];

    actingAs($user)
        ->post(route('api.brands.store'), $newBrand)
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
        ->put(route('api.brands.update', $newBrand->id), $updatedDataBrand)
        ->assertStatus(403);
});

it('non-admin user cannot delete brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newBrand = Brand::factory()->create();

    actingAs($user)
        ->delete(route('api.brands.destroy', $newBrand->id))
        ->assertStatus(403);
});
