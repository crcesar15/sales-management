# Phase 01: Critical Fixes & Refactor - Pattern Map

**Mapped:** 2026-06-21
**Files analyzed:** 52
**Analogs found:** 47 / 52

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|-------------------|------|-----------|----------------|---------------|
| `app/Services/FifoStockDeductionService.php` | service | CRUD/batch-update | `app/Services/BatchService.php` (lines 100-163) | role-match |
| `app/Services/BatchService.php` | service | CRUD/delegate | `app/Services/StockTransferService.php` (lines 126-170) | role-match |
| `app/Services/CashRegisterShiftService.php` | service | state-machine | `app/Services/SalesOrderService.php` (lines 20-308) | role-match |
| `app/Services/StockService.php` | service | list-sort | exact canonical pattern for FIX-13 | exact |
| `app/Services/RoleService.php` | service | list-sort | `app/Services/BrandService.php` | role-match |
| `app/Services/CategoryService.php` | service | list-sort | `app/Services/BrandService.php` | exact |
| `app/Services/VariantService.php` | service | list-sort | `app/Services/StockService.php` | role-match |
| `app/Services/VendorService.php` | service | list-sort/delete-guard | `app/Services/BrandService.php` | exact |
| `app/Services/StoreService.php` | service | list-sort | `app/Services/BrandService.php` | role-match |
| `app/Services/UserService.php` | service | list-sort | `app/Services/BrandService.php` | role-match |
| `app/Services/MeasurementUnitService.php` | service | list-sort/delete-guard | `app/Services/BrandService.php` | exact |
| `app/Services/BrandService.php` | service | list-sort/delete-guard | `app/Services/BrandService.php` | exact |
| `app/Services/CustomerService.php` | service | list-sort/delete-guard | `app/Services/BrandService.php` | exact |
| `app/Services/ProductService.php` | service | list-sort/delete-guard | `app/Services/BrandService.php` | role-match |
| `app/Services/CatalogService.php` | service | list-sort | `app/Services/VariantService.php` (lines 170-189) | role-match |
| `app/Services/ReceptionOrderService.php` | service | CRUD/eager-load | `app/Services/SalesOrderService.php` | role-match |
| `app/Services/SalesOrderService.php` | service | CRUD/eager-load | `app/Services/SalesOrderService.php` | exact |
| `app/Services/SettingsService.php` | service | cache-invalidation | `app/Models/Setting.php` | role-match |
| `app/Services/StockAdjustmentService.php` | service | CRUD/stock | `app/Services/ReceptionOrderService.php` | role-match |
| `app/Services/StockTransferService.php` | service | CRUD/stock | `app/Services/ReceptionOrderService.php` | role-match |
| `app/Http/Controllers/Api/VendorsController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | exact |
| `app/Http/Controllers/Api/PurchaseOrdersController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | role-match |
| `app/Http/Controllers/Api/ActivityLogController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | role-match |
| `app/Http/Controllers/Api/BatchesController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | role-match |
| `app/Http/Controllers/Api/PermissionsController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | role-match |
| `app/Http/Controllers/Api/SettingsController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | role-match |
| `app/Http/Controllers/Api/RoleController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | exact |
| `app/Http/Controllers/Api/MeasurementUnitController.php` | controller | request-response | `app/Http/Controllers/Api/BrandController.php` | exact |
| `app/Http/Requests/Api/Vendors/StoreVendorRequest.php` | form-request | request-response | `app/Http/Requests/Vendors/StoreVendorRequest.php` | exact |
| `app/Http/Requests/Api/Vendors/UpdateVendorRequest.php` | form-request | request-response | `app/Http/Requests/Vendors/StoreVendorRequest.php` | role-match |
| `app/Http/Requests/Api/PurchaseOrders/StorePurchaseOrderRequest.php` | form-request | request-response | `app/Http/Requests/PurchaseOrders/StorePurchaseOrderRequest.php` | exact |
| `app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php` | form-request | request-response | `app/Http/Requests/PurchaseOrders/StorePurchaseOrderRequest.php` | role-match |
| `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php` | resource | transform | `app/Http/Resources/SalesOrder/SalesOrderResource.php` | role-match |
| `app/Http/Resources/StockTransfer/StockTransferResource.php` | resource | transform | `app/Http/Resources/PurchaseOrder/PurchaseOrderResource.php` | role-match |
| `app/Http/Resources/ApiCollection.php` | resource-collection | transform | `app/Http/Resources/User/UserCollection.php` | exact |
| `app/Http/Controllers/VendorsController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | exact |
| `app/Http/Controllers/CatalogController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/MeasurementUnitController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | exact |
| `app/Http/Controllers/CustomerController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/ProductVariantController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/ProductController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/ProductOptionController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/OptionValueController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | role-match |
| `app/Http/Controllers/BrandController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | exact |
| `app/Http/Controllers/CategoryController.php` | controller | request-response | `app/Http/Controllers/BrandController.php` | exact |
| `app/Http/Controllers/ReceptionOrderController.php` | controller | request-response | `app/Http/Controllers/ReceptionOrderController.php` | exact |
| `app/Models/Setting.php` | model | cache-read/write | `app/Models/Setting.php` | exact |
| `app/Providers/AuthServiceProvider.php` | provider | config | `app/Providers/AuthServiceProvider.php` | exact |
| `resources/js/Composables/useApi.ts` | composable | request-response | `resources/js/Composables/useApi.ts` | exact |
| `resources/js/Pages/SalesOrders/Create/Index.vue` | component | event-driven/form | `resources/js/Pages/SalesOrders/Create/Index.vue` | exact |
| `resources/js/Pages/SalesOrders/Edit/Index.vue` | component | event-driven/form | `resources/js/Pages/SalesOrders/Edit/Index.vue` | exact |
| `config/logging.php` | config | config | `config/logging.php` | exact |
| `tests/Unit/Services/Inventory/FifoStockDeductionServiceTest.php` *(new)* | test | unit-test | `tests/Unit/Services/Inventory/StockAlertServiceTest.php` | role-match |
| `tests/Feature/Settings/SettingsCacheTest.php` *(new)* | test | feature-test | `tests/Feature/Settings/SettingsTest.php` | role-match |
| `tests/Feature/CashRegisterShifts/CashRegisterShiftTransitionsTest.php` *(new)* | test | feature-test | `tests/Feature/StockTransfers/StockTransferTest.php` | role-match |

## Pattern Assignments

### `FifoStockDeductionService::deductForOrder()` + new `deductForTransfer()` (FIX-11)

**Analog:** `app/Services/FifoStockDeductionService.php` (lines 1-73) and `app/Services/BatchService.php` (lines 100-163)

**Imports pattern** (lines 1-11):
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use InvalidArgumentException;
```

**Core FIFO pattern** (lines 20-72):
```php
public function deductForOrder(SalesOrder $order): void
{
    $affectedVariantIds = [];

    foreach ($order->items as $item) {
        $baseQuantity = $item->quantity * $item->conversion_factor;

        $batches = Batch::where('product_variant_id', $item->product_variant_id)
            ->where('store_id', $order->store_id)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('remaining_quantity');

        if ($totalAvailable < $baseQuantity) {
            $variant = ProductVariant::find($item->product_variant_id);
            $sku = $variant !== null ? $variant->identifier : "ID {$item->product_variant_id}";
            throw new InvalidArgumentException(
                "Insufficient stock for variant {$sku}: requested {$baseQuantity}, available {$totalAvailable}."
            );
        }

        $remaining = $baseQuantity;
        foreach ($batches as $batch) {
            if ($remaining <= 0) { break; }
            $deduct = min($remaining, (int) $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $deduct);
            $batch->increment('sold_quantity', $deduct);
            $remaining -= $deduct;
        }

        $affectedVariantIds[] = $item->product_variant_id;
    }

    $uniqueVariantIds = array_unique($affectedVariantIds);
    foreach ($uniqueVariantIds as $variantId) {
        $variant = ProductVariant::find($variantId);
        if ($variant) { $variant->recalculateStock(); }
    }
}
```

**Auto-close at zero** (mirror from `BatchService.php:123-127`):
```php
$batch->refresh();
if ($batch->remaining_quantity === 0) {
    $batch->update(['status' => 'closed']);
}
```

**`deductForTransfer()` contract** — NEW method in `FifoStockDeductionService`. Wrap entire body in `DB::transaction(...)` because the caller (`BatchService::deductFIFOForTransfer`) expects self-contained transaction semantics. Increment `transferred_quantity` instead of `sold_quantity`, auto-close at zero, and call `recalculateStock()`.

---

### `BatchService` delegation + dead-code removal (FIX-11)

**Analog:** `app/Services/BatchService.php` (lines 16-175) and `app/Services/StockTransferService.php` (lines 26-31)

**Constructor injection pattern** (mirror `StockTransferService`):
```php
final class BatchService
{
    public function __construct(private readonly FifoStockDeductionService $fifoStockDeductionService) {}
}
```

**Delegate pattern** (replace `deductFIFOForTransfer()` body):
```php
public function deductFIFOForTransfer(int $variantId, int $storeId, int $quantity): void
{
    $this->fifoStockDeductionService->deductForTransfer($variantId, $storeId, $quantity);
}
```

**Deletion target:** Remove `deductFIFO()` (lines 100-131) and private `getAvailableBatches()` (lines 168-174).

---

### `CashRegisterShiftService` `TRANSITION_MAP` + `validateTransition()` (FIX-12)

**Analog:** `app/Services/SalesOrderService.php` (lines 20-26, 301-308)

**Transition map pattern** (lines 20-26):
```php
private const TRANSITION_MAP = [
    'draft' => ['sent', 'paid', 'held', 'cancelled'],
    'held' => ['draft', 'cancelled'],
    'sent' => ['paid', 'cancelled'],
    'paid' => ['cancelled'],
    'cancelled' => [],
];
```

**Validator pattern** (lines 301-308):
```php
private function validateTransition(string $from, string $to): void
{
    $allowed = self::TRANSITION_MAP[$from] ?? [];

    if (! in_array($to, $allowed, true)) {
        throw new InvalidArgumentException("Cannot transition order from {$from} to {$to}.");
    }
}
```

**CashRegisterShift adaptation:** Replace the four inline `$shift->status->value !== CashRegisterShiftStatus::OPEN->value` checks at `CashRegisterShiftService.php:67,102,136,170` with:
```php
private const TRANSITION_MAP = [
    'open' => ['closed', 'forced_close'],
    'closed' => [],
    'forced_close' => [],
];

private function validateTransition(string $from, string $to): void
{
    $allowed = self::TRANSITION_MAP[$from] ?? [];
    if (! in_array($to, $allowed, true)) {
        throw new InvalidArgumentException("Cannot transition shift from {$from} to {$to}.");
    }
}
```

---

### Service `list()` with `SORT_COLUMN_MAP` (FIX-13)

**Analog:** `app/Services/StockService.php` (lines 13-23, 53) and `app/Services/BrandService.php` (lines 17-35)

**Canonical constant pattern** (`StockService.php:17-23`):
```php
private const SORT_COLUMN_MAP = [
    'product_name' => 'products.name',
    'brand_name' => 'brands.name',
    'total_stock' => 'product_variants.stock',
    'identifier' => 'product_variants.identifier',
    'price' => 'product_variants.price',
];
```

**Application in query** (`StockService.php:53`):
```php
$sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'products.name';
```

**Direction validation** (`VariantService.php:94`):
```php
$orderDirection = $config['order_direction'] ?? 'ASC';
```

**Recommended per-service default pattern for the 11 services:**
```php
private const SORT_COLUMN_MAP = [
    'name' => 'name',
    'created_at' => 'created_at',
    'status' => 'status',
];

$sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'created_at';
$orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc'], true)
    ? strtolower($orderDirection)
    : 'asc';
$query->orderBy($sortColumn, $orderDirection);
```

For `CatalogService::listVariants()`/`list()`, use the existing `match()` whitelist at `VariantService.php:171-176` as the analog.

---

### Web controller `catch (InvalidArgumentException $e)` (FIX-14)

**Analog:** `app/Http/Controllers/BrandController.php` (lines 67-78) and `app/Http/Controllers/VendorsController.php` (lines 81-92)

**Current pattern to change** (`BrandController.php:71-75`):
```php
try {
    $this->brandService->delete($brand);
} catch (Exception $e) {
    return redirect()->back()->with('error', $e->getMessage());
}
```

**Target pattern:**
```php
use InvalidArgumentException;

try {
    $this->brandService->delete($brand);
} catch (InvalidArgumentException $e) {
    return redirect()->back()->with('error', $e->getMessage());
}
```

**Services that must switch `Exception` → `InvalidArgumentException` (mirrored from `BrandService.php:59-65`):**
```php
public function delete(Brand $brand): void
{
    if ($brand->hasActiveProducts()) {
        throw new InvalidArgumentException('Cannot delete brand: it is assigned to one or more active products.');
    }

    DB::transaction(fn () => $brand->delete());
}
```

Apply identical conversions to `CategoryService::delete`, `VendorService::delete`, `MeasurementUnitService::delete`, `CustomerService::delete`, `ProductService::delete`.

---

### API controller `authorize()` + resource return (FIX-02, FIX-09)

**Analog:** `app/Http/Controllers/Api/BrandController.php` (lines 46-73) and `app/Http/Controllers/Api/UserController.php` (lines 88-95)

**Authorize pattern** (`BrandController.php:48`):
```php
$this->authorize(PermissionsEnum::BRANDS_VIEW->value, auth()->user());
```

**Store return pattern** (`UserController.php:88-95`):
```php
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->create($request->validated());

    return (new UserResource($user))
        ->response()
        ->setStatusCode(201);
}
```

**PurchaseOrdersController target:** Replace raw `new JsonResponse($order, 200)` / `response()->json($order, 201)` with `(new PurchaseOrderResource($order))->response()->setStatusCode(201)` and add `PurchaseOrderResource` use/import.

---

### API Form Request structure (FIX-01)

**Analog:** `app/Http/Requests/Api/Users/StoreUserRequest.php` (lines 1-46) and `app/Http/Requests/Vendors/StoreVendorRequest.php` (lines 1-38)

**Imports + namespace** (`StoreUserRequest.php:1-9`):
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
```

**Authorize pattern** (`StoreUserRequest.php:16-19` / `StoreVendorRequest.php:13-16`):
```php
public function authorize(): bool
{
    return $this->user()?->can(PermissionsEnum::VENDORS_CREATE->value) ?? false;
}
```

**Rules pattern** (`StoreVendorRequest.php:21-36`):
```php
public function rules(): array
{
    return [
        'fullname' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:vendors,email'],
        'phone' => ['nullable', 'string', 'max:50'],
        'address' => ['nullable', 'string', 'max:500'],
        'details' => ['nullable', 'string', 'max:1000'],
        'status' => ['required', 'string', 'in:active,inactive,archived'],
        'additional_contacts' => ['nullable', 'array'],
        'additional_contacts.*.name' => ['required_with:additional_contacts', 'string', 'max:255'],
        'additional_contacts.*.phone' => ['nullable', 'string', 'max:50'],
        'additional_contacts.*.email' => ['nullable', 'email', 'max:255'],
        'additional_contacts.*.role' => ['nullable', 'string', 'max:100'],
        'meta' => ['nullable', 'array'],
    ];
}
```

For API Vendors, mirror the web request exactly but namespace to `App\Http\Requests\Api\Vendors`. For API PurchaseOrders, mirror `StorePurchaseOrderRequest.php` (lines 22-82) under `App\Http\Requests\Api\PurchaseOrders`.

---

### Resource `whenLoaded()` + pagination `meta` (FIX-03, FIX-04, FIX-05, FIX-10)

**Analog 1 — `whenLoaded` in resources:** `app/Http/Resources/SalesOrder/SalesOrderResource.php` (lines 40-58)

```php
'customer' => $this->whenLoaded('customer', fn () => [
    'id' => $this->customer?->id,
    'display_name' => $this->customer ? trim($this->customer->first_name . ' ' . $this->customer->last_name) : null,
]),
'items' => $this->relationLoaded('items')
    ? SalesOrderItemResource::collection($this->items)->resolve()
    : [],
```

**Fix for `CashRegisterShiftResource.php:37-39`:** Replace the broken `$this->relationLoaded(...)` call with:
```php
'movements' => $this->whenLoaded('movements', fn () => CashRegisterMovementResource::collection($this->movements)->resolve(), []),
```

**Analog 2 — `UserCollection` pagination meta:** `app/Http/Resources/User/UserCollection.php` (lines 17-38)

```php
public function toArray(Request $request): array
{
    return [
        'data' => $this->collection,
        'meta' => [
            'current_page' => $this->resource->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'per_page' => $this->resource->perPage(),
            'total' => $this->resource->total(),
        ],
    ];
}

public function paginationInformation(Request $request, array $paginated, array $default): array
{
    return [];
}
```

**Fix for `ApiCollection.php`:** Apply the same `toArray()` + `paginationInformation()` override so every consumer of `ApiCollection` (Vendors, PurchaseOrders, MeasurementUnits, Roles, Permissions, Settings, ActivityLogs, Brands, Categories) returns `{data, meta}`.

**Date-cast fix for `StockTransferResource.php:28`:** The model casts `created_at`/`updated_at` as formatted strings. Use `getAttribute()` or cast to Carbon:
```php
'created_at' => $transfer->getAttribute('created_at') ? Carbon::parse($transfer->getAttribute('created_at'))->toISOString() : null,
'updated_at' => $transfer->getAttribute('updated_at') ? Carbon::parse($transfer->getAttribute('updated_at'))->toISOString() : null,
```

**Identifier fix for `StockTransferResource.php:56`:** Replace `$item->productVariant?->sku` with `$item->productVariant?->identifier`.

---

### `Setting` cache `Cache::rememberForever()` + key invalidation (FIX-18)

**Analog:** `app/Models/Setting.php` (lines 39-73)

**Current `Cache::tags()` pattern to replace** (`Setting.php:41-44`):
```php
$value = Cache::tags(['settings'])->rememberForever(
    "settings.{$key}",
    fn () => self::where('key', $key)->value('value')
);
```

**Target pattern:**
```php
use Illuminate\Support\Facades\Cache;

public static function get(string $key, mixed $default = null): mixed
{
    $value = Cache::rememberForever(
        "settings.{$key}",
        fn () => self::where('key', $key)->value('value')
    );

    if ($value === null) {
        return $default;
    }

    return self::castValue($key, $value);
}

public static function set(string $key, mixed $value): void
{
    $setting = self::where('key', $key)->first();
    if ($setting === null) {
        throw new InvalidArgumentException("Setting {$key} not found.");
    }
    $setting->update(['value' => (string) $value]);
    Cache::forget("settings.{$key}");
    Cache::forget("settings.group.{$setting->group}");
}

public static function group(string $group): array
{
    return Cache::rememberForever(
        "settings.group.{$group}",
        fn () => self::where('group', $group)->pluck('value', 'key')->toArray()
    );
}
```

**SettingsService invalidation** (`SettingsService.php:29`): Replace `Cache::tags(['settings'])->flush()` with a loop that forgets both `settings.{$key}` and `settings.group.{$group}` for every key written.

**Api\SettingsController** (`SettingsController.php:37`): Replace `Cache::forget('settings')` with explicit per-key + per-group forgets.

---

### Frontend tax mirroring (FIX-07)

**Analog:** `resources/js/Pages/SalesOrders/Create/Index.vue` (lines 50-55) and `app/Services/SalesOrderService.php` (lines 255-277)

**Backend formula** (`SalesOrderService.php:269-270`):
```php
$taxAmount = round(($subTotal - $discount) * ($taxRate / 100), 2);
$total = round($subTotal - $discount + $taxAmount, 2);
```

**Frontend target** (`Create/Index.vue:50-55` and `Edit/Index.vue:80-85`):
```typescript
import { useAuth } from "@/Composables/useAuth";

const { getSetting } = useAuth();
const taxRate = computed(() => Number(getSetting("sales", "tax_rate", 0)) / 100);
const taxAmount = computed(() => round((subTotal.value - discountAmount.value) * taxRate.value, 2));
const totalAmount = computed(() => subTotal.value - discountAmount.value + taxAmount.value);
```

Payment-difference validation at line 67/97 stays `Math.abs(paymentsTotal - totalAmount.value) > 0.01` but now compares against the taxed total.

---

### `useApi.ts` CSRF header fix (FIX-08)

**Analog:** `resources/js/Composables/useApi.ts` (lines 7-16)

**Current broken block:**
```typescript
const apiClient = axios.create({
  baseURL: `${window.location.protocol}//${window.location.hostname}/api/`,
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
    "X-XSRF-TOKEN": document.head.querySelector('meta[name="csrf-token"]'),
  },
  withCredentials: true,
  withXSRFToken: true,
});
```

**Target pattern:**
```typescript
const apiClient = axios.create({
  baseURL: `${window.location.protocol}//${window.location.hostname}/api/`,
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
  withCredentials: true,
  withXSRFToken: true,
});
```

Delete the `X-XSRF-TOKEN` key entirely; `withXSRFToken: true` + `withCredentials: true` handle the header automatically.

---

### `AuthServiceProvider` dead mapping removal (FIX-15)

**Analog:** `app/Providers/AuthServiceProvider.php` (lines 30-38)

**Current mapping to remove:**
```php
protected $policies = [
    Brand::class => BrandPolicy::class,
    Category::class => CategoryPolicy::class,
    Customer::class => CustomerPolicy::class,
    MeasurementUnit::class => MeasurementUnitPolicy::class,
    Product::class => ProductPolicy::class,
    StockAdjustment::class => StockAdjustmentPolicy::class,  // <-- remove this line
    User::class => UserPolicy::class,
];
```

Keep the existing imports (StockAdjustment/StockAdjustmentPolicy are already imported); simply delete the mapping entry because Laravel auto-discovers policies per `authorization.md`.

---

### `ReceptionOrderController` claimed-quantities extraction (FIX-16)

**Analog:** `app/Services/ReceptionOrderService.php` (lines 310-328)

**Existing service helper** (`ReceptionOrderService.php:310-328`):
```php
private function getClaimedQuantities(PurchaseOrder $po, ?int $excludeReceptionOrderId = null): array
{
    $receptionOrders = $po->receptionOrders()
        ->where('status', '!=', 'cancelled')
        ->when($excludeReceptionOrderId, fn ($q) => $q->where('id', '!=', $excludeReceptionOrderId))
        ->with('lineItems')
        ->get();

    $quantities = [];

    foreach ($receptionOrders as $receptionOrder) {
        foreach ($receptionOrder->lineItems as $lineItem) {
            $poItemId = (int) $lineItem->purchase_order_item_id;
            $quantities[$poItemId] = bcadd((string) ($quantities[$poItemId] ?? '0'), (string) $lineItem->quantity, 4);
        }
    }

    return $quantities;
}
```

**Target usage in `ReceptionOrderController`:** Make `getClaimedQuantities()` public in the service, then replace the duplicated `bcadd` loops in `create()` and `edit()` with:
```php
$claimedQuantities = $this->receptionService->getClaimedQuantities($purchaseOrder, $receptionOrder?->id);
```

---

### Dead `$request->validated()` removal / capture (FIX-17)

**Analog:** `app/Http/Controllers/Api/MeasurementUnitController.php` (lines 53-72) and `app/Http/Controllers/Api/BrandController.php` (lines 53-72)

**Good pattern — capture and pass:**
```php
public function store(StoreMeasurementUnitRequest $request): JsonResponse
{
    $validated = $request->validated();

    $measurementUnit = DB::transaction(function () use ($validated) {
        return MeasurementUnit::query()->create($validated);
    });

    return response()->json($measurementUnit, 201);
}
```

**Fixes needed:**
- `Api\RoleController::index()` (`RoleController.php:21`): remove the unused `$request->validated();` call.
- `Api\RoleController::store()` (`RoleController.php:54`): capture `$validated = $request->validated();` and use `$validated['name']` / `$validated['permissions'] ?? []`.
- `Api\RoleController::update()` (`RoleController.php:77`): same capture/use.
- `Api\MeasurementUnitController::index()` (`MeasurementUnitController.php:22`): remove unused `$request->validated();`.

---

### N+1 eager-load fixes (FIX-19, FIX-20)

**Analog 1 — `SalesOrderService::list()`** (`SalesOrderService.php:38-39`):
```php
$query = SalesOrder::query()
    ->with(['customer', 'user', 'store', 'cashRegisterShift'])
    ->where('store_id', $actor->stores()->first()->id ?? 0);
```

**Fix:** Add `items` and `items.productVariant.product`:
```php
->with(['customer', 'user', 'store', 'cashRegisterShift', 'items', 'items.productVariant.product'])
```

**Analog 2 — `ReceptionOrderService::list()`** (`ReceptionOrderService.php:27`):
```php
return ReceptionOrder::query()
    ->with(['purchaseOrder', 'vendor', 'store', 'user', 'lineItems.productVariant.product'])
```

**Fix:** Add nested measurement-unit and catalog-entry unit loads:
```php
->with([
    'purchaseOrder',
    'vendor',
    'store',
    'user',
    'lineItems.productVariant.product.measurementUnit',
    'lineItems.catalogEntry.unit',
])
```

---

### `recalculateStock()` re-query fix (FIX-21)

**Analog:** `app/Models/ProductVariant.php` (lines 156-162)

**Current recalculate signature/behavior:**
```php
public function recalculateStock(): self
{
    $this->stock = max(0, (int) $this->batches()->active()->sum('remaining_quantity'));
    $this->save();

    return $this;
}
```

**Target pattern at call sites:** Pass the already-loaded instance instead of re-querying with `firstOrFail()`. For services that only have an ID, use `find()` + `InvalidArgumentException`:
```php
$variant = ProductVariant::find($variantId);
if ($variant === null) {
    throw new InvalidArgumentException("Product variant ID {$variantId} not found.");
}
$variant->recalculateStock();
```

Files to update: `StockAdjustmentService.php:86`, `BatchService.php:63`, `StockTransferService.php:157`, `ReceptionOrderService.php:204`, and the loop in `FifoStockDeductionService.php:65-71`.

---

### Log rotation (FIX-22)

**Analog:** `config/logging.php` (lines 56-76)

**Current `stack` uses `single` (lines 57-61):**
```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single'],
    'ignore_exceptions' => false,
],

'single' => [
    'driver' => 'single',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'replace_placeholders' => true,
],
```

**Target pattern:**
```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['daily'],
    'ignore_exceptions' => false,
],
```

**Browser log bound:** Add a `browser` channel using the `daily` driver pointing at `storage/logs/browser.log` with a limited retention (e.g. 7 days), or configure the Laravel Boost handler to write to that bounded channel. The 310 MB `browser.log` should be truncated/rotated once.

---

### Pest test patterns (Wave 0 tests)

**Analog:** `tests/Feature/StockTransfers/StockTransferTest.php` (lines 1-405) and `tests/Pest.php` (lines 1-20)

**Pest setup pattern** (`Pest.php:11-20`):
```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature')
    ->beforeEach(function () {
        $test = $this;
        $test->seed(RoleSeeder::class);
        $test->seed(PermissionSeeder::class);
    });
```

**Feature test pattern** (`StockTransferTest.php:18-27`):
```php
beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->storeA = Store::factory()->create(['status' => 'active']);
    // ...
});
```

**Recommended new tests:**
- `tests/Unit/Services/Inventory/FifoStockDeductionServiceTest.php` — test auto-close-at-zero and `InvalidArgumentException` on both sale and transfer paths.
- `tests/Feature/CashRegisterShifts/CashRegisterShiftTransitionsTest.php` — test valid/invalid `TRANSITION_MAP` transitions.
- `tests/Feature/Settings/SettingsCacheTest.php` — test `CACHE_DRIVER=file` `Setting::get()`/`set()` invalidation.
- `tests/Feature/SalesOrders/SalesOrderTaxTest.php` — test `tax_rate=13` end-to-end total.

---

## Shared Patterns

### Authentication & Authorization
**Source:** `app/Http/Controllers/Api/BrandController.php` and `app/Http/Controllers/BrandController.php`
**Apply to:** All API controllers (FIX-02) and all Web controllers
```php
// API
$this->authorize(PermissionsEnum::BRANDS_VIEW->value, auth()->user());

// Web
$this->authorize(PermissionsEnum::BRANDS_VIEW);
```

### Form Request Authorization
**Source:** `app/Http/Requests/Api/Users/StoreUserRequest.php`
**Apply to:** New API Form Requests (FIX-01)
```php
public function authorize(): bool
{
    return $this->user()?->can(PermissionsEnum::XXX_CREATE->value) ?? false;
}
```

### Business Rule Violations
**Source:** `app/Services/SalesOrderService.php`
**Apply to:** All services touched by FIX-12, FIX-14, FIX-21
```php
use InvalidArgumentException;

throw new InvalidArgumentException('Only draft orders can be updated.');
```

### `DB::transaction()` Wrapping
**Source:** `app/Services/SalesOrderService.php` (lines 81-143)
**Apply to:** `FifoStockDeductionService::deductForTransfer`, `CashRegisterShiftService` transitions, service delete guards
```php
return DB::transaction(function () use ($data, $actor): SalesOrder {
    // ...
});
```

### Eager Loading in Service `list()`
**Source:** `app/Services/SalesOrderService.php` (line 39)
**Apply to:** `ReceptionOrderService::list()` (FIX-19) and `SalesOrderService::list()` (FIX-20)
```php
->with(['items', 'items.productVariant.product'])
```

### Resource `whenLoaded` / Collection `meta`
**Source:** `app/Http/Resources/User/UserCollection.php`
**Apply to:** `ApiCollection`, `CashRegisterShiftResource`, `StockTransferResource`
```php
'meta' => [
    'current_page' => $this->resource->currentPage(),
    'last_page' => $this->resource->lastPage(),
    'per_page' => $this->resource->perPage(),
    'total' => $this->resource->total(),
]
```

---

## No Analog Found

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| `app/Http/Requests/Api/PurchaseOrders/UpdatePurchaseOrderRequest.php` | form-request | request-response | No API PurchaseOrders requests exist; mirror web request instead. |
| `tests/Feature/SalesOrders/SalesOrderTaxTest.php` | test | feature-test | No SalesOrders tests exist yet; pattern is inferred from `StockTransferTest.php` + `Pest.php`. |
| `tests/Unit/Services/Inventory/FifoStockDeductionServiceTest.php` | test | unit-test | No service unit tests for FIFO; closest is `StockAlertServiceTest.php`. |

## Metadata

**Analog search scope:** `app/Http/Controllers/`, `app/Http/Controllers/Api/`, `app/Http/Requests/Api/`, `app/Http/Resources/`, `app/Services/`, `app/Models/`, `app/Providers/`, `resources/js/`, `config/`, `tests/`
**Files scanned:** 52
**Pattern extraction date:** 2026-06-21

## PATTERN MAPPING COMPLETE
