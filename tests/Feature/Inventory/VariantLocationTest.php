<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantStoreLocation;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo([PermissionsEnum::INVENTORY_VIEW->value, PermissionsEnum::INVENTORY_EDIT->value]);
    $this->store = Store::factory()->create(['status' => 'active']);
    $this->otherStore = Store::factory()->create(['status' => 'active']);
    $this->user->stores()->attach([$this->store->id, $this->otherStore->id]);
    $this->variant = ProductVariant::factory()->for(Product::factory())->create();
});

it('assigns normalized locations without stock and permits shared codes', function (): void {
    $otherVariant = ProductVariant::factory()->for(Product::factory())->create();
    VariantStoreLocation::factory()->create(['product_variant_id' => $otherVariant->id, 'store_id' => $this->store->id, 'location_code' => 'A-03']);
    $this->actingAs($this->user)->put(route('inventory.variants.locations.update', $this->variant), ['locations' => [
        ['store_id' => $this->store->id, 'location_code' => ' a-03 '],
        ['store_id' => $this->otherStore->id, 'location_code' => 'rack 2 / b'],
    ]])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $this->variant->id, 'store_id' => $this->store->id, 'location_code' => 'A-03']);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $this->variant->id, 'store_id' => $this->otherStore->id, 'location_code' => 'RACK 2 / B']);
    $this->actingAs($this->user)->get(route('inventory.variants.show', $this->variant))->assertOk()->assertInertia(fn ($page) => $page->has('locationStores', 2));
    $this->actingAs($this->user)->put(route('inventory.variants.locations.update', $this->variant), ['locations' => [
        ['store_id' => $this->store->id, 'location_code' => '   '],
    ]])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('variant_store_locations', ['product_variant_id' => $this->variant->id, 'store_id' => $this->store->id]);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $otherVariant->id, 'store_id' => $this->store->id]);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $this->variant->id, 'store_id' => $this->otherStore->id]);
});

it('validates store access, duplicate stores and normalized length atomically', function (): void {
    $inaccessible = Store::factory()->create(['status' => 'active']);
    $this->actingAs($this->user)->put(route('inventory.variants.locations.update', $this->variant), ['locations' => [
        ['store_id' => $this->store->id, 'location_code' => 'A-01'],
        ['store_id' => $inaccessible->id, 'location_code' => str_repeat('x', 51)],
    ]])->assertSessionHasErrors(['locations.1.store_id', 'locations.1.location_code']);
    $this->assertDatabaseCount('variant_store_locations', 0);
    $this->actingAs($this->user)->put(route('inventory.variants.locations.update', $this->variant), ['locations' => [
        ['store_id' => $this->store->id, 'location_code' => 'A'],
        ['store_id' => $this->store->id, 'location_code' => 'B'],
    ]])->assertSessionHasErrors(['locations.0.store_id']);
});

it('limits suggestions to distinct codes from the accessible store', function (): void {
    foreach (['A-01', 'A-01', 'A-02', 'B-01'] as $code) {
        VariantStoreLocation::factory()->create(['store_id' => $this->store->id, 'location_code' => $code]);
    }
    VariantStoreLocation::factory()->create(['store_id' => $this->otherStore->id, 'location_code' => 'A-99']);
    $this->actingAs($this->user, 'sanctum')->getJson(route('api.v1.stores.location-codes.index', ['store' => $this->store, 'search' => 'a-']))
        ->assertOk()->assertJsonPath('data', ['A-01', 'A-02']);
    $outsider = Store::factory()->create(['status' => 'active']);
    $this->actingAs($this->user, 'sanctum')->getJson(route('api.v1.stores.location-codes.index', $outsider))->assertForbidden();
});

it('denies editing without inventory permission', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->putJson(route('inventory.variants.locations.update', $this->variant), ['locations' => [
        ['store_id' => $this->store->id, 'location_code' => 'A-01'],
    ]])->assertForbidden();
});

it('replicates nonempty locations to sibling variants only and preserves blank store assignments', function (): void {
    $sibling = ProductVariant::factory()->create(['product_id' => $this->variant->product_id]);
    $other = ProductVariant::factory()->for(Product::factory())->create();
    VariantStoreLocation::factory()->create(['product_variant_id' => $sibling->id, 'store_id' => $this->store->id, 'location_code' => 'OLD']);
    VariantStoreLocation::factory()->create(['product_variant_id' => $sibling->id, 'store_id' => $this->otherStore->id, 'location_code' => 'KEEP']);
    VariantStoreLocation::factory()->create(['product_variant_id' => $other->id, 'store_id' => $this->store->id, 'location_code' => 'OTHER']);

    $this->actingAs($this->user)->put(route('inventory.variants.locations.update', $this->variant), [
        'locations' => [
            ['store_id' => $this->store->id, 'location_code' => ' a-03 '],
            ['store_id' => $this->otherStore->id, 'location_code' => null],
        ],
        'replicate_locations' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $this->variant->id, 'store_id' => $this->store->id, 'location_code' => 'A-03']);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $sibling->id, 'store_id' => $this->store->id, 'location_code' => 'A-03']);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $sibling->id, 'store_id' => $this->otherStore->id, 'location_code' => 'KEEP']);
    $this->assertDatabaseHas('variant_store_locations', ['product_variant_id' => $other->id, 'store_id' => $this->store->id, 'location_code' => 'OTHER']);
});
