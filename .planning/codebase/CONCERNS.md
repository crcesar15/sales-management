# Codebase Concerns

**Analysis Date:** 2026-06-21

## Tech Debt

### Broken `AuthServiceProvider` — Missing Imports

- Issue: `app/Providers/AuthServiceProvider.php` registers `StockAdjustment::class => StockAdjustmentPolicy::class` in the `$policies` array, but neither `App\Models\StockAdjustment` nor `App\Policies\StockAdjustmentPolicy` is imported. PHPStan reports `Class App\Providers\StockAdjustment not found` and `Class App\Providers\StockAdjustmentPolicy not found`.
- Files: `app/Providers/AuthServiceProvider.php:34`
- Impact: The policy mapping is effectively dead code — Laravel auto-discovers policies elsewhere, so authorization still works, but the explicit mapping is misleading and the file fails static analysis. Any developer reading the provider will assume the mapping is authoritative.
- Fix approach: Add the missing `use App\Models\StockAdjustment;` and `use App\Policies\StockAdjustmentPolicy;` imports, OR remove the line entirely since Laravel auto-discovers policies (per `authorization.md` rules: "Auto-discovered (not registered in `AuthServiceProvider`)"). The latter is consistent with how every other policy in the app works.

### Pre-Existing PHPStan Errors (616 Total)

- Issue: `composer lint` runs PHPStan at level 8 (strictest) with Larastan, but the codebase currently emits **616 errors** (verified via `vendor/bin/phpstan analyse`). Breakdown by identifier:
  - `property.notFound` — 464 (overwhelmingly `$this->service`/`$this->store` in Pest tests using PHPUnit-style property access without `uses()->property` wiring)
  - `property.nonObject` — 116 (accessing properties on nullable models, e.g. `Product|null`)
  - `missingType.iterableValue` — 13 (test helper arrays without value type docs)
  - `method.nonObject` — 7 (`->toISOString()` called on string-cast dates, `->getMedia()` on `Model|null`)
  - `method.notFound` — 2 (`CashRegisterShiftResource::relationLoaded()` does not exist on `JsonResource`)
  - `class.notFound` — 2 (the `AuthServiceProvider` issue above)
  - Others — 12 (offset access, nullsafe, unresolvable types)
- Files: `tests/Unit/Services/Inventory/StockAlertServiceTest.php` (most of the 464 `property.notFound`), `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php`, `app/Http/Resources/StockTransfer/StockTransferResource.php`, `app/Http/Resources/Catalog/CatalogResource.php`, `app/Http/Resources/Catalog/CatalogVariantResource.php`, `app/Http/Resources/Product/ProductVariantResource.php`, `app/Http/Resources/SalesOrder/SalesOrderResource.php`, `app/Http/Controllers/InventoryController.php`, `app/Services/SalesOrderService.php`, `app/Services/CashRegisterShiftService.php`, `app/Providers/AuthServiceProvider.php`
- Impact: `composer lint` is configured to run after every PHP edit (PostToolUse hook), so every commit surfaces these errors. Developers likely either ignore them or the hook noise masks real new violations. The 464 test errors stem from Pest tests using `$this->service`/`$this->store` properties without declaring them via `uses()` or PHPDoc `@property`, which blocks strict typing of test fixtures.
- Fix approach:
  1. Fix the `StockAlertServiceTest` by declaring properties on the Pest test case (e.g. `uses()->property('service', StockAlertService::class)`) or via a `@property` PHPDoc on a base test class. This alone removes ~450 errors.
  2. Fix nullable model access in resources/controllers with `?->` or null guards (`InventoryController.php:87-110`).
  3. Fix `CashRegisterShiftResource` to use `$this->resource->relationLoaded(...)` instead of `$this->relationLoaded(...)`.
  4. Fix the `AuthServiceProvider` imports.
  5. Fix `status->value` on string (see Known Bugs section).

### Pre-Existing TypeScript Errors (9 Non-PrimeVue, 8 PrimeVue Module Declarations)

- Issue: `npm run type-check` (`vue-tsc --noEmit`) emits 17 errors total. 8 are PrimeVue internal module declaration issues (`primevue/confirmationeventbus`, `primevue/dynamicdialogeventbus`, `primevue/overlayeventbus`, `primevue/toasteventbus`) — these are PrimeVue 4 package-level declaration gaps. The 9 application errors are real bugs:
  - `resources/js/Composables/useRoleClient.ts:1` — imports `Role` from `@app-types/role-types` but the type is named `RoleResponse` (or similar); no `Role` export exists.
  - `resources/js/Pages/ActivityLogs/Index.vue:90,91` — `response.data` is `unknown` (axios response not typed).
  - `resources/js/Pages/Inventory/Show/Components/UnitsTab.vue:234,238` — passing `Record<string, unknown>` to a parameter expecting `RequestPayload | undefined` (FormData). The Inertia `router` call signature mismatch.
  - `resources/js/Pages/Products/Create/Index.vue:170`, `resources/js/Pages/Products/Edit/Index.vue:153` — same `RequestPayload` mismatch.
  - `resources/js/Pages/ReceptionOrders/Create/Index.vue:84` — accesses `item.catalog` which does not exist on `PurchaseOrderLineItem` type.
  - `resources/js/Pages/StockAdjustments/Create/Index.vue:457` — calls `.toISOString()` on `Date | Date[] | (Date | null)[]` (DatePicker value can be array).
- Files: listed above
- Impact: `npm run type-check` fails, so type safety is eroded. New errors introduced in the same files will be invisible against the pre-existing baseline.
- Fix approach:
  1. Fix `useRoleClient.ts` import to `RoleResponse`.
  2. Type axios responses with `AxiosResponse<ActivityLogResponse[]>` in the composable.
  3. Use `FormData` or cast the Inertia router payload correctly, or update the composable signatures to accept plain objects.
  4. Add `catalog?` to `PurchaseOrderLineItem` type or guard with optional chaining.
  5. Normalize DatePicker value to a single `Date | null` before calling `.toISOString()`.

### Models Using `$casts` Property Instead of `casts()` Method

- Issue: `.claude/rules/laravel-backend.md` mandates the `casts()` method (not the `$casts` property), but two models still use the deprecated property form.
- Files: `app/Models/ProductVariant.php:41` (`protected $casts = [...]`), `app/Models/ProductVariantUnit.php:27` (`protected $casts = [...]`)
- Impact: Inconsistent with the other 28 models; violates the documented convention. Both are also flagged because `ProductVariant` is a high-traffic model.
- Fix approach: Convert each `protected $casts = [...]` to `protected function casts(): array { return [...]; }` mirroring `app/Models/User.php:99` and siblings.

### Models Missing `LogsActivity` Trait

- Issue: `AGENTS.md` and `laravel-backend.md` state "ALL models use the `LogsActivity` trait", but 7 models omit it.
- Files: `app/Models/PendingMediaUpload.php`, `app/Models/ProductOption.php`, `app/Models/ProductOptionValue.php`, `app/Models/ProductVariantOptionValue.php`, `app/Models/PurchaseOrderProduct.php`, `app/Models/ReceptionOrderProduct.php`, `app/Models/StockTransferItem.php`
- Impact: No audit trail for these entities. Purchase-order/reception-order line items (`PurchaseOrderProduct`, `ReceptionOrderProduct`) and stock transfer items are auditable business records that silently lose history.
- Fix approach: Add `use LogsActivity;` and the standard `getActivitylogOptions()` method to each. For the pure pivot-style models (`ProductVariantOptionValue`), confirm with the team whether audit logging is desired for pivot changes.

### Dead Code — `$request->validated()` Return Value Ignored

- Issue: Multiple API controllers call `$request->validated()` for its side effects (it has none) but discard the return value, then read raw input via `$request->input()` / `$request->array()`.
- Files: `app/Http/Controllers/Api/RoleController.php:21,54,77`, `app/Http/Controllers/Api/MeasurementUnitController.php:22`
- Impact: Misleading — implies validation result is used. Encourages future contributors to read unvalidated input. The actual values used (`$request->input('name')`, `$request->array('permissions')`) bypass the typed return of `validated()`.
- Fix approach: Remove the dead `$request->validated();` calls and use `$validated = $request->validated();` then pass `$validated` to the service, OR just delete the dead calls if the Form Request is only for authorization/validation side effects.

### Duplicate FIFO Deduction Logic

- Issue: Two implementations of FIFO stock deduction exist with subtly different behavior.
  - `app/Services/FifoStockDeductionService.php` — used by `SalesOrderService::deductForOrder()`, throws `InvalidArgumentException` on insufficient stock, locks batches via `lockForUpdate()`.
  - `app/Services/BatchService::deductFIFO()` and `deductFIFOForTransfer()` (`app/Services/BatchService.php:100,133`) — used by `StockTransferService`, throws `RuntimeException` on insufficient stock, locks via `getAvailableBatches()` (which also uses `lockForUpdate()`).
- Files: `app/Services/FifoStockDeductionService.php`, `app/Services/BatchService.php:100-167`
- Impact: Behavioral drift risk — the two paths throw different exception types (`InvalidArgumentException` vs `RuntimeException`), increment different counter columns (`sold_quantity` in both, but `deductFIFOForTransfer` increments `transferred_quantity`), and have separate "closed" status logic. The `BatchService` version is documented as a method on a service that owns batches; the `FifoStockDeductionService` is dedicated. Consolidating is safer.
- Fix approach: Have `BatchService::deductFIFO*` delegate to `FifoStockDeductionService` (or extract a shared FIFO helper), and standardize on `InvalidArgumentException` per `laravel-backend.md` rules.

### `CashRegisterShiftService` Lacks `TRANSITION_MAP`

- Issue: `laravel-backend.md` requires services with status transitions to define a `TRANSITION_MAP` constant + `validateTransition()` method. `PurchaseOrderService`, `SalesOrderService`, and `StockTransferService` all follow this. `CashRegisterShiftService` does not — it uses inline `if ($shift->status->value !== CashRegisterShiftStatus::OPEN->value)` checks scattered across `open()`, `close()`, `forceClose()`, and `addMovement()`.
- Files: `app/Services/CashRegisterShiftService.php:67,102,136,170`
- Impact: Transition rules are not centrally documented; adding a new status or transition requires touching every method. Harder to audit valid state machines.
- Fix approach: Add a `TRANSITION_MAP` constant (e.g. `['open' => ['closed', 'forced_close'], 'closed' => [], 'forced_close' => []]`) and a `validateTransition()` method, replacing the inline checks.

## Known Bugs

### Hardcoded `taxRate = 0` in Sales Order Frontend (TODO)

- Symptoms: The create/edit sales order forms always compute `taxAmount = 0` in the client, regardless of the configured `tax_rate` setting. The displayed total omits tax entirely.
- Files: `resources/js/Pages/SalesOrders/Create/Index.vue:53-55`, `resources/js/Pages/SalesOrders/Edit/Index.vue:83-85`
- Trigger: Open the sales order create or edit page with any non-zero `tax_rate` setting configured.
- Workaround: None on the frontend. The backend (`app/Services/SalesOrderService.php:86,161`) correctly reads `Setting::get('tax_rate', 0)` and computes `($subTotal - $discount) * ($taxRate / 100)`, so saved orders have correct tax. But the live preview the user sees before saving is wrong, and the `payments must equal order total` validation at `SalesOrders/Create/Index.vue:68` will reject valid payments because it compares against the untaxed total.
- Note: There is also a units mismatch — the frontend multiplies by `taxRate` directly (`* taxRate`) while the backend divides by 100 (`* ($taxRate / 100)`). Even if the frontend read the setting, it would need to apply `/100` to match.

### `CashRegisterShiftResource` Calls Nonexistent Methods

- Symptoms: `relationLoaded()` and `$movements` are called on `$this` (the `JsonResource`), but these methods/properties exist on the underlying Eloquent model, not the resource wrapper.
- Files: `app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php:36-37`
- Trigger: Any API or Inertia response that serializes a `CashRegisterShift` with the `movements` relation loaded.
- Workaround: The resource uses `?? null` and `?: []` fallbacks pervasively, so runtime may not crash, but the `movements` field will always resolve to `[]` (the `relationLoaded` call returns null/throws, falling through to the empty array). Movements are silently dropped from API responses.
- Fix approach: Use `$this->resource->relationLoaded('movements')` and `$this->resource->movements`, or better, use `$this->whenLoaded('movements', fn () => CashRegisterMovementResource::collection($this->movements)->resolve(), [])` matching the pattern in other resources.

### `StockTransferResource` Calls `toISOString()` on String Dates

- Symptoms: `cancelled_at`/`completed_at` are cast as `'datetime'` (Carbon), but `created_at`/`updated_at` are cast as `'datetime:Y-m-d H:i'` (string format). The resource calls `->toISOString()` on all four, which fails for the string-cast ones.
- Files: `app/Http/Resources/StockTransfer/StockTransferResource.php:26-28`
- Trigger: Any API response serializing a `StockTransfer`.
- Workaround: None — `created_at`/`updated_at` will either throw or return null depending on PHP error handling.
- Fix approach: Either change the model casts in `app/Models/StockTransfer.php:66-69` to plain `'datetime'` for all four fields, or in the resource use `$transfer->getAttribute('created_at')` already returns a string — wrap with Carbon or format directly.

### `ProductVariant` Has No `sku` Field (Referenced in Resource)

- Symptoms: `StockTransferResource` accesses `$item->productVariant?->sku`, but `ProductVariant` has no `sku` column or accessor. PHPStan reports `Access to an undefined property App\Models\ProductVariant::$sku`.
- Files: `app/Http/Resources/StockTransfer/StockTransferResource.php:56`
- Trigger: Any stock transfer API response with items.
- Workaround: The value will be `null`.
- Fix approach: Use `$item->productVariant?->identifier` (the actual field) or add a `sku` accessor if SKU is a derived concept.

### `status->value` Access on String-Cast Status

- Symptoms: Several services/resources call `->value` on status fields that are stored/cast as strings, not enums.
- Files: `app/Http/Resources/SalesOrder/SalesOrderResource.php:27-28`, `app/Http/Resources/SalesOrder/SalesOrderPayment/SalesOrderPaymentResource.php:22`, `app/Services/SalesOrderService.php:159,215,286,289`, `app/Services/CashRegisterShiftService.php:67,102,136,170`, `app/Http/Controllers/SalesOrderController.php:102`, `app/Http/Requests/SalesOrders/TransitionStatusRequest.php:51`
- Impact: Mixed casting — `SalesOrder` casts `status` to `SalesOrderStatus::class` enum (so `->value` works), but `CashRegisterShift` casts `status` to `CashRegisterShiftStatus::class` too. The PHPStan errors on these lines suggest that in some code paths the value is a string (e.g. when read from a query result or pivot). Inconsistent — some comparisons use `$order->status === SalesOrderStatus::DRAFT->value` (string compare against enum-cast property, which after casting is an enum, so this comparison is always false unless the cast is bypassed). See `app/Services/SalesOrderService.php:152,282` for the string-compare pattern.
- Workaround: None.
- Fix approach: Standardize — either always compare enum-to-enum (`$order->status === SalesOrderStatus::DRAFT`) or always string-to-string. Given the casts are in place, prefer enum comparisons and remove `->value` on the model side. Keep `->value` only when writing to the DB or comparing against raw input strings.

### `Api/VendorsController` and `Api/PurchaseOrdersController` Use `$request->all()` (Mass Assignment)

- Symptoms: These API controllers pass `$request->all()` directly to `create()`/`update()`, bypassing the `validated()` convention and exposing any fillable column to client-controlled values.
- Files: `app/Http/Controllers/Api/VendorsController.php:66,77` (with `// TODO Develop formRequest` and `// @phpstan-ignore-next-line` comments), `app/Http/Controllers/Api/PurchaseOrdersController.php:57,64`
- Trigger: Any authenticated API call to `POST /api/v1/vendors`, `PUT /api/v1/vendors/{vendor}`, `POST /api/v1/purchase-orders`, `PUT /api/v1/purchase-orders/{order}`.
- Impact: Mass assignment vulnerability. A client can submit fields not intended for mass update (e.g. `id`, timestamps, `status` overrides) and they will be written if the model has them in `$fillable`. The `TODO` comments confirm the developer knew this was incomplete.
- Workaround: None.
- Fix approach: Create `app/Http/Requests/Api/Vendors/StoreVendorRequest.php`, `UpdateVendorRequest.php`, `app/Http/Requests/Api/PurchaseOrders/StorePurchaseOrderRequest.php`, `UpdatePurchaseOrderRequest.php` mirroring the web Form Requests in `app/Http/Requests/Vendors/` and `app/Http/Requests/PurchaseOrders/`, then replace `$request->all()` with `$request->validated()`.

### `Api/PurchaseOrdersController` Returns Raw Eloquent Models

- Symptoms: `show()`, `store()`, `update()` return `new JsonResponse($order, ...)` / `response()->json($order, ...)` instead of using a `PurchaseOrderResource`, exposing internal model structure (all attributes, no consistent shape, no relationship control).
- Files: `app/Http/Controllers/Api/PurchaseOrdersController.php:52,59,66`
- Trigger: Any API call to the purchase orders endpoints.
- Impact: Inconsistent API contract — every other API controller uses Resources. Internal fields leaked. No `whenLoaded` relationship gating.
- Fix approach: Create `app/Http/Resources/PurchaseOrder/PurchaseOrderResource.php` (and Collection) and return `(new PurchaseOrderResource($order))->response()->setStatusCode(201)`.

### `ApiCollection` Drops Pagination Metadata

- Symptoms: `app/Http/Resources/ApiCollection.php` returns only `['data' => $this->collection]` with no `meta` block. Every other collection in the app overrides `paginationInformation()` or manually builds `meta` with `current_page`/`last_page`/`per_page`/`total`.
- Files: `app/Http/Resources/ApiCollection.php`, used by `app/Http/Controllers/Api/VendorsController.php:50`, `app/Http/Controllers/Api/PurchaseOrdersController.php:47`, `app/Http/Controllers/Api/MeasurementUnitController.php:43`, `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/SettingsController.php`, `app/Http/Controllers/Api/PermissionsController.php`, `app/Http/Controllers/Api/BrandController.php`, `app/Http/Controllers/Api/CategoryController.php`
- Impact: Frontend DataTables expecting `{data, meta}` for lazy pagination break on these endpoints — there is no `meta.last_page`/`meta.total`. The frontend composables likely compensate, but the API contract is inconsistent.
- Fix approach: Add a `paginationInformation()` override or a manual `meta` block matching `app/Http/Resources/User/UserCollection.php:35`.

### `useApi.ts` Sets `X-XSRF-TOKEN` Header to a DOM Element

- Symptoms: `resources/js/Composables/useApi.ts:12` sets `"X-XSRF-TOKEN": document.head.querySelector('meta[name="csrf-token']")`. `querySelector` returns an `HTMLMetaElement` (or null), not a string. The header name `X-XSRF-TOKEN` expects the decoded value of the `XSRF-TOKEN` cookie, not the Laravel CSRF token from the `csrf-token` meta tag (those are different tokens). Additionally, `withXSRFToken: true` is already set on the same axios instance, which automatically attaches the `X-XSRF-TOKEN` header from the cookie.
- Files: `resources/js/Composables/useApi.ts:8-14`
- Trigger: Every API call via `useApi()`.
- Impact: The `X-XSRF-TOKEN` header is sent as `[object HTMLMetaElement]` (or null), which Laravel will reject or ignore. The automatic `withXSRFToken: true` likely saves the day, but the explicit header is broken and confusing. If a future axios version stops auto-attaching, all API calls will fail CSRF.
- Fix approach: Delete the `headers` block entirely (rely on `withXSRFToken: true` + `withCredentials: true`), OR if a manual CSRF header is desired, use `"X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content` (note: `X-CSRF-TOKEN` with the Laravel CSRF token, not `X-XSRF-TOKEN`).

### `storage/logs/laravel.log` Permission Issues (Documented)

- Symptoms: Tests using `get()`/`post()` (non-JSON) for forbidden assertions can fail because Laravel attempts to write to `storage/logs/laravel.log` during the request lifecycle and the file is not writable by the test process.
- Files: `tests/Feature/BrandTest.php`, `tests/Feature/CategoryTest.php`, `tests/Feature/MeasurementUnitTest.php`, `tests/Feature/CatalogTest.php` (mix of `getJson()` for `index` forbidden, but `post()`/`put()`/`delete()` for store/update/destroy forbidden)
- Trigger: Running the test suite when `storage/logs/laravel.log` is owned by `www-data` and the test runs as another user.
- Workaround: Documented in `.claude/rules/testing.md` — use `getJson()`/`postJson()` etc. for forbidden assertions. Many tests still use non-JSON verbs.
- Fix approach: Convert all `assertForbidden()` assertions to use the `*Json()` variants of the HTTP methods.

### Gigantic Log Files

- Symptoms: `storage/logs/laravel.log` is 10 MB and `storage/logs/browser.log` is **310 MB** (325 MB total in the logs directory).
- Files: `storage/logs/laravel.log`, `storage/logs/browser.log`
- Impact: Repository bloat (these are gitignored but consume disk), slow log inspection, and the `browser.log` size suggests a frontend is logging verbosely in production/dev without rotation.
- Fix approach: Configure log rotation (`config/logging.php` daily driver), investigate why `browser.log` is 310 MB (likely a Playwright/Dusk or a `console.log` capture running unbounded), and add a `.gitignore` cleanup or rotation for `browser.log`.

## Security Considerations

### API Authorization Gaps — 5 Controllers Have No `authorize()` Calls

- Risk: Five API controllers expose endpoints protected only by `auth:sanctum` (any authenticated user) with no per-permission checks. Any logged-in user, regardless of role, can access these endpoints.
- Files:
  - `app/Http/Controllers/Api/ActivityLogController.php` (0 `authorize` calls)
  - `app/Http/Controllers/Api/BatchesController.php` (0 `authorize` calls)
  - `app/Http/Controllers/Api/PermissionsController.php` (0 `authorize` calls — exposes all permissions/roles)
  - `app/Http/Controllers/Api/PurchaseOrdersController.php` (0 `authorize` calls)
  - `app/Http/Controllers/Api/SettingsController.php` (0 `authorize` calls — exposes/writes application settings)
- Current mitigation: `auth:sanctum` middleware ensures only authenticated users reach these endpoints. But a low-privilege `salesman` user can read/modify settings, list all batches, view activity logs, etc.
- Recommendations: Add `$this->authorize(PermissionsEnum::X->value, auth()->user())` to each action per `routes-and-api.md` conventions. `PermissionsController` and `SettingsController` are especially sensitive — consider restricting to admin role.

### API Mass Assignment — `$request->all()` Passed to `create()`/`update()`

- Risk: Unvalidated user input flows directly into Eloquent mass assignment.
- Files: `app/Http/Controllers/Api/VendorsController.php:66,77`, `app/Http/Controllers/Api/PurchaseOrdersController.php:57,64`
- Current mitigation: Models use `$fillable` (not `$guarded`), so only listed columns are writable. But `$fillable` is broad for many models, and there is no validation layer to reject malformed or out-of-domain values before persistence.
- Recommendations: Create API Form Requests for Vendors and PurchaseOrders (mirroring web Form Requests) and use `$request->validated()`.

### Missing API Form Requests — 17 of 24 Modules Have No API Validation

- Risk: API endpoints for 17 modules accept raw `Request $request` with no dedicated validation class. Only 7 modules have API Form Requests: `ActivityLogs`, `Brands`, `Categories`, `Customers`, `MeasurementUnits`, `Roles`, `Users`.
- Files: `app/Http/Requests/Api/` contains only those 7 subdirectories. Missing: `PurchaseOrders`, `Vendors`, `Settings`, `Permissions`, `Batches`, `StockTransfers`, `CashRegisters`, `CashRegisterShifts`, `CashRegisterMovements`, `SalesOrders`, `ReceptionOrders`, `Catalog`, `Products`, `Inventory`, `Stores`.
- Current mitigation: Some controllers call `$request->validated()` on a web Form Request type-hint, but most just use `Request`.
- Recommendations: Generate API Form Requests for each module's `store`/`update` endpoints, mirroring the web versions with `PermissionsEnum`-based authorization.

### `orderBy` Injection Risk — 11 Services Pass User Input to `->orderBy()` Without Whitelisting

- Risk: User-supplied `order_by` query parameter flows directly to `->orderBy($orderBy, $orderDirection)` in 11 services. Laravel does not parameterize column names — a malicious `order_by` value could potentially inject SQL (depending on DB driver) or, more practically, be used to sort by sensitive columns (e.g. `password`, `email`) to infer data via ordering side-channels.
- Files: `app/Services/RoleService.php:28`, `app/Services/CategoryService.php:32`, `app/Services/VariantService.php:95`, `app/Services/VendorService.php:34`, `app/Services/StoreService.php:37`, `app/Services/UserService.php:38`, `app/Services/MeasurementUnitService.php:33`, `app/Services/BrandService.php:32`, `app/Services/CustomerService.php:31`, `app/Services/ProductService.php:54`, `app/Services/CatalogService.php:164`
- Current mitigation: `StockService` and `VariantService` (line 132+) use a `SORT_COLUMN_MAP` whitelist — the safe pattern. `CatalogService.php:164` uses a `match()` expression — also safe. The other 11 do not.
- Recommendations: Add a `SORT_COLUMN_MAP` constant (or `match()` whitelist) to each service, defaulting unknown values to a safe column. Validate `orderDirection` to `asc`/`desc` only.

### Broad `catch (Exception $e)` Swallows All Errors

- Risk: 12 web controllers catch the base `Exception` class (not just `InvalidArgumentException` per conventions) and pipe `$e->getMessage()` directly to the user via `redirect()->back()->with('error', ...)`.
- Files: `app/Http/Controllers/VendorsController.php:87`, `app/Http/Controllers/CatalogController.php:144`, `app/Http/Controllers/MeasurementUnitController.php:73`, `app/Http/Controllers/CustomerController.php:87`, `app/Http/Controllers/ProductVariantController.php:31,42`, `app/Http/Controllers/ProductController.php:163`, `app/Http/Controllers/ProductOptionController.php:29,55`, `app/Http/Controllers/OptionValueController.php:38`, `app/Http/Controllers/BrandController.php:73`, `app/Http/Controllers/CategoryController.php:73`
- Current mitigation: None — the raw exception message is shown to the user.
- Recommendations: Per `laravel-backend.md`, catch `InvalidArgumentException` specifically for business rule violations. Let other exceptions propagate to the global handler (which can log and show a generic error). Never expose `$e->getMessage()` for non-business exceptions — they may contain SQL, file paths, or stack details.

## Performance Bottlenecks

### N+1 Query Risk in `ReceptionOrderResource`

- Problem: The resource iterates `$reception->lineItems->map(...)` and accesses `$item->productVariant->product->measurementUnit` and `$item->catalogEntry->unit` per item.
- Files: `app/Http/Resources/ReceptionOrder/ReceptionOrderResource.php:79-110`
- Cause: `ReceptionOrderService.php:27` eager loads `lineItems.productVariant.product` but NOT `lineItems.productVariant.product.measurementUnit` or `lineItems.catalogEntry.unit`. Each line item triggers 2+ extra queries.
- Improvement path: Add `lineItems.productVariant.product.measurementUnit`, `lineItems.catalogEntry.unit` to the `->with([...])` call in `ReceptionOrderService::list()` and `show()`.

### N+1 Query Risk in `SalesOrderResource`

- Problem: The resource accesses `$this->items` (a relation) via `SalesOrderItemResource::collection($this->items)`, but `SalesOrderService::list()` eager loads only `['customer', 'user', 'store', 'cashRegisterShift']` — NOT `items`.
- Files: `app/Http/Resources/SalesOrder/SalesOrderResource.php:54`, `app/Services/SalesOrderService.php:39`
- Cause: Each sales order in a paginated list lazy-loads its items on serialization. For a page of 20 orders with 5 items each, that is 20 extra queries (plus any per-item relation access in `SalesOrderItemResource`).
- Improvement path: Add `'items'` (and `items.productVariant.product`) to the eager load in `SalesOrderService::list()`.

### `Setting::get()` Uses `Cache::tags()` on Non-Tag-Supporting Driver

- Problem: `app/Models/Setting.php:41,59,69` and `app/Services/SettingsService.php:29` call `Cache::tags(['settings'])->...`. Cache tags are only supported by Redis, Memcached, DynamoDB, and database (via `cache` table) drivers. The default driver is `file` (`.env.example: CACHE_DRIVER=file`), and tests use `array`. Calling `Cache::tags()` on the `file` or `array` driver throws a `BadMethodCallException` at runtime.
- Files: `app/Models/Setting.php:41,59,69`, `app/Services/SettingsService.php:29`, `config/cache.php:20` (`'default' => env('CACHE_DRIVER', 'file')`)
- Cause: Mismatch between cache API used and configured driver. This means `Setting::get()` will crash on any environment using the default `file` driver unless `CACHE_DRIVER=redis` (or similar) is set.
- Improvement path: Either (a) remove `Cache::tags()` and use plain `Cache::rememberForever()` with manual key-based invalidation, or (b) document that Redis is required and update `.env.example`. Option (a) is lower-risk. Replace `Cache::tags(['settings'])->flush()` with `Cache::forget("settings.{$key}")` and `Cache::forget("settings.group.{$group}")` on writes.

### Stock Deduction Queries `ProductVariant::where('id', $variantId)->firstOrFail()` After Transaction

- Problem: After every stock deduction, the code re-fetches the `ProductVariant` via `ProductVariant::where('id', $variantId)->firstOrFail()->recalculateStock()`. The model is already loaded in context (or could be passed in), and `firstOrFail` adds a query plus a potential 404 if the variant was deleted mid-transaction.
- Files: `app/Services/StockAdjustmentService.php:86`, `app/Services/BatchService.php:63,129,161`, `app/Services/StockTransferService.php:157`, `app/Services/ReceptionOrderService.php:204`, `app/Services/SalesOrderService.php` (via `FifoStockDeductionService`)
- Cause: Convenience pattern that re-queries instead of reusing the in-memory model. `firstOrFail` also throws `ModelNotFoundException` (404) instead of a business exception when the variant is missing.
- Improvement path: Pass the already-loaded `ProductVariant` instance into `recalculateStock()`, or use `ProductVariant::find($variantId)?->recalculateStock()` with a null check that throws `InvalidArgumentException`.

## Fragile Areas

### `Api/VendorsController` and `Api/PurchaseOrdersController` (Unfinished API Layer)

- Files: `app/Http/Controllers/Api/VendorsController.php`, `app/Http/Controllers/Api/PurchaseOrdersController.php`
- Why fragile: These two controllers are explicitly incomplete — they contain `// TODO Develop formRequest` comments, use `$request->all()`, return raw models via `JsonResponse`, and `PurchaseOrdersController` skips `authorize()` entirely. Any change to the underlying models' `$fillable` instantly changes the API contract. Any new column becomes writable via API.
- Safe modification: Do NOT add new `$fillable` fields to `Vendor` or `PurchaseOrder` without first adding API Form Requests. Treat these controllers as stubs pending completion.
- Test coverage: No dedicated API tests for these endpoints beyond `tests/Feature/UserTest.php` (which tests the User API, not Vendors/PurchaseOrders).

### Sales Order Tax Calculation (Frontend/Backend Drift)

- Files: `resources/js/Pages/SalesOrders/Create/Index.vue:53-55`, `resources/js/Pages/SalesOrders/Edit/Index.vue:83-85`, `app/Services/SalesOrderService.php:255-277`
- Why fragile: The frontend computes tax one way (`* taxRate`), the backend another (`* ($taxRate / 100)`), and the frontend hardcodes `taxRate = 0`. Any fix must touch both layers consistently. The payment validation at `Create/Index.vue:68` compares against the (wrong) frontend total, so enabling tax on the frontend without matching the backend formula will reject valid payments.
- Safe modification: When fixing, update the frontend to read `useAuth().getSetting('sales', 'tax_rate', 0)` AND apply `/100` to match the backend. Test the payment-difference validation with a non-zero tax rate.
- Test coverage: No sales order tests exist (see Test Coverage Gaps).

### `ReceptionOrderController` Claimed Quantities Logic

- Files: `app/Http/Controllers/ReceptionOrderController.php:86,172` (manually builds `$claimedQuantities` via `bcadd`), `app/Services/ReceptionOrderService.php:323`
- Why fragile: The claimed-quantities aggregation is duplicated between the controller (two methods) and the service, using `bcadd` with `(string)` casts. The controller reaches into business logic that belongs in the service. Any change to how claimed quantities are calculated must be applied in three places.
- Safe modification: Extract the claimed-quantities calculation into a single service method and call it from both controller methods.
- Test coverage: No `ReceptionOrders` tests exist.

### Cache Configuration Mismatch

- Files: `app/Models/Setting.php`, `config/cache.php`, `.env.example`
- Why fragile: The `Setting` model assumes a tag-supporting cache driver, but the default is `file`. Deployments that do not explicitly set `CACHE_DRIVER=redis` will crash on the first `Setting::get()` call. The `.env.example` advertises `CACHE_DRIVER=file`, which is non-functional with this code.
- Safe modification: Any change to caching must verify against the actual configured driver. Do not assume Redis in development.
- Test coverage: `tests/Feature/Settings/SettingsTest.php` exists but runs with `CACHE_DRIVER=array` (per `phpunit.xml`), which also does not support tags — so the settings tests may be passing by accident (or the `array` store silently ignores tags in some Laravel versions).

## Scaling Limits

### Session and Cache Default to `file` Driver

- Current capacity: Single-server, local filesystem. Sessions in `storage/framework/sessions/`, cache in `storage/framework/cache/data/`.
- Limit: File-based sessions/cache do not work across multiple web servers (no shared filesystem). The app cannot horizontally scale without switching to Redis/database.
- Scaling path: Set `SESSION_DRIVER=redis` and `CACHE_DRIVER=redis` for multi-server deployment. The `Cache::tags()` usage in `Setting` already requires Redis for correctness.

### Soft-Deleted Records in List Queries

- Current capacity: `list()` methods use `->when($status === 'all', fn ($q) => $q->withTrashed())` — the default `status` filter is `'all'`, which includes soft-deleted records on every page load.
- Limit: As soft-deleted records accumulate, the "all" list grows unbounded. There is no pagination cap on trashed records.
- Scaling path: Default to `'active'` status in list endpoints; require explicit `?status=all` or `?status=archived` to view trashed records.

## Dependencies at Risk

### `tightenco/ziggy` — Route Generation at Runtime

- Risk: Ziggy generates the JS route manifest via `@routes` Blade directive (`resources/views/layouts/app.blade.php`). The manifest is regenerated on every page load (or cached). If routes change without a cache flush, the frontend `route()` calls will use stale URLs.
- Impact: 404s or wrong-endpoint calls after route renames.
- Migration plan: Use `php artisan ziggy:generate` to produce a static `resources/js/ziggy.js` during build, and commit or build-step-generate it. This is already supported by Ziggy.

### `moment-timezone` (Legacy Date Library)

- Risk: `moment-timezone` is used via `useDatetimeFormatter` (`resources/js/Composables/`). Moment.js is in maintenance mode (officially deprecated for new projects since 2020). The project already uses Vue 3 + native `Date` in some places, creating two date-handling paths.
- Impact: Bundle size bloat (moment + timezone data). No new features. Future Vue/PrimeVue updates may drop moment compatibility.
- Migration plan: Replace `moment-timezone` with `date-fns` + `date-fns-tz` (tree-shakeable) or native `Intl.DateTimeFormat` for formatting only.

## Missing Critical Features

### No CI/CD Pipeline

- Problem: `commands.md` explicitly states "No Docker, no CI/CD, no Makefile". There is no automated test execution on push, no deployment pipeline.
- Blocks: Reliable shipping. The 616 PHPStan errors and 17 TypeScript errors would be caught by CI; without it, they accumulate. Pre-commit hooks (PostToolUse) only run on edited files.

### No API Rate Limiting Beyond Global `api` Limiter

- Problem: `app/Providers/RouteServiceProvider.php:29` defines a single `api` rate limiter (60/min by user ID or IP) applied to the whole `api` middleware group. There is no per-endpoint throttling (e.g. login attempts are throttled separately in `LoginRequest.php`, but other sensitive endpoints like settings updates, permission assignments, etc. are not).
- Blocks: Brute-force protection on sensitive write endpoints.

### No API Tests for Most Modules

- Problem: Only `tests/Feature/UserTest.php` tests API endpoints (and it tests both web and API). No dedicated API tests for Vendors, PurchaseOrders, Settings, Permissions, Batches, StockTransfers, CashRegisters, CashRegisterShifts, CashRegisterMovements, SalesOrders, ReceptionOrders, Catalog, Products, Inventory, Stores, ActivityLogs, Roles, Brands, Categories, Customers, MeasurementUnits.
- Blocks: Confident refactoring of the API layer. Any change to API serialization, authorization, or validation has no safety net.

## Test Coverage Gaps

### Modules With Zero Test Files

- What's not tested: `CashRegisters`, `CashRegisterShifts`, `Customers`, `Dashboard`, `Gallery`, `Products`, `PurchaseOrders`, `ReceptionOrders`, `SalesOrders`, `StockAdjustments`, `ActivityLogs`, `Roles` (only `RoleTest.php` exists, not in a subdir), `Stores` (has 2 tests), `Brands` (only `BrandTest.php`), `Categories` (only `CategoryTest.php`), `MeasurementUnits` (only `MeasurementUnitTest.php`), `Catalog` (only `CatalogTest.php`), `Vendors` (2 tests), `Batches` (2 tests), `Inventory` (3 tests), `Pos` (1 layout test), `Settings` (1 test), `StockTransfers` (1 test).
- Files: `tests/Feature/` — 23 test files total for 25 page modules.
- Risk: Sales orders, purchase orders, reception orders, cash registers, shifts, products, stock adjustments, and customers have NO tests. These are the core revenue/inventory flows. Any bug in order totals, stock deductions, payment processing, or shift reconciliation will ship undetected.
- Priority: **High** for `SalesOrders`, `PurchaseOrders`, `ReceptionOrders`, `StockAdjustments`, `CashRegisterShifts` (financial impact). **Medium** for `Products`, `Customers`, `CashRegisters` (operational). **Low** for `Dashboard`, `Gallery` (display-only).

### Service-Layer Unit Tests Are Minimal

- What's not tested: Only 2 unit test files exist: `tests/Unit/Models/VendorTest.php` and `tests/Unit/Services/Inventory/StockAlertServiceTest.php`. The 25 services in `app/Services/` are otherwise untested at the unit level. Service methods like `SalesOrderService::calculateTotals()`, `FifoStockDeductionService::deductForOrder()`, `BatchService::deductFIFO()` (the financial core) have no isolated tests.
- Files: `tests/Unit/Services/` contains only `Inventory/StockAlertServiceTest.php`.
- Risk: Business logic bugs (tax calculation, FIFO deduction, discount application, stock recalculation) are only caught if a feature test happens to exercise them end-to-end, and most have no feature tests either.
- Priority: **High** for `SalesOrderService`, `FifoStockDeductionService`, `BatchService`, `StockAdjustmentService` (money + stock). **Medium** for `PurchaseOrderService`, `ReceptionOrderService`, `StockTransferService`, `CashRegisterShiftService`.

### Pest Test Property Access Without `uses()` Wiring

- What's not tested: `tests/Unit/Services/Inventory/StockAlertServiceTest.php` accesses `$this->service` and `$this->store` (464 PHPStan errors), but these properties are never declared via `uses()->property(...)` or a `@property` PHPDoc. The tests likely pass at runtime because Pest dynamically resolves them, but they are not type-safe and any refactor that renames a property will silently break the tests at runtime while PHPStan (if run) reports the error.
- Files: `tests/Unit/Services/Inventory/StockAlertServiceTest.php`
- Risk: Tests appear green but are fragile — the property wiring is implicit. A future Pest or PHP version may stop supporting dynamic property access.
- Priority: **Medium** — fix by adding `uses()->property('service', StockAlertService::class)` etc. in `tests/Pest.php` or a `TestCase` base class.

---

*Concerns audit: 2026-06-21*