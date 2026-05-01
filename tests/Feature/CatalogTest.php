<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// ─── Authorization ────────────────────────────────────────────────────────────

it('guest is redirected to login', function () {
    get(route('catalog'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = App\Models\User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('catalog'))
        ->assertForbidden();
});

it('admin with permission can access the page', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Catalog/Index')
            ->has('catalog')
            ->has('filters')
        );
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated catalog entries', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    Catalog::factory()->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id]);
    Catalog::factory()->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id]);

    actingAs($admin)
        ->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Catalog/Index')
            ->has('catalog.data', 2)
            ->has('catalog.meta')
        );
});

it('filters catalog by status', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    Catalog::factory()->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id, 'status' => 'active']);
    Catalog::factory()->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id, 'status' => 'inactive']);

    actingAs($admin)
        ->get(route('catalog', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Catalog/Index')
            ->has('catalog.data', 1)
            ->where('catalog.data.0.status', 'active')
        );
});

it('filters catalog by vendor_id', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendorA = Vendor::factory()->create();
    $vendorB = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    Catalog::factory()->create(['vendor_id' => $vendorA->id, 'product_variant_id' => $variant->id]);
    Catalog::factory()->create(['vendor_id' => $vendorB->id, 'product_variant_id' => $variant->id]);

    actingAs($admin)
        ->get(route('catalog', ['vendor_id' => $vendorA->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Catalog/Index')
            ->has('catalog.data', 1)
            ->where('catalog.data.0.vendor_id', $vendorA->id)
        );
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin creates a catalog entry', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs($admin)
        ->post(route('catalog.store'), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'unit_id' => null,
            'price' => 99.99,
            'payment_terms' => 'debit',
            'details' => 'Test details',
            'status' => 'active',
            'minimum_order_quantity' => 10,
            'lead_time_days' => 5,
        ])
        ->assertRedirect(route('catalog'));

    expect(Catalog::where('vendor_id', $vendor->id)->where('product_variant_id', $variant->id)->exists())->toBeTrue();
});

it('duplicate vendor_variant_unit returns validation error', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
        'unit_id' => null,
    ]);

    actingAs($admin)
        ->post(route('catalog.store'), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'unit_id' => null,
            'price' => 99.99,
            'payment_terms' => 'debit',
            'details' => null,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['vendor_id']);
});

it('invalid vendor_id returns validation error', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs($admin)
        ->post(route('catalog.store'), [
            'vendor_id' => 99999,
            'product_variant_id' => $variant->id,
            'price' => 99.99,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['vendor_id']);
});

it('negative price returns validation error', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs($admin)
        ->post(route('catalog.store'), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'price' => -1,
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['price']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin updates a catalog entry', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
        'price' => 50.00,
        'status' => 'active',
    ]);

    actingAs($admin)
        ->put(route('catalog.update', $catalog), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'unit_id' => null,
            'price' => 75.00,
            'payment_terms' => 'credit',
            'details' => 'Updated details',
            'status' => 'inactive',
            'minimum_order_quantity' => 20,
            'lead_time_days' => 10,
        ])
        ->assertRedirect(route('catalog'));

    $catalog->refresh();
    expect($catalog->price)->toBe(75.00)
        ->and($catalog->status)->toBe('inactive')
        ->and($catalog->payment_terms)->toBe('credit');
});

it('updating with same vendor_variant_unit passes unique rule', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
        'unit_id' => null,
    ]);

    actingAs($admin)
        ->put(route('catalog.update', $catalog), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'unit_id' => null,
            'price' => 100.00,
            'status' => 'active',
        ])
        ->assertRedirect(route('catalog'));

    $catalog->refresh();
    expect($catalog->price)->toBe(100.00);
});

// ─── Delete ───────────────────────────────────────────────────────────────────

it('admin deletes a catalog entry', function () {
    $admin = App\Models\User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
    ]);

    actingAs($admin)
        ->delete(route('catalog.destroy', $catalog))
        ->assertRedirect(route('catalog'));

    expect(Catalog::find($catalog->id))->toBeNull();
});

// ─── Permission Denials ──────────────────────────────────────────────────────

it('non-admin cannot create catalog entries', function () {
    $user = App\Models\User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs($user)
        ->post(route('catalog.store'), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'price' => 99.99,
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('non-admin cannot update catalog entries', function () {
    $user = App\Models\User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
    ]);

    actingAs($user)
        ->put(route('catalog.update', $catalog), [
            'vendor_id' => $vendor->id,
            'product_variant_id' => $variant->id,
            'price' => 99.99,
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('non-admin cannot delete catalog entries', function () {
    $user = App\Models\User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
    ]);

    actingAs($user)
        ->delete(route('catalog.destroy', $catalog))
        ->assertForbidden();
});
