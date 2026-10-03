<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\PurchaseOrder;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('filters purchase orders by paid status', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $paidPurchaseOrder = PurchaseOrder::factory()->create(['is_paid' => true]);
    PurchaseOrder::factory()->create(['is_paid' => false]);

    actingAs($admin)
        ->get(route('purchase-orders', ['is_paid' => 'paid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PurchaseOrders/Index')
            ->where('filters.is_paid', 'paid')
            ->where('purchaseOrders.meta.total', 1)
            ->where('purchaseOrders.data.0.id', $paidPurchaseOrder->id)
            ->where('purchaseOrders.data.0.is_paid', true)
        );
});

it('filters purchase orders by unpaid status', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    PurchaseOrder::factory()->create(['is_paid' => true]);
    $unpaidPurchaseOrder = PurchaseOrder::factory()->create(['is_paid' => false]);

    actingAs($admin)
        ->get(route('purchase-orders', ['is_paid' => 'unpaid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PurchaseOrders/Index')
            ->where('filters.is_paid', 'unpaid')
            ->where('purchaseOrders.meta.total', 1)
            ->where('purchaseOrders.data.0.id', $unpaidPurchaseOrder->id)
            ->where('purchaseOrders.data.0.is_paid', false)
        );
});
