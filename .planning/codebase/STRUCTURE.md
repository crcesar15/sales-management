# Codebase Structure

**Analysis Date:** 2026-06-21

## Directory Layout

```
sales-management/
├── app/                          # Laravel application code (Laravel 10 structure)
│   ├── Console/                  # Console kernel & commands
│   │   └── Kernel.php
│   ├── Enums/                    # Backed string enums
│   │   ├── PermissionsEnum.php   # All authorization permissions (dot notation)
│   │   ├── RolesEnum.php         # Role definitions
│   │   ├── SalesOrderStatus.php  # Order status enum
│   │   ├── CashRegisterShiftStatus.php
│   │   ├── CashRegisterStatus.php
│   │   ├── PaymentMethod.php
│   │   ├── AdjustmentReason.php
│   │   ├── CashMovementType.php
│   │   ├── DiscountType.php
│   │   └── MarginType.php
│   ├── Exceptions/               # Laravel 10 exception handler
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/          # Web (Inertia) controllers — final classes
│   │   │   ├── Api/              # API v1 controllers (JSON)
│   │   │   ├── Auth/             # Login, register, password reset
│   │   │   ├── Pos/              # POS module controller
│   │   │   ├── {Module}Controller.php  # Flat files for each module
│   │   │   └── Controller.php    # Base controller
│   │   ├── Kernel.php            # Laravel 10 HTTP kernel (middleware groups)
│   │   ├── Middleware/           # Laravel 10 middleware location
│   │   │   ├── Authenticate.php
│   │   │   ├── HandleInertiaRequests.php  # Shares auth/settings/alerts
│   │   │   ├── RedirectIfAuthenticated.php
│   │   │   ├── TrustProxies.php
│   │   │   ├── VerifyCsrfToken.php
│   │   │   └── ... (other Laravel middleware)
│   │   ├── Requests/             # Form Request classes
│   │   │   ├── Api/              # API form requests (Api/{Module}/)
│   │   │   ├── Auth/
│   │   │   ├── {Module}/         # Web form requests per module
│   │   │   └── ProfileUpdateRequest.php
│   │   └── Resources/            # Eloquent Resources (JSON transformers)
│   │       ├── {Module}/         # {Module}Resource + {Module}Collection
│   │       └── ApiCollection.php
│   ├── Models/                   # Eloquent models — final, LogsActivity
│   ├── Policies/                 # Auto-discovered policies (final, PermissionsEnum)
│   ├── Providers/                # Laravel 10 service providers
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── BroadcastServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/                 # Service classes — final, business logic
│       └── {Module}Service.php
├── bootstrap/                    # Laravel bootstrap (app.php)
├── config/                       # Laravel config files
├── database/
│   ├── factories/                # final classes, @extends Factory<Model>
│   ├── migrations/               # 44 migrations, anonymous class format
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── PermissionSeeder.php  # Re-seed after PermissionsEnum changes
│       ├── RoleSeeder.php        # Auto-seeds before every test
│       └── SettingsSeeder.php
├── public/                       # Compiled assets, index.php entry
├── resources/
│   ├── js/
│   │   ├── app.ts                # MAIN app Vite entry (admin, Aura theme)
│   │   ├── login/                # SEPARATE login app
│   │   │   └── index.js          # Login Vite entry (Options API, Noir theme)
│   │   ├── Pages/                # Vue 3 Inertia pages (per module)
│   │   │   └── {Module}/
│   │   │       ├── Index.vue
│   │   │       ├── Create/Index.vue
│   │   │       ├── Edit/Index.vue
│   │   │       ├── Show/Index.vue
│   │   │       └── Components/   # Page-local components
│   │   ├── Composables/          # use{Module}Client.ts + shared composables
│   │   ├── Types/                # {module}-types.ts + global.d.ts
│   │   ├── Layouts/              # admin.vue, pos.vue + Components/Composables/Types
│   │   ├── Directives/           # can.ts (v-can directive)
│   │   └── validations/          # yupLocale.ts (configureYupLocale)
│   ├── lang/                     # i18n: en.json, es.json (default es)
│   ├── sass/                     # app.scss (Tailwind entry)
│   └── views/                    # Blade root templates only
│       ├── layouts/
│       │   ├── app.blade.php     # Inertia root for main app
│       │   └── login.blade.php   # Inertia root for login app
│       └── auth/                 # Auth blade views
├── routes/
│   ├── web.php                   # Web routes (Inertia pages)
│   ├── api.php                   # API routes (v1 prefix, api.v1. names)
│   ├── channels.php
│   └── console.php
├── storage/                      # Logs, uploads, framework cache
├── tests/                        # Pest 3 tests
│   └── Pest.php                  # Auto-seeds RoleSeeder + PermissionSeeder
├── .claude/rules/                # Per-layer convention rules
├── composer.json                 # PHP dependencies
├── package.json                  # JS dependencies
├── vite.config.ts                # Two Vite entry points
├── tsconfig.json                 # Path aliases: @/, @components/, @composables/, @app-types/
├── pint.json                     # Pint config (declare_strict_types, final_class, etc.)
├── phpstan.neon.dist             # PHPStan level 8 + Larastan
├── rector.php                    # Rector Laravel level 120
├── eslint.config.js              # ESLint 9 flat config (Vue 3 + TS)
├── .prettierrc                   # Prettier config
├── tailwind.config.js            # Tailwind 3
└── .editorconfig                 # 4-space PHP, 2-space JS/TS/Vue
```

## Directory Purposes

**`app/Http/Controllers/`:**
- Purpose: Web (Inertia) controllers — one flat file per module at the root, with subdirectories for `Api/`, `Auth/`, `Pos/`
- Contains: `final class {Module}Controller extends Controller`
- Key files: `UserController.php`, `ProductController.php`, `SalesOrderController.php`, `Pos/PosController.php`, `Api/UserController.php`, `Auth/LoginController.php`
- Note: Some controllers are plural (`PurchaseOrdersController`, `VendorsController`) — match existing naming when extending a module

**`app/Http/Controllers/Api/`:**
- Purpose: API v1 controllers returning JSON via Eloquent Resources
- Contains: `final class {Module}Controller` — flat files, no module subdirectories
- Key files: `UserController.php`, `BrandController.php`, `VariantsController.php`, `PurchaseOrdersController.php`

**`app/Services/`:**
- Purpose: Business logic, DB transactions, activity logging, status transitions
- Contains: `final class {Module}Service` — one flat file per module
- Key files: `UserService.php`, `ProductService.php`, `SalesOrderService.php`, `PendingMediaService.php`, `CustomPathGeneratorService.php`, `FifoStockDeductionService.php`

**`app/Http/Requests/`:**
- Purpose: Form Request validation + authorization
- Contains: `{Module}/` subdirectories for web, `Api/{Module}/` for API
- Key files: `Users/StoreUserRequest.php`, `Api/Users/StoreUserRequest.php`

**`app/Http/Resources/`:**
- Purpose: JSON transformation for API responses
- Contains: `{Module}/` subdirectories with `{Module}Resource.php` and `{Module}Collection.php`
- Key files: `User/UserResource.php`, `User/UserCollection.php`, `Product/ProductResource.php`, `Product/ProductVariantResource.php`

**`app/Models/`:**
- Purpose: Eloquent models — all `final`, all use `LogsActivity`
- Contains: 30 model files (flat)
- Key files: `User.php`, `Product.php`, `ProductVariant.php`, `SalesOrder.php`, `PurchaseOrder.php`, `CashRegisterShift.php`, `Setting.php`, `PendingMediaUpload.php`

**`app/Enums/`:**
- Purpose: Backed string enums for permissions, roles, statuses, types
- Key files: `PermissionsEnum.php` (single source of auth truth), `RolesEnum.php`, `SalesOrderStatus.php`, `PaymentMethod.php`

**`app/Policies/`:**
- Purpose: Authorization logic — auto-discovered, not registered in `AuthServiceProvider`
- Contains: `final class {Module}Policy` using `PermissionsEnum`
- Key files: `UserPolicy.php` (only one with ownership checks `$user->id !== $target->id`), `BrandPolicy.php`, `ProductPolicy.php`

**`app/Providers/`:**
- Purpose: Laravel 10 service providers (no `bootstrap/app.php` structure)
- Key files: `RouteServiceProvider.php` (HOME = `/home`), `AuthServiceProvider.php`

**`resources/js/Pages/`:**
- Purpose: Vue 3 Inertia pages — one subdirectory per module
- Contains: `{Module}/Index.vue`, `{Module}/Create/Index.vue`, `{Module}/Edit/Index.vue`, `{Module}/Show/Index.vue`, `{Module}/Components/`
- Key files: `Users/Index.vue`, `Products/Index.vue`, `Pos/Index.vue`, `Dashboard/Index.vue`, `Auth/Login.vue`

**`resources/js/Composables/`:**
- Purpose: Vue composables — `use{Module}Client.ts` for API calls + shared composables
- Contains: One composable file per module for API access, plus `useAuth.ts`, `useApi.ts`, `useCurrencyFormatter.ts`, `useDatetimeFormatter.ts`, `useLayout`/`usePosLayout` (module-level refs)
- Key files: `useAuth.ts` (can/canAny/canAll/getSetting), `useApi.ts` (axios client + loading), `usePosStore.ts` (only Pinia store), `useMediaClient.ts` (two-phase upload)

**`resources/js/Types/`:**
- Purpose: TypeScript interfaces mirroring API resources
- Contains: One file per domain `{module}-types.ts` + `global.d.ts`
- Key files: `user-types.ts`, `product-types.ts`, `sales-order-types.ts`, `global.d.ts` (augments Inertia `PageProps` with `auth`, `settings`, `appConfig`)

**`resources/js/Layouts/`:**
- Purpose: Layout shells — `admin.vue` wraps `Components/AppLayout.vue` (sidebar); `pos.vue` wraps `Components/PosLayout.vue` (shift bar)
- Contains: `admin.vue`, `pos.vue`, `Components/` (AppLayout, AppSidebar, AppFooter, PosLayout, PosShiftBar), `Composables/useMenuItems.ts`, `Types/menu.ts`

**`resources/js/Directives/`:**
- Purpose: Custom Vue directives
- Key files: `can.ts` — `v-can` directive (reactive, watches `useAuth()` permissions)

**`resources/views/`:**
- Purpose: Blade templates — ONLY root Inertia templates, no application pages
- Contains: `layouts/app.blade.php` (main app root), `layouts/login.blade.php` (login app root), `auth/`

## Key File Locations

**Entry Points:**
- `public/index.php`: Laravel HTTP entry
- `resources/js/app.ts`: Main Vue/Inertia app entry (admin, Aura theme, Pinia, i18n, PrimeVue, ZiggyVue)
- `resources/js/login/index.js`: Separate login app entry (Options API, Noir theme)
- `resources/views/layouts/app.blade.php`: Inertia root template for main app (`@vite(['resources/js/app.ts'])`)
- `resources/views/layouts/login.blade.php`: Inertia root template for login app
- `app/Console/Kernel.php`: Console entry

**Configuration:**
- `app/Http/Kernel.php`: HTTP middleware groups (Laravel 10 structure)
- `app/Providers/RouteServiceProvider.php`: Route loading + rate limiting
- `vite.config.ts`: Two Vite entry points configuration
- `tsconfig.json`: Path aliases `@/`, `@components/`, `@composables/`, `@app-types/`
- `pint.json`: Pint formatting rules
- `phpstan.neon.dist`: PHPStan level 8 + Larastan
- `eslint.config.js`: ESLint 9 flat config (Vue 3 strongly-recommended + TS recommended)
- `.prettierrc`: `{ semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140 }`
- `tailwind.config.js`: Tailwind 3 config
- `.editorconfig`: 4-space PHP, 2-space JS/TS/Vue/YAML/JSON, LF, trim trailing whitespace

**Core Logic:**
- `app/Services/{Module}Service.php`: Business logic per module
- `app/Models/{Model}.php`: Eloquent entities
- `app/Enums/PermissionsEnum.php`: Authorization source of truth
- `app/Http/Middleware/HandleInertiaRequests.php`: Shared Inertia props (`auth.user`, `auth.settings`, `alertsSummary`)

**Routing:**
- `routes/web.php`: Web routes (Inertia pages, `auth`/`guest` middleware)
- `routes/api.php`: API v1 routes (`auth:sanctum`, `v1` prefix, `api.v1.` name prefix)

**Testing:**
- `tests/`: Pest 3 feature/unit tests
- `tests/Pest.php`: Auto-seeds `RoleSeeder` + `PermissionSeeder` before every test
- `database/factories/`: Final factory classes with `@extends Factory<Model>` PHPDoc

**Database:**
- `database/migrations/`: 44 migrations, anonymous class format (`return new class extends Migration`)
- `database/seeders/PermissionSeeder.php`: Re-run after `PermissionsEnum` changes (`php artisan db:seed --class=PermissionSeeder`)

## Naming Conventions

**Files (PHP):**
- Controllers: `{Module}Controller.php` (singular or plural as established — `UserController`, `PurchaseOrdersController`, `VendorsController`). Match existing sibling naming.
- Services: `{Module}Service.php` (`UserService`, `ProductService`, `StockService`)
- Form Requests: `{Action}{Module}Request.php` (`StoreUserRequest`, `UpdateUserRequest`, `AssignStoreRequest`)
- Resources: `{Module}Resource.php` / `{Module}Collection.php` in `{Module}/` subdirectory
- Models: Singular PascalCase (`User.php`, `SalesOrder.php`, `CashRegisterShift.php`)
- Enums: `{Name}Enum.php` not required — just `{Name}.php` (`PermissionsEnum.php`, `SalesOrderStatus.php`)
- Factories: `{Model}Factory.php`
- Policies: `{Module}Policy.php`
- Migrations: `{YYYY_MM_DD_HHMMSS}_create_{table}_table.php`

**Files (Vue/TS):**
- Pages: `Index.vue`, `Create/Index.vue`, `Edit/Index.vue`, `Show/Index.vue`
- Composables: `use{Module}Client.ts` (API), `use{Name}.ts` (shared)
- Types: `{module}-types.ts` (kebab-case domain)
- Page-local components: PascalCase `.vue` in `{Module}/Components/`
- Layouts: lowercase `admin.vue`, `pos.vue`

**Directories:**
- Backend: PascalCase module subdirectories (`Users/`, `Products/`, `CashRegisterShifts/`)
- Frontend Pages: PascalCase module names matching backend (`Users/`, `Products/`, `CashRegisterShifts/`)
- Composables/Types: flat files, no subdirectories

**Classes:**
- All controllers, services, models, policies, factories, resources, form requests: `final class`
- PHP: `declare(strict_types=1);` at top (enforced by Pint)

**Functions/Methods:**
- PHP: camelCase (`list`, `create`, `updateStatus`, `openShift`, `validateTransition`)
- TS: camelCase (`fetchProductsApi`, `formatCurrency`, `setErrors`)

**Variables:**
- PHP: camelCase properties (`$firstName`, `$perPage`)
- TS: camelCase (`userFullName`, `isShiftOpen`)

**Types:**
- TS interfaces: PascalCase (`UserResponse`, `ProductVariantPayload`, `SettingGrouped`)
- Naming: `{Entity}`, `{Entity}Response`, `{Entity}Payload` — Response extends base, adds `id`, timestamps, relations

**Routes:**
- Web: `{module}.index`/`{module}` for list, `{module}.create`, `{module}.store`, `{module}.edit`, `{module}.update`, `{module}.destroy`, `{module}.restore`
- Non-RESTful: `updateStatus`, `transitionStatus`, `openShift`, `closeShift`, `forceCloseShift`, `submit`, `approve`, `pay`, `cancel`, `complete`, `addMovement`, `generate`, `syncImages`
- API: `api.v1.{module}.index`, `api.v1.{module}.store`, etc.

**Enums:**
- `PermissionsEnum`: `{MODULE}_{ACTION}` case names, dot-notation values (`brand.view`, `products.create`)
- `RolesEnum`: descriptive values (`super administrator`, `salesman`)
- Domain enums: snake_case values (`draft`, `flat`, `cash_in`) matching DB enum columns exactly

## Where to Add New Code

**New Module (full stack):**
- Web Controller: `app/Http/Controllers/{Module}Controller.php` (flat, mirror `UserController.php`)
- API Controller: `app/Http/Controllers/Api/{Module}Controller.php` (flat)
- Service: `app/Services/{Module}Service.php` (flat, mirror `UserService.php`)
- Web Form Requests: `app/Http/Requests/{Module}/Store{Module}Request.php`, `Update{Module}Request.php`
- API Form Requests: `app/Http/Requests/Api/{Module}/Store{Module}Request.php`, etc.
- Resources: `app/Http/Resources/{Module}/{Module}Resource.php`, `{Module}Collection.php`
- Model: `app/Models/{Module}.php` (final, LogsActivity, casts() method)
- Migration: `database/migrations/{timestamp}_create_{table}_table.php` (anonymous class)
- Factory: `database/factories/{Module}Factory.php` (final, `@extends Factory<Model>`)
- Policy: `app/Policies/{Module}Policy.php` (final, PermissionsEnum, auto-discovered)
- Enum entries: Add `{MODULE}_{ACTION}` cases to `app/Enums/PermissionsEnum.php`
- Permission seeder: Register new permissions in `database/seeders/PermissionSeeder.php`
- Vue Pages: `resources/js/Pages/{Module}/Index.vue`, `Create/Index.vue`, `Edit/Index.vue`, optional `Show/Index.vue`, `Components/`
- Composable: `resources/js/Composables/use{Module}Client.ts`
- Types: `resources/js/Types/{module}-types.ts`
- Sidebar menu: Add entry to `resources/js/Layouts/Composables/useMenuItems.ts` with `can` property
- Routes: Add to `routes/web.php` (Inertia) and `routes/api.php` (API v1)
- Run: `php artisan db:seed --class=PermissionSeeder`

**New Service Method:**
- Add to existing `app/Services/{Module}Service.php`
- Wrap critical ops in `DB::transaction()`
- Use `when()` for conditional query building
- Throw `InvalidArgumentException` for business rule violations
- Log activity via `activity()` helper or rely on `LogsActivity` trait

**New Vue Page Component:**
- Page-local: `resources/js/Pages/{Module}/Components/{Name}.vue` (no shared `Components/` directory)
- Use `<script setup lang="ts">`, set layout via `defineOptions({ layout: AppLayout })`
- Use VeeValidate + Yup for forms, PrimeVue components from `primevue`, `route()` from `ziggy-js`

**New Composable:**
- API client: `resources/js/Composables/use{Module}Client.ts` wrapping `useApi()`
- Shared logic: `resources/js/Composables/use{Name}.ts` (use module-level refs for cross-instance state, not Pinia)

**New TypeScript Type:**
- Add to `resources/js/Types/{module}-types.ts`
- Named exports, interfaces (not type aliases) per ESLint rule
- Mirror API resource shape: `{Entity}`, `{Entity}Response`, `{Entity}Payload`

**New Migration:**
- `php artisan make:migration create_{table}_table --no-interaction`
- Anonymous class format, foreign keys via `foreignId()->constrained()`
- Do NOT add extra migrations for modifications — edit existing migration and use `migrate:fresh` during development

**Utilities/Shared Helpers:**
- Backend: `app/Services/` (service classes) or `app/` helpers
- Frontend: `resources/js/Composables/` for reusable composition functions

## Special Directories

**`.claude/rules/`:**
- Purpose: Per-layer convention rules loaded as project instructions
- Contains: `laravel-backend.md`, `vue-frontend.md`, `routes-and-api.md`, `authorization.md`, `testing.md`, `commands.md`
- Generated: No
- Committed: Yes

**`.planning/`:**
- Purpose: GSD planning artifacts (codebase maps, phase plans, roadmap)
- Generated: Yes (by GSD workflow)
- Committed: Yes

**`storage/`:**
- Purpose: Logs, uploads, framework cache, compiled views
- Generated: Yes
- Committed: No (gitignored)
- Note: `storage/logs/laravel.log` has permission issues — use `getJson()` in tests to avoid log write failures

**`node_modules/` / `vendor/`:**
- Purpose: Dependencies
- Generated: Yes
- Committed: No

**`public/`:**
- Purpose: Compiled Vite assets + `index.php` Laravel entry
- Generated: Yes (build output)
- Committed: Partially (build artifacts, `index.php`, `.htaccess`)

**`resources/views/`:**
- Purpose: Blade templates — ONLY root Inertia templates, no application pages
- Generated: No
- Committed: Yes

**`_ide_helper.php` / `_ide_helper_models.php`:**
- Purpose: IDE helper files generated by `barryvdh/laravel-ide-helper`
- Generated: Yes
- Committed: Yes (for IDE support)

---

*Structure analysis: 2026-06-21*