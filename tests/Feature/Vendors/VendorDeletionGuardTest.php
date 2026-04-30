<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\actingAs;

it('deletes vendor with no purchase orders or catalog entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();

    actingAs($admin)
        ->delete(route('vendors.destroy', $vendor))
        ->assertRedirect(route('vendors'));

    expect(Vendor::find($vendor->id))->toBeNull();
});

it('blocks deletion when vendor has purchase orders', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    PurchaseOrder::factory()->create(['vendor_id' => $vendor->id]);

    actingAs($admin)
        ->delete(route('vendors.destroy', $vendor))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Vendor::find($vendor->id))->not->toBeNull();
});

it('blocks deletion when vendor has catalog entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $vendor->variants()->attach($variant->id, ['price' => 100]);

    actingAs($admin)
        ->delete(route('vendors.destroy', $vendor))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Vendor::find($vendor->id))->not->toBeNull();
});

it('non-admin cannot delete vendors', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $vendor = Vendor::factory()->create();

    actingAs($user)
        ->deleteJson(route('vendors.destroy', $vendor))
        ->assertForbidden();
});
