<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CatalogService
{
    /**
     * Whitelist of user-facing sort keys mapped to real DB columns.
     * Unknown keys fall back to a safe default column.
     */
    private const SORT_COLUMN_MAP = [
        'product_name' => 'products.name',
        'brand_name' => 'brands.name',
        'name' => 'products.name',
        'identifier' => 'product_variants.identifier',
        'vendor_count' => 'vendor_count',
        'price' => 'catalog.price',
        'status' => 'catalog.status',
        'created_at' => 'catalog.created_at',
        'updated_at' => 'catalog.updated_at',
        'id' => 'catalog.id',
    ];

    /** @param list<array<string, mixed>> $products */
    public function updateVendorOffers(int $vendorId, array $products): void
    {
        DB::transaction(function () use ($vendorId, $products): void {
            foreach ($products as $product) {
                $this->saveVendorOffer($vendorId, (int) $product['id'], collect($product)->except('id')->all());
            }
        });
    }

    public function removeVendorOffers(int $vendorId, int $variantId): void
    {
        DB::transaction(function () use ($vendorId, $variantId): void {
            foreach (Catalog::query()->where('vendor_id', $vendorId)->where('product_variant_id', $variantId)->get() as $entry) {
                $this->delete($entry);
            }
        });
    }

    /** @param array<string, mixed> $data */
    public function saveVendorOffer(int $vendorId, int $variantId, array $data): Catalog
    {
        return DB::transaction(function () use ($vendorId, $variantId, $data): Catalog {
            if (isset($data['previous_product_id']) && (int) $data['previous_product_id'] !== $variantId) {
                $this->removeVendorOffers($vendorId, (int) $data['previous_product_id']);
            }
            unset($data['previous_product_id']);
            $entry = Catalog::query()->where('vendor_id', $vendorId)->where('product_variant_id', $variantId)->where('unit_id', $data['unit_id'] ?? null)->first();
            $payload = [...$data, 'vendor_id' => $vendorId, 'product_variant_id' => $variantId];

            return $entry === null ? $this->create($payload) : $this->update($entry, $payload);
        });
    }

    /** @param list<array{id: int, price: float|int|string, payment_terms?: string|null, details?: string|null}> $vendors */
    public function syncVariantVendors(ProductVariant $variant, array $vendors): void
    {
        DB::transaction(function () use ($variant, $vendors): void {
            foreach ($variant->catalogEntries()->whereNotIn('vendor_id', array_column($vendors, 'id'))->get() as $entry) {
                $this->delete($entry);
            }
            foreach ($vendors as $vendor) {
                $entry = $variant->catalogEntries()->where('vendor_id', $vendor['id'])->whereNull('unit_id')->first();
                $data = ['vendor_id' => $vendor['id'], 'product_variant_id' => $variant->id, 'unit_id' => null, 'price' => $vendor['price'], 'payment_terms' => $vendor['payment_terms'] ?? null, 'details' => $vendor['details'] ?? null];
                if ($entry === null) {
                    $this->create($data);
                } else {
                    $this->update($entry, $data);
                }
            }
        });
    }

    /**
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 20,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'catalog.created_at';
        $direction = mb_strtolower($orderDirection) === 'asc' ? 'asc' : 'desc';
        $needsProductJoin = $sortColumn === 'products.name';

        return Catalog::query()
            ->select('catalog.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id'))
            ->with(['vendor', 'productVariant.product.brand', 'productVariant.product.measurementUnit', 'productVariant.values.option', 'productVariant.activePurchaseUnits', 'unit'])
            ->when($vendorId, fn ($q) => $q->where('catalog.vendor_id', $vendorId))
            ->when($status !== 'all', fn ($q) => $q->where('catalog.status', $status))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('productVariant', fn ($vq) => $vq->where('identifier', 'like', "%{$filter}%"));
                })
            )
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function listGroupedByProduct(
        string $status = 'active',
        string $orderBy = 'product_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'catalog.created_at';
        $direction = mb_strtolower($orderDirection) === 'desc' ? 'desc' : 'asc';
        $needsProductJoin = $sortColumn === 'products.name';

        return Catalog::query()
            ->select('catalog.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id'))
            ->with(['vendor', 'productVariant.product.brand', 'productVariant.product.measurementUnit', 'productVariant.values.option', 'productVariant.activePurchaseUnits', 'unit'])
            ->when($vendorId, fn ($q) => $q->where('catalog.vendor_id', $vendorId))
            ->when($status !== 'all', fn ($q) => $q->where('catalog.status', $status))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('productVariant', fn ($vq) => $vq->where('identifier', 'like', "%{$filter}%"));
                })
            )
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Catalog
    {
        return DB::transaction(function () use ($data): Catalog {
            $catalog = Catalog::create($data);
            $actor = auth()->user();
            $variant = ProductVariant::query()->lockForUpdate()->whereKey($catalog->product_variant_id)->sole();
            if ($actor !== null && $variant->replacement_cost_event_id === null && $variant->catalogEntries()->where('status', 'active')->where('is_outdated', false)->count() === 1) {
                $catalog->load(['vendor', 'unit']);
                if ($catalog->vendor?->status === 'active' && ($catalog->unit_id === null || ($catalog->unit?->type === 'purchase' && $catalog->unit->status === 'active'))) {
                    app(ReplacementCostService::class)->select($variant, $catalog->id, $actor);
                    $variant->pendingCostReviews()->update(['reviewed_at' => now(), 'reviewed_by' => $actor->id]);
                }
            }

            return $catalog->load(['vendor', 'productVariant.product', 'productVariant.values.option', 'unit']);
        });
    }

    /**
     * @return array<int, string>
     */
    public function existingKeysForVendor(int $vendorId): array
    {
        return Catalog::query()
            ->where('vendor_id', $vendorId)
            ->get(['product_variant_id', 'unit_id'])
            ->map(fn (Catalog $catalog): string => sprintf('%d:%s', $catalog->product_variant_id, $catalog->unit_id ?? 'base'))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Catalog $catalog, array $data): Catalog
    {
        return DB::transaction(function () use ($catalog, $data): Catalog {
            $catalog->update($data);

            if (auth()->user() !== null && $catalog->wasChanged(['price', 'unit_id', 'vendor_id', 'product_variant_id', 'status', 'is_outdated'])) {
                app(ReplacementCostService::class)->refreshCatalog($catalog, auth()->user());
            }

            return $catalog->load(['vendor', 'productVariant.product', 'productVariant.values.option', 'unit']);
        });
    }

    public function getVariantWithCatalogEntries(int $productVariantId): ProductVariant
    {
        return ProductVariant::query()
            ->with([
                'product.brand',
                'product.measurementUnit',
                'product.categories',
                'values.option',
                'catalogEntries.vendor',
                'catalogEntries.unit',
            ])
            ->findOrFail($productVariantId);
    }

    /**
     * List all product variants with their catalog entries, regardless of
     * whether they have a vendor. Filters apply to the variant's status.
     *
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function listVariants(
        string $status = 'active',
        string $orderBy = 'product_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'product_variants.created_at';
        $direction = mb_strtolower($orderDirection) === 'desc' ? 'desc' : 'asc';
        $needsProductJoin = in_array($orderBy, ['product_name', 'brand_name'], true);
        $needsBrandJoin = $orderBy === 'brand_name';

        return ProductVariant::query()
            ->select('product_variants.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->whereNull('products.deleted_at'))
            ->when($needsBrandJoin, fn ($q) => $q
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id'))
            ->with([
                'product.brand',
                'product.measurementUnit',
                'values.option',
                'activePurchaseUnits',
                'catalogEntries.vendor',
                'catalogEntries.unit',
            ])
            ->addSelect(['vendor_count' => Catalog::selectRaw('count(distinct vendor_id)')
                ->whereColumn('catalog.product_variant_id', 'product_variants.id'),
            ])
            ->when($status !== 'all', fn ($q) => $q
                ->where('product_variants.status', $status))
            ->when($vendorId, fn ($q) => $q
                ->whereHas('catalogEntries', fn ($q) => $q->where('catalog.vendor_id', $vendorId)))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhere('product_variants.identifier', 'like', "%{$filter}%");
                })
            )
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function delete(Catalog $catalog): void
    {
        DB::transaction(function () use ($catalog): void {
            ProductVariant::query()->where('pricing_catalog_id', $catalog->id)->update(['reference_outdated' => true, 'pricing_catalog_id' => null]);
            $catalog->delete();
        });
    }
}
