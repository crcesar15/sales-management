<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\PurchaseOrderStatus;
use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use App\Models\ReplacementCostEvent;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CatalogService;
use App\Services\PriceReviewService;
use App\Services\PricingRecommendationService;
use App\Services\ProductVariantUnitService;
use App\Services\PurchaseOrderService;
use App\Services\ReceptionOrderService;
use App\Services\ReplacementCostService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\put;
use function Pest\Laravel\putJson;

/** @return array{User, ProductVariant} */
function pricingFixture(): array
{
    $actor = User::factory()->create();
    $actor->assignRole(RolesEnum::ADMIN);
    actingAs($actor);
    $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create(['status' => 'active'])->id, 'status' => 'active', 'price' => 12, 'margin_type' => 'percent', 'margin_value' => 25, 'has_expiration' => false]);

    return [$actor, $variant];
}

function pricingTestOffer(ProductVariant $variant, float $price = 10, ?ProductVariantUnit $unit = null): Catalog
{
    return Catalog::factory()->create(['product_variant_id' => $variant->id, 'vendor_id' => Vendor::factory()->create(['status' => 'active'])->id, 'price' => $price, 'unit_id' => $unit?->id, 'status' => 'active']);
}

function pricingTestOrder(Catalog $offer, float $price, User $actor, float $discount = 0): PurchaseOrder
{
    return app(PurchaseOrderService::class)->create([
        'vendor_id' => $offer->vendor_id, 'order_date' => today()->toDateString(), 'discount' => $discount,
        'items' => [['product_variant_id' => $offer->product_variant_id, 'catalog_id' => $offer->id, 'unit_id' => $offer->unit_id, 'quantity' => 2, 'price' => $price]],
    ], $actor);
}

it('calculates gross margin, fixed per-base-unit targets and conservative rounding', function () {
    $service = app(PricingRecommendationService::class);
    expect($service->evaluate('13', '12', 'percent', '25'))->toMatchArray(['suggested_price' => 17.34, 'below_cost' => true, 'below_target' => true, 'current_margin' => -8.33])
        ->and($service->evaluate('10', '150', 'amount', '3', 12)['suggested_price'])->toBe(156.0)
        ->and($service->evaluate('0', '0', 'percent', '25'))->toMatchArray(['suggested_price' => 0.0, 'current_margin' => null, 'below_cost' => false])
        ->and($service->evaluate(null, '12', 'percent', null)['suggested_price'])->toBeNull()
        ->and($service->normalize('72', 12))->toBe('6.000000000000');
    expect(fn () => $service->evaluate('10', '12', 'percent', '100'))->toThrow(InvalidArgumentException::class);
});

it('previews draft costs without changing references or selling prices', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    pricingTestOrder($offer, 13, $actor);
    postJson(route('inventory.pricing.preview'), ['items' => [['product_variant_id' => $variant->id, 'price' => 13]]])
        ->assertSuccessful()->assertJsonPath('data.0.suggested_price', 17.34)->assertJsonPath('data.0.below_cost', true);
    expect($variant->refresh()->replacement_cost)->toBeNull()->and((float) $variant->price)->toBe(12.0)->and(ReplacementCostEvent::count())->toBe(0);
});

it('switches to the approved supplier and retains normal cost while receipts use discounts', function () {
    [$actor, $variant] = pricingFixture();
    $first = pricingTestOffer($variant, 10);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $first->id, $actor));
    $unit = ProductVariantUnit::create(['product_variant_id' => $variant->id, 'type' => 'purchase', 'name' => 'Case', 'conversion_factor' => 12, 'status' => 'active']);
    $second = pricingTestOffer($variant, 156, $unit);
    $order = pricingTestOrder($second, 156, $actor, 72);
    app(PurchaseOrderService::class)->approve($order, $actor);
    expect($variant->refresh()->pricing_catalog_id)->toBe($second->id)->and($variant->replacement_cost)->toBe('13.000000000000')
        ->and((float) $variant->price)->toBe(12.0)->and((float) $variant->margin_value)->toBe(25.0);
    $store = Store::factory()->create(['status' => 'active']);
    $line = $order->lineItems()->sole();
    $receipt = app(ReceptionOrderService::class)->create(['purchase_order_id' => $order->id, 'store_id' => $store->id, 'items' => [['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => 2, 'loose_unit_quantity' => 0]]]]], $actor);
    app(ReceptionOrderService::class)->complete($receipt, $actor);
    expect(Batch::query()->where('reception_order_id', $receipt->id)->sole()->unit_cost)->toBe('10.000000000000')->and($variant->refresh()->replacement_cost)->toBe('13.000000000000');
});

it('restores the prior reference on cancellation and protects newer references', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    $service = app(PurchaseOrderService::class);
    $first = pricingTestOrder($offer, 13, $actor);
    $service->approve($first, $actor);
    $second = pricingTestOrder($offer, 14, $actor);
    $service->approve($second, $actor);
    $service->cancel($first->refresh(), null, $actor);
    expect($variant->refresh()->replacement_cost)->toBe('14.000000000000');
    $service->cancel($second->refresh(), null, $actor);
    expect($variant->refresh()->replacement_cost)->toBe('10.000000000000')->and(ReplacementCostEvent::whereNotNull('invalidated_at')->count())->toBe(2);
});

it('rejects conflicting normal costs atomically and does not publish twice', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    $order = pricingTestOrder($offer, 13, $actor);
    $order->lineItems()->create(['product_variant_id' => $variant->id, 'price' => 14, 'quantity' => 1, 'total' => 14]);
    expect(fn () => app(PurchaseOrderService::class)->approve($order, $actor))->toThrow(InvalidArgumentException::class);
    expect($order->refresh()->status)->toBe(PurchaseOrderStatus::DRAFT)->and(ReplacementCostEvent::count())->toBe(0);
    $order->lineItems()->latest('id')->firstOrFail()->update(['pricing_reference' => true]);
    app(PurchaseOrderService::class)->approve($order->refresh(), $actor);
    expect($variant->refresh()->replacement_cost)->toBe('14.000000000000');
    expect(fn () => app(PurchaseOrderService::class)->approve($order->refresh(), $actor))->toThrow(InvalidArgumentException::class);
    expect(ReplacementCostEvent::count())->toBe(1);
});

it('acknowledges increases and decreases without suppressing persistent risks', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    $order = pricingTestOrder($offer, 13, $actor);
    app(PurchaseOrderService::class)->approve($order, $actor);
    expect(app(PriceReviewService::class)->summary($variant->refresh())['reasons'])->toContain('Cost increased', 'Below replacement cost');
    put(route('inventory.pricing-reference.update', $variant), ['action' => 'review', 'reference_id' => $variant->replacement_cost_event_id])->assertRedirect();
    expect(app(PriceReviewService::class)->summary($variant->refresh())['reasons'])->not->toContain('Cost increased')->toContain('Below replacement cost');
    expect(app(PriceReviewService::class)->alerts(null, reason: 'changes', reviewState: 'reviewed')->total())->toBe(1);
    get(route('inventory.alerts', ['tab' => 'price-review', 'price_review_state' => 'reviewed', 'price_review_severity' => 'critical']))
        ->assertSuccessful()->assertInertia(fn ($page) => $page->where('priceReviewAlerts.meta.total', 1)->where('summary.price_review_count', 1));
    $lower = pricingTestOrder($offer, 9, $actor);
    app(PurchaseOrderService::class)->approve($lower, $actor);
    expect(app(PriceReviewService::class)->summary($variant->refresh())['reasons'])->toContain('Cost decreased');
});

it('finds independently underpriced selling packages in alert queries', function () {
    [$actor, $variant] = pricingFixture();
    $variant->update(['price' => 20]);
    $offer = pricingTestOffer($variant, 10);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    ProductVariantUnit::create(['product_variant_id' => $variant->id, 'type' => 'sale', 'name' => 'Box', 'conversion_factor' => 12, 'price' => 100, 'status' => 'active']);
    $alerts = app(PriceReviewService::class)->alerts(null, reason: 'pricing');
    expect($alerts->total())->toBe(1)->and($alerts->items()[0]['pricing']['reasons'])->toContain('Below replacement cost');
    get(route('inventory.alerts', ['tab' => 'price-review']))->assertSuccessful()->assertInertia(fn ($page) => $page->where('summary.price_review_count', 1)->has('priceReviewAlerts.data', 1));
});

it('keeps catalog changes and manual freshness in sync with selected references', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    app(CatalogService::class)->update($offer, ['price' => 13]);
    expect($variant->refresh()->replacement_cost)->toBe('13.000000000000');
    put(route('inventory.pricing-reference.update', $variant), ['action' => 'outdated', 'reference_id' => $variant->replacement_cost_event_id])->assertRedirect();
    expect($offer->refresh()->is_outdated)->toBeTrue()->and(app(PriceReviewService::class)->summary($variant->refresh())['outdated'])->toBeTrue();
    put(route('inventory.pricing-reference.update', $variant), ['action' => 'current', 'reference_id' => $variant->replacement_cost_event_id])->assertRedirect();
    expect($offer->refresh()->is_outdated)->toBeFalse();
    app(CatalogService::class)->delete($offer);
    expect($variant->refresh()->reference_outdated)->toBeTrue()->and($variant->pricing_catalog_id)->toBeNull();
});

it('preserves historical costs when purchase conversion changes and flags the reference', function () {
    [$actor, $variant] = pricingFixture();
    $unit = ProductVariantUnit::create(['product_variant_id' => $variant->id, 'type' => 'purchase', 'name' => 'Case', 'conversion_factor' => 12, 'status' => 'active']);
    $offer = pricingTestOffer($variant, 120, $unit);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    app(ProductVariantUnitService::class)->update($unit, ['conversion_factor' => 24]);
    expect($variant->refresh()->replacement_cost)->toBe('10.000000000000')->and($variant->reference_outdated)->toBeTrue()
        ->and($variant->replacementCostEvent?->conversion_factor)->toBe(12);
});

it('reports partial weighted cost coverage without leaking other stores', function () {
    [, $variant] = pricingFixture();
    $store = Store::factory()->create();
    foreach ([[4, 10], [2, 16], [4, null]] as [$quantity, $cost]) {
        Batch::factory()->create(['product_variant_id' => $variant->id, 'store_id' => $store->id, 'remaining_quantity' => $quantity, 'unit_cost' => $cost, 'status' => 'active', 'expiry_date' => null]);
    }
    Batch::factory()->create(['product_variant_id' => $variant->id, 'unit_cost' => 999, 'remaining_quantity' => 100, 'status' => 'active', 'expiry_date' => null]);
    expect(app(PriceReviewService::class)->actualCosts($variant, [$store->id]))->toMatchArray(['average' => 12.0, 'known_quantity' => 6, 'total_quantity' => 10, 'complete' => false]);
});

it('rejects stale price saves and preserves the target during price edits', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    $payload = ['price' => 18, 'margin_type' => 'percent', 'margin_value' => 25, 'status' => 'active', 'reference_id' => 0];
    put(route('inventory.variant.update', $variant), $payload)->assertSessionHasErrors('price');
    put(route('inventory.variant.update', $variant), [...$payload, 'reference_id' => $variant->replacement_cost_event_id])->assertSessionHasNoErrors();
    expect((float) $variant->refresh()->price)->toBe(18.0)->and((float) $variant->margin_value)->toBe(25.0);
});

it('saves overview independently without validating or overwriting pricing', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    $referenceId = $variant->replacement_cost_event_id;
    put(route('inventory.variant.update', $variant), [
        'section' => 'overview', 'identifier' => 'OVERVIEW-ONLY', 'barcode' => '123456',
        'minimum_stock_level' => 5, 'has_expiration' => true, 'status' => 'inactive',
        'price' => 999, 'margin_value' => 99, 'reference_id' => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($variant->refresh()->identifier)->toBe('OVERVIEW-ONLY')->and($variant->status)->toBe('inactive')
        ->and($variant->minimum_stock_level)->toBe(5)->and($variant->has_expiration)->toBeTrue()
        ->and((float) $variant->price)->toBe(12.0)->and((float) $variant->margin_value)->toBe(25.0)
        ->and($variant->replacement_cost_event_id)->toBe($referenceId);
});

it('saves pricing independently without overwriting overview fields', function () {
    [, $variant] = pricingFixture();
    $identifier = $variant->identifier;
    put(route('inventory.variant.update', $variant), [
        'section' => 'pricing', 'price' => 17.34, 'margin_type' => 'percent', 'margin_value' => 25,
        'reference_id' => null, 'status' => 'archived', 'identifier' => 'SHOULD-NOT-CHANGE',
        'minimum_stock_level' => 999,
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect((float) $variant->refresh()->price)->toBe(17.34)->and($variant->status)->toBe('active')
        ->and($variant->identifier)->toBe($identifier)->and($variant->minimum_stock_level)->not->toBe(999);
    get(route('inventory.variants.show', ['variant' => $variant->id, 'tab' => 'pricing']))
        ->assertSuccessful()->assertInertia(fn ($page) => $page->where('initialTab', 'pricing'));
});

it('enforces cost visibility, mutation permissions and store scope', function () {
    [, $variant] = pricingFixture();
    $reader = User::factory()->create();
    $reader->givePermissionTo([PermissionsEnum::INVENTORY_VIEW, PermissionsEnum::STOCK_ALERTS_VIEW]);
    actingAs($reader);
    get(route('inventory.variants.show', $variant))->assertSuccessful()->assertInertia(fn ($page) => $page->where('variant.actual_costs', null));
    putJson(route('inventory.pricing-reference.update', $variant), ['action' => 'review', 'reference_id' => 1])->assertForbidden();
    get(route('inventory.alerts', ['tab' => 'price-review']))->assertSuccessful()->assertInertia(fn ($page) => $page->where('summary.price_review_count', 0));
    $outsiderStore = Store::factory()->create();
    getJson(route('inventory.alerts', ['store_id' => $outsiderStore->id]))->assertForbidden();
});

it('rejects invalid targets and purchase units from another variant in previews', function () {
    [, $variant] = pricingFixture();
    $unit = ProductVariantUnit::create(['product_variant_id' => ProductVariant::factory()->for(Product::factory())->create()->id, 'type' => 'purchase', 'name' => 'Case', 'conversion_factor' => 12, 'status' => 'active']);
    postJson(route('inventory.pricing.preview'), ['items' => [['product_variant_id' => $variant->id, 'unit_id' => $unit->id, 'price' => 10]]])->assertUnprocessable();
    postJson(route('inventory.pricing.preview'), ['items' => [['product_variant_id' => $variant->id, 'price' => 10, 'margin_type' => 'percent', 'margin_value' => 100]]])->assertUnprocessable();
});

it('selects a sole offer on creation without replacing it when another supplier is added', function () {
    [$actor, $variant] = pricingFixture();
    $vendor = Vendor::factory()->create(['status' => 'active']);
    $offer = app(CatalogService::class)->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id, 'price' => 10, 'status' => 'active']);
    expect($variant->refresh()->pricing_catalog_id)->toBe($offer->id);
    $secondVendor = Vendor::factory()->create(['status' => 'active']);
    app(CatalogService::class)->create(['vendor_id' => $secondVendor->id, 'product_variant_id' => $variant->id, 'price' => 9, 'status' => 'active']);
    expect($variant->refresh()->pricing_catalog_id)->toBe($offer->id);
    $order = pricingTestOrder($offer, 13, $actor);
    app(PurchaseOrderService::class)->approve($order, $actor);
    app(CatalogService::class)->update($offer, ['details' => 'New delivery instructions']);
    expect($variant->refresh()->replacement_cost)->toBe('13.000000000000');
});

it('publishes supplier changes through the legacy catalog API without detaching offer identities', function () {
    [$actor, $variant] = pricingFixture();
    $offer = pricingTestOffer($variant);
    DB::transaction(fn () => app(ReplacementCostService::class)->select($variant, $offer->id, $actor));
    putJson(route('api.v1.variants.vendors.update', $variant), ['vendors' => [['id' => $offer->vendor_id, 'price' => 14, 'payment_terms' => 'immediate']]])->assertSuccessful();
    expect($offer->refresh()->price)->toBe('14.00')->and($variant->refresh()->pricing_catalog_id)->toBe($offer->id)->and($variant->replacement_cost)->toBe('14.000000000000');
    getJson(route('api.v1.variants.purchase-price-history', $variant))->assertSuccessful()->assertJsonPath('data.basis', 'normal_replacement_cost_per_base_unit')->assertJsonPath('data.stats.latest', 14)->assertJsonPath('data.stats.average', null);
});

it('prices package suggestions from replacement cost instead of multiplying the current selling price', function () {
    [, $variant] = pricingFixture();
    postJson(route('inventory.pricing.preview'), ['items' => [['product_variant_id' => $variant->id, 'price' => 13, 'sale_factor' => 12, 'selling_price' => 100]]])
        ->assertSuccessful()->assertJsonPath('data.0.cost', 156)->assertJsonPath('data.0.suggested_price', 208)->assertJsonPath('data.0.below_cost', true);
});

it('keeps vendor-centric catalog mutations connected to replacement pricing', function () {
    [, $variant] = pricingFixture();
    $vendor = Vendor::factory()->create(['status' => 'active']);
    postJson(route('api.v1.vendors.variants.store', [$vendor, $variant]), ['record' => ['price' => 10, 'status' => 'active']])->assertCreated();
    $offerId = $variant->refresh()->pricing_catalog_id;
    putJson(route('api.v1.vendors.variants.update', $vendor), ['variants' => [['id' => $variant->id, 'price' => 14]]])->assertSuccessful();
    expect($variant->refresh()->replacement_cost)->toBe('14.000000000000')->and($variant->pricing_catalog_id)->toBe($offerId);
    deleteJson(route('api.v1.vendors.variants.delete', [$vendor, $variant]))->assertSuccessful();
    expect($variant->refresh()->reference_outdated)->toBeTrue()->and($variant->pricing_catalog_id)->toBeNull();
});
