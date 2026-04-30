<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts additional_contacts to array', function () {
    $vendor = Vendor::factory()->create([
        'additional_contacts' => [['name' => 'Jane', 'role' => 'Billing']],
    ]);

    expect($vendor->additional_contacts)->toBeArray()
        ->and($vendor->additional_contacts[0]['name'])->toBe('Jane');
});

it('casts meta to array', function () {
    $vendor = Vendor::factory()->create([
        'meta' => ['tax_id' => '12-3456789'],
    ]);

    expect($vendor->meta)->toBeArray()
        ->and($vendor->meta['tax_id'])->toBe('12-3456789');
});

it('has purchaseOrders relationship', function () {
    $vendor = Vendor::factory()->create();
    PurchaseOrder::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->purchaseOrders)->toHaveCount(1);
});

it('has variants relationship via catalog pivot', function () {
    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $vendor->variants()->attach($variant->id, ['price' => 50]);

    expect($vendor->fresh()->variants)->toHaveCount(1);
});
