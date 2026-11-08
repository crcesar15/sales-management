<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\SalesOrderPaymentStatus;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('filters sales orders by unpaid payment status', function (): void {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store);
    $user->givePermissionTo(PermissionsEnum::SALES_VIEW->value);

    $pendingOrder = SalesOrder::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'payment_status' => SalesOrderPaymentStatus::PENDING->value,
        'created_at' => now()->subMinutes(2),
    ]);
    $partiallyPaidOrder = SalesOrder::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'payment_status' => SalesOrderPaymentStatus::PARTIALLY_PAID->value,
        'created_at' => now()->subMinute(),
    ]);
    SalesOrder::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'payment_status' => SalesOrderPaymentStatus::PAID->value,
        'created_at' => now(),
    ]);

    actingAs($user)
        ->get(route('sales-orders', ['payment_status' => 'unpaid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SalesOrders/Index')
            ->where('filters.payment_status', 'unpaid')
            ->where('orders.meta.total', 2)
            ->where('orders.data.0.id', $partiallyPaidOrder->id)
            ->where('orders.data.1.id', $pendingOrder->id)
        );
});
