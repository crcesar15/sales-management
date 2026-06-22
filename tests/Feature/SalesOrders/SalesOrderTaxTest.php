<?php

declare(strict_types=1);

use App\Enums\DiscountType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesOrderService;

beforeEach(function () {
    // Seed a 13% tax rate so SalesOrderService::create() picks it up via Setting::get().
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '13',
        'group' => 'sales',
    ]);

    $this->store = Store::factory()->create();
    $this->actor = User::factory()->create();
    $this->actor->stores()->attach($this->store->id);

    // A product variant priced at 100 so a quantity of 10 yields subtotal 1000.
    $product = Product::factory()->create();
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => 100,
        'stock' => 100,
    ]);

    $this->service = app(SalesOrderService::class);
});

it('saves tax_amount and total with a 13% tax rate and no discount', function () {
    $order = $this->service->create([
        'customer_id' => null,
        'store_id' => $this->store->id,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 0,
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 10,
                'unit_price' => 100,
                'conversion_factor' => 1,
            ],
        ],
        'payments' => [],
    ], $this->actor);

    // subTotal = 1000, discount = 0, tax = round(1000 * 13/100, 2) = 130, total = 1130
    expect((float) $order->sub_total)->toBe(1000.0)
        ->and((float) $order->discount)->toBe(0.0)
        ->and((float) $order->tax_amount)->toBe(130.0)
        ->and((float) $order->total)->toBe(1130.0);
});

it('saves tax_amount and total with a 13% tax rate and a flat discount', function () {
    $order = $this->service->create([
        'customer_id' => null,
        'store_id' => $this->store->id,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 200,
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 10,
                'unit_price' => 100,
                'conversion_factor' => 1,
            ],
        ],
        'payments' => [],
    ], $this->actor);

    // subTotal = 1000, discount = 200, tax = round(800 * 13/100, 2) = 104, total = 904
    expect((float) $order->sub_total)->toBe(1000.0)
        ->and((float) $order->discount)->toBe(200.0)
        ->and((float) $order->tax_amount)->toBe(104.0)
        ->and((float) $order->total)->toBe(904.0);
});

it('saves tax_amount and total with a 13% tax rate and a percentage discount', function () {
    $order = $this->service->create([
        'customer_id' => null,
        'store_id' => $this->store->id,
        'discount_type' => DiscountType::PERCENTAGE->value,
        'discount_value' => 10,
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 10,
                'unit_price' => 100,
                'conversion_factor' => 1,
            ],
        ],
        'payments' => [],
    ], $this->actor);

    // subTotal = 1000, discount = round(1000 * 10/100, 2) = 100, tax = round(900 * 13/100, 2) = 117, total = 1017
    expect((float) $order->sub_total)->toBe(1000.0)
        ->and((float) $order->discount)->toBe(100.0)
        ->and((float) $order->tax_amount)->toBe(117.0)
        ->and((float) $order->total)->toBe(1017.0);
});