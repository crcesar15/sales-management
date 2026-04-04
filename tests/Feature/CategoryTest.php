<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;

// ─── Authorization ────────────────────────────────────────────────────────────

it('guest is redirected to login', function () {
    get(route('categories'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('categories'))
        ->assertForbidden();
});

it('admin with permission can access the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('categories'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories')
            ->has('filters')
        );
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated categories ordered by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Category::factory()->create(['name' => 'Zebra']);
    Category::factory()->create(['name' => 'Alpha']);
    Category::factory()->create(['name' => 'Middle']);

    actingAs($admin)
        ->get(route('categories'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->where('categories.data.0.name', 'Alpha')
            ->where('categories.data.1.name', 'Middle')
            ->where('categories.data.2.name', 'Zebra')
            ->has('categories.meta')
        );
});

it('search by name filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Category::factory()->create(['name' => 'Electronics']);
    Category::factory()->create(['name' => 'Clothing']);

    actingAs($admin)
        ->get(route('categories', ['filter' => 'Elec']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->where('categories.data.0.name', 'Electronics')
            ->where('categories.meta.total', 1)
        );
});

it('soft-deleted records excluded from default list', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create(['name' => 'Deleted']);
    $category->delete();
    Category::factory()->create(['name' => 'Active']);

    actingAs($admin)
        ->get(route('categories'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->where('categories.meta.total', 1)
            ->where('categories.data.0.name', 'Active')
        );
});

it('status=archived returns only soft-deleted records', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create(['name' => 'Deleted']);
    $category->delete();
    Category::factory()->create(['name' => 'Active']);

    actingAs($admin)
        ->get(route('categories', ['status' => 'archived']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->where('categories.meta.total', 1)
            ->where('categories.data.0.name', 'Deleted')
        );
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin creates a category', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('categories.store'), ['name' => 'Electronics'])
        ->assertRedirect(route('categories'));

    expect(Category::where('name', 'Electronics')->exists())->toBeTrue();
});

it('empty name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('categories.store'), ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('name exceeding 50 chars returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('categories.store'), ['name' => str_repeat('a', 51)])
        ->assertSessionHasErrors(['name']);
});

it('duplicate name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Category::factory()->create(['name' => 'Electronics']);

    actingAs($admin)
        ->post(route('categories.store'), ['name' => 'Electronics'])
        ->assertSessionHasErrors(['name']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin updates a category name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create(['name' => 'Old Name']);

    actingAs($admin)
        ->put(route('categories.update', $category), ['name' => 'New Name'])
        ->assertRedirect(route('categories'));

    $category->refresh();
    expect($category->name)->toBe('New Name');
});

it('updating with the same name passes unique rule', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create(['name' => 'Electronics']);

    actingAs($admin)
        ->put(route('categories.update', $category), ['name' => 'Electronics'])
        ->assertRedirect(route('categories'));

    $category->refresh();
    expect($category->name)->toBe('Electronics');
});

it('renaming to an existing name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Category::factory()->create(['name' => 'Taken']);
    $category = Category::factory()->create(['name' => 'Other']);

    actingAs($admin)
        ->put(route('categories.update', $category), ['name' => 'Taken'])
        ->assertSessionHasErrors(['name']);
});

// ─── Soft Delete ──────────────────────────────────────────────────────────────

it('admin deletes a category', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();

    actingAs($admin)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories'));

    assertSoftDeleted($category);
});

it('category with active products cannot be deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $unit = MeasurementUnit::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id, 'measurement_unit_id' => $unit->id]);
    $category->products()->attach($product);

    actingAs($admin)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('error');

    $category->refresh();
    expect($category->deleted_at)->toBeNull();
});

// ─── Restore ──────────────────────────────────────────────────────────────────

it('admin restores a soft-deleted category', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();
    $category->delete();

    assertSoftDeleted($category);

    actingAs($admin)
        ->put(route('categories.restore', $category->id))
        ->assertRedirect(route('categories'));

    $category->refresh();
    expect($category->deleted_at)->toBeNull();
});

// ─── Permission Denials ──────────────────────────────────────────────────────

it('non-admin cannot create categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->post(route('categories.store'), ['name' => 'Test'])
        ->assertForbidden();
});

it('non-admin cannot update categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $category = Category::factory()->create();

    actingAs($user)
        ->put(route('categories.update', $category), ['name' => 'Test'])
        ->assertForbidden();
});

it('non-admin cannot delete categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $category = Category::factory()->create();

    actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();
});

it('non-admin cannot restore categories', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $category = Category::factory()->create();
    $category->delete();

    actingAs($user)
        ->put(route('categories.restore', $category->id))
        ->assertForbidden();
});
