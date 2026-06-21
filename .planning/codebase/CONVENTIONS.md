# Coding Conventions

**Analysis Date:** 2026-06-21

## Naming Patterns

**Files (PHP):**
- PascalCase for classes: `BrandService.php`, `StoreBrandRequest.php`, `BrandController.php`, `BrandCollection.php`, `BrandPolicy.php`, `BrandFactory.php`, `PermissionsEnum.php`
- Suffix by layer: `*Service`, `*Controller`, `*Request`, `*Collection` / `*Resource`, `*Policy`, `*Factory`, `*Enum`
- kebab-case migration filenames: `2026_02_08_002614_create_activity_log_table.php`

**Files (TS/Vue):**
- kebab-case for type/composable files: `brand-types.ts`, `useBrandClient.ts`
- PascalCase for Vue pages and components: `Index.vue`, `ItemEditor.vue`, `admin.vue` / `pos.vue` layouts are lowercase
- Module pages under `Pages/{Module}/Index.vue`, `Pages/{Module}/Create/Index.vue`, `Pages/{Module}/Edit/Index.vue`, `Pages/{Module}/Show/Index.vue`, page-local components under `Pages/{Module}/Components/` or `Pages/{Module}/List/`

**Functions (PHP):**
- camelCase methods: `list()`, `create()`, `validateTransition()`, `recalculateStock()`
- `scopeXxx(Builder $query, ...)` for Eloquent local scopes: `scopeSearch`, `scopeActive`, `scopeExpiringSoon`

**Functions (TS):**
- camelCase: `fetchBrandsApi`, `storeBrandApi`, `applyFilters`, `resetFilters`, `onPage`, `onSort`
- Composable entry points named `useXxx`: `useBrandClient`, `useAuth`, `useApi`, `useCurrencyFormatter`

**Variables (TS):**
- camelCase for locals/refs: `filterPopover`, `sortField`, `selectedBrand`
- `const` ref variables match prop names: `const filter = ref(...)`, `const status = ref(...)`

**Types (TS):**
- PascalCase interfaces, named exports only: `export interface Brand`, `export interface BrandResponse extends Brand`
- Naming convention `{Entity}`, `{Entity}Response`, `{Entity}Payload` — base type has columns/relations, `Response` adds `id`, timestamps, serialized relations
- Always use `type` keyword for type-only imports: `import { type BrandResponse } from "@/Types/brand-types"`

**Enums (PHP):**
- All backed string enums (`enum FooEnum: string`)
- `PermissionsEnum`: UPPER_SNAKE case names, dot-notation values (`BRANDS_VIEW = 'brand.view'`)
- `RolesEnum`: UPPER names, descriptive values (`ADMIN = 'super administrator'`)
- Domain enums (`SalesOrderStatus`, `CashMovementType`): snake_case values matching DB enum columns exactly

## Code Style

**Formatting (PHP):**
- Tool: Laravel Pint (`pint.json`)
- `declare(strict_types=1)` required at top of every PHP file
- 4-space indent (PHP), LF line endings, trim trailing whitespace (`.editorconfig`)
- `concat_space` with one space, `new_with_parentheses: false`
- `ordered_class_elements` enforces element ordering: `use_trait → case → constant → property → construct → destruct → magic → phpunit → method_abstract → method_public_static → method_public → ...`
- `global_namespace_import`: import classes, constants, and functions from global namespace
- `fully_qualified_strict_types`: fully-qualified names only in imports/PHPDoc

**Linting (PHP):**
- PHPStan level 8 (strictest) with Larastan + Carbon extensions (`phpstan.neon.dist`)
- Rector configured for Laravel up to level 120 (`rector.php`) with Laravel code-quality sets
- Run `composer lint` (= `vendor/bin/pint --dirty` + `vendor/bin/phpstan analyse`) after all PHP changes are complete — never mid-implementation
- Key Pint rules: `final_class`, `final_internal_class`, `final_public_method_for_abstract_class`, `strict_comparison`, `mb_str_functions`, `modernize_types_casting`, `date_time_immutable`, `protected_to_private`, `no_superfluous_elseif`, `no_useless_else`

**Formatting (TS/Vue):**
- Tool: Prettier (`.prettierrc`) — `{ semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140, htmlWhitespaceSensitivity: "ignore", endOfLine: "lf" }`
- 2-space indent for JSON/JS/TS/Vue/YAML (`.editorconfig`)

**Linting (TS/Vue):**
- ESLint 9 flat config (`eslint.config.js`)
- Vue 3 `flat/strongly-recommended` + TypeScript `flat/recommended`
- `vue/component-api-style: ["error", ["script-setup", "composition"]]` — enforces `<script setup>` + Composition API
- `vue/block-order: ["error", { order: ["script", "template", "style"] }]`
- `vue/multi-word-component-names` with ignores: `Index`, `Home`, `Login`, `Error`, `Admin`, `Pos`
- `@typescript-eslint/consistent-type-definitions: ["error", "interface"]` — interfaces only, no `type` aliases for object shapes
- `@typescript-eslint/consistent-type-imports: ["error", { prefer: "type-imports", fixStyle: "inline-type-imports" }]`
- `@typescript-eslint/no-explicit-any: ["warn", { ignoreRestArgs: true }]`
- `@typescript-eslint/no-unused-vars` with `argsIgnorePattern: "^_"`
- Run `npm run lint` / `npm run lint:fix` / `npm run type-check` (vue-tsc) after frontend changes

## Import Organization

**PHP (`use` statements):**
- Group order: framework/external first, then `App\` namespaces
- All global-namespace classes imported (no `\Illuminate\Http\Request` inline)
- Example (`app/Http/Controllers/BrandController.php`):
  ```php
  use App\Enums\PermissionsEnum;
  use App\Http\Requests\Brands\StoreBrandRequest;
  use App\Http\Requests\Brands\UpdateBrandRequest;
  use App\Http\Resources\Brand\BrandCollection;
  use App\Models\Brand;
  use App\Services\BrandService;
  use Exception;
  use Illuminate\Http\RedirectResponse;
  use Inertia\Inertia;
  use Inertia\Response as InertiaResponse;
  ```

**TS/Vue order:**
1. PrimeVue components and types
2. Vue/Vue ecosystem (`vue`, `@inertiajs/vue3`, `vue-i18n`, `ziggy-js`)
3. Path-alias imports (`@layouts/`, `@pages/`, `@composables/`, `@app-types/`, `@/`)
4. Relative imports (rare — prefer aliases)
5. Type-only imports inline with `type` keyword

Example (`resources/js/Pages/Brands/Index.vue`):
```typescript
import { DataTable, Card, Column, Button, useToast, type DataTablePageEvent } from "primevue";
import AppLayout from "@layouts/admin.vue";
import { useDatetimeFormatter } from "@composables/useDatetimeFormatter";
import { router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import type { BrandResponse } from "@/Types/brand-types";
import { useI18n } from "vue-i18n";
```

**Path Aliases (`tsconfig.json` / `vite.config.ts`):**
- `@/*` → `resources/js/*`
- `@components/*` → `resources/js/Components/*`
- `@pages/*` → `resources/js/Pages/*`
- `@composables/*` → `resources/js/Composables/*`
- `@app-types/*` → `resources/js/Types/*`
- `@layouts/*` → `resources/js/Layouts/*`
- `@directives/*` → `resources/js/Directives/*`
- `@stores/*` → `resources/js/Stores/*`
- `@plugins/*` → `resources/js/Plugins/*`

## Error Handling

**Backend — business rule violations:**
- Throw `InvalidArgumentException` (NOT custom exception classes) for business rule violations
- Example: `throw new InvalidArgumentException('Only draft orders can be updated.');`
- Services with status transitions define a `TRANSITION_MAP` constant + private `validateTransition()` that throws `InvalidArgumentException` for invalid transitions (`app/Services/SalesOrderService.php`, `app/Services/PurchaseOrderService.php`, `app/Services/StockTransferService.php`)

**Backend — controllers catching service exceptions:**
- Web controllers wrap service calls that may throw in try/catch and redirect back with error:
  ```php
  try {
      $this->service->delete($brand);
  } catch (Exception $e) {
      return redirect()->back()->with('error', $e->getMessage());
  }
  ```
- API controllers return JSON with status codes via Eloquent Resources (`->setStatusCode(201)`, `response()->noContent()`)

**Backend — validation:**
- Always Form Request classes (never inline validation in controllers)
- Authorization in `authorize(): bool` using `$this->user()?->can(PermissionsEnum::X->value) ?? false`
- Array-format rules (never pipe-delimited): `'name' => ['required', 'string', 'max:50', 'unique:brands,name']`
- Use `Rule::unique()->ignore($model)` for unique-on-update
- Use `withValidator()` for cross-field validation (not `after()`)

**Frontend — form errors:**
- VeeValidate `setErrors(errs)` to display backend validation errors
- Toast on error: `toast.add({ severity: "error", summary: t("Error"), detail: ..., life: 3000 })`
- Focus first invalid field after error: `nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus())`

**Frontend — async API errors:**
- `useApi()` (`resources/js/Composables/useApi.ts`) axios client sets `loading.value = false` in response error interceptor and re-rejects

## Logging

**Framework:** spatie/laravel-activitylog (`activity()` helper) — no `Log::` calls for application audit

**Patterns:**
- All models use `LogsActivity` trait with standardized `getActivitylogOptions()`:
  ```php
  public function getActivitylogOptions(): LogOptions
  {
      return LogOptions::defaults()
          ->logFillable()
          ->logOnlyDirty()
          ->useLogName('brand') // snake_case model name
          ->dontSubmitEmptyLogs();
  }
  ```
- `User` model adds `->logExcept(['password'])` to exclude sensitive fields
- Explicit activity logging via `activity('log_name')->performedOn($model)->causedBy(auth()->user())->withProperties([...])->log("...")`
- `storage/logs/laravel.log` has permission issues — tests use `getJson()` instead of `get()` for forbidden assertions to avoid log write failures

## Comments

**When to Comment:**
- Section dividers in test files use `/* ─── Section Name ─── */` or `/* | Section Name | */` blocks
- PHPDoc `@return`, `@param`, `@extends`, `@var` annotations on all public methods/classes for PHPStan level 8
- Vue `<script setup>` uses short inline comments like `// Set Layout`, `// Props from Inertia`, `// Submit`

**JSDoc/TSDoc:**
- Minimal — TypeScript types self-document
- PHPDoc required on generics: `/** @return HasMany<Product, $this> */` for relationships, `/** @extends Factory<Brand> */` on factories, `/** @return LengthAwarePaginator<int, Brand> */` on `list()` methods

## Function Design

**Size:** Services keep methods small and focused — `BrandService` methods average 5–15 lines; `list()` methods are the longest due to query chaining

**Parameters (PHP):**
- Named arguments at call sites: `$this->brandService->list(status: $status, orderBy: ..., filter: $filter)`
- Constructor property promotion for DI: `public function __construct(private readonly BrandService $brandService) {}`
- `list()` uses a consistent signature across services: `list(string $status = 'all', ?string $filter = null, string $orderBy = 'name', string $orderDirection = 'asc', int $perPage = 20)`

**Return Values (PHP):**
- Services return models, paginators, or void
- Controllers return typed responses: `InertiaResponse`, `RedirectResponse`, `JsonResponse`, `Response`
- Collections return `array<string, mixed>` with `data` + `meta` keys

**Parameters/Returns (TS):**
- Composable functions return typed objects: `Promise<AxiosResponse<T>>`
- Vue event handlers typed with PrimeVue types: `DataTablePageEvent`, `DataTableSortEvent`

## Module Design

**Exports (TS):**
- Named exports only for types and composables: `export interface Brand`, `export function useBrandClient()`
- Default export for Vue directive: `export default canDirective` (`resources/js/Directives/can.ts`)
- Composables return an object bag of functions + `loading` ref

**Barrel Files:**
- Not used — import directly from module files (e.g., `@composables/useApi`, `@app-types/brand-types`)

## Key Architectural Conventions

**Class finality:** All controllers, services, models, factories, policies, form requests, and resources are `final class` (enforced by Pint `final_class` rule)

**Controller thinness:** Controllers contain no business logic — delegate to services. `app/Http/Controllers/BrandController.php` is the canonical example: authorize → extract query params → call service → render/redirect

**Service responsibility:** Business logic, DB transactions, activity logging live in `app/Services/`. Wrap critical operations in `DB::transaction()`. Use `lockForUpdate()` inside transactions for stock/concurrency-sensitive operations (`app/Services/BatchService.php`, `app/Services/FifoStockDeductionService.php`, `app/Services/StockAdjustmentService.php`)

**Authorization enforcement points:**
- Web controllers: `$this->authorize(PermissionsEnum::X)` (no user param — uses authenticated user)
- API controllers: `$this->authorize(PermissionsEnum::X->value, auth()->user())` (requires `->value` and explicit user — different from Web)
- Form Requests: `return $this->user()?->can(PermissionsEnum::X->value) ?? false`
- Policies: auto-discovered (NOT in `AuthServiceProvider`), each method checks `$user->can(PermissionsEnum::X->value)`
- Vue templates: `v-can="'brand.create'"` directive (reactive — watches `useAuth().permissions`)
- Vue composables: `const { can, canAny, canAll } = useAuth()`

**Laravel 10 structure retained:** Middleware in `app/Http/Middleware/`, kernel in `app/Http/Kernel.php`, exception handler in `app/Exceptions/Handler.php`, providers in `app/Providers/` — do not migrate to Laravel 12 streamlined structure

**Two Vite entry points:** `resources/js/app.ts` (main app, Aura theme, Inertia/Pinia/i18n) and `resources/js/login/index.js` (separate login app, Noir theme, Options API, no Inertia/i18n/Pinia)

**URLs:** Always use Ziggy's `route()` helper — never hardcode URLs. API routes prefixed `v1` and named `api.v1.{module}.{action}`; web routes named `{module}.{action}` (`routes/web.php`, `routes/api.php`)

**Frontend forms:** VeeValidate + Yup (`useForm` from `vee-validate`, `toTypedSchema` from `@vee-validate/yup`) for all create/edit forms. Inertia `useForm` ONLY for delete/restore with empty body:
```typescript
const form = useForm({});
form.delete(route("brands.destroy", id), { onSuccess, onError });
```

**i18n:** Always `t()` from `vue-i18n` for user-visible text. Default locale Spanish (`es`), fallback `es`. Plain English strings as keys. Yup locale tied to vue-i18n via `configureYupLocale()` in `app.ts`

**Styling:** Tailwind CSS 3 + PrimeVue 4. Font Awesome 6 (`fa fa-xxx`). Grid: `grid grid-cols-12 gap-4`. Validation errors: `text-red-400 dark:text-red-300` + `p-invalid` class. Dark mode dual trigger: `["class", ".app-dark"]`. PrimeVue Aura theme with custom blue primary palette (`#00539b`)

---

*Convention analysis: 2026-06-21*