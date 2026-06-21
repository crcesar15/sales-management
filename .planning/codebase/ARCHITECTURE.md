<!-- refreshed: 2026-06-21 -->
# Architecture

**Analysis Date:** 2026-06-21

## System Overview

```text
┌──────────────────────────────────────────────────────────────────────────┐
│                           Browser Client                                  │
│  Vue 3 + Inertia.js v2 client + PrimeVue 4 + Tailwind 3 + TypeScript      │
│  Two Vite entry points:                                                   │
│   • `resources/js/app.ts`        — main admin app (AppLayout, Aura)       │
│   • `resources/js/login/index.js`— separate login app (Options API, Noir) │
├───────────────────────┬───────────────────────┬──────────────────────────┤
│  Admin Pages          │  POS Module           │  Auth Pages              │
│  `resources/js/Pages/`│  `resources/js/Pages/`│  `resources/js/Pages/`   │
│   {Module}/Index.vue  │   Pos/Index.vue       │   Auth/Login.vue etc.    │
│   + Create/Edit/Show  │  Pinia `usePosStore`  │  (rendered by login app) │
│  AppLayout (sidebar)  │  PosLayout (shift bar)│                          │
│  Inertia props +      │                        │                          │
│  composables only     │                        │                          │
└───────────┬───────────┴───────────┬───────────┴────────────┬─────────────┘
            │ Inertia visits /      │ Inertia render +       │ form post
            │ Axios API calls       │ Pinia + API            │ (LoginController)
            ▼                       ▼                        ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                        HTTP Routing Layer                                 │
│  `routes/web.php` (Inertia pages)  `routes/api.php` (JSON, /v1 prefix)    │
│  `app/Http/Kernel.php` (Laravel 10 kernel)                                │
│  `app/Http/Middleware/HandleInertiaRequests.php` (shared props)           │
└───────────┬───────────────────────────────────────┬────────────────────────┘
            ▼                                       ▼
┌──────────────────────────────────┐  ┌──────────────────────────────────────┐
│   Web Controllers (Inertia)       │  │   API Controllers (JSON Resources)   │
│   `app/Http/Controllers/{Module}/`│  │   `app/Http/Controllers/Api/{Module}/`│
│   final classes, authorize via   │  │   final classes, authorize via       │
│   $this->authorize(Permissions   │  │   $this->authorize(->value, user())  │
│   Enum::X)                       │  │   return JsonResource / ResourceColl │
│   Inertia::render(...) + redirect│  │   ->setStatusCode(201)               │
└───────────┬──────────────────────┘  └──────────────────┬───────────────────┘
            ▼                                            ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                            Form Requests                                  │
│  `app/Http/Requests/{Module}/`       (Web validation + authorize())       │
│  `app/Http/Requests/Api/{Module}/`   (API validation + authorize())       │
│  Array-format rules, Rule::unique()->ignore(), withValidator()            │
└───────────┬──────────────────────────────────────────────────────────────┘
            ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                              Services                                     │
│  `app/Services/{Module}Service.php` — final classes                       │
│  All business logic, DB::transaction(), activity logging, TRANSITION_MAP  │
│  Standard list() signature: (status, filter, orderBy, orderDir, perPage)  │
└───────────┬──────────────────────────────────────────────────────────────┘
            ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                              Models                                       │
│  `app/Models/*.php` — final classes, casts() method, LogsActivity trait   │
│  SoftDeletes, HasMedia (InteractsWithMedia) where applicable              │
│  Local scopes: scopeSearch, scopeActive, scopeExpiringSoon                │
│  Boot() route binding with withTrashed() for soft-deleted models          │
└───────────┬──────────────────────────────────────────────────────────────┘
            ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  MySQL Database  │  spatie/permission  │  spatie/activitylog  │  Media    │
│  44 migrations   │  roles/permissions  │  activity_log table  │  media +  │
│  (see STRUCTURE) │  + store_user pivot │  LogsActivity trait  │  pending  │
│                  │                     │                      │  uploads  │
└──────────────────────────────────────────────────────────────────────────┘
```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| Web Controller | Authorize, delegate to service, render Inertia page or redirect | `app/Http/Controllers/UserController.php` |
| API Controller | Authorize, delegate to service, return Eloquent Resource with status code | `app/Http/Controllers/Api/UserController.php` |
| Service | Business logic, DB transactions, activity logging, status transitions | `app/Services/UserService.php` |
| Form Request (Web) | Validate + authorize for web routes | `app/Http/Requests/Users/StoreUserRequest.php` |
| Form Request (API) | Validate + authorize for API routes | `app/Http/Requests/Api/Users/StoreUserRequest.php` |
| Eloquent Resource | Transform single model to JSON | `app/Http/Resources/User/UserResource.php` |
| Resource Collection | Transform paginator to JSON with `meta` pagination | `app/Http/Resources/User/UserCollection.php` |
| Model | Data representation, casts, relationships, LogsActivity, scopes | `app/Models/User.php` |
| Policy | Authorization via PermissionsEnum (auto-discovered) | `app/Policies/UserPolicy.php` |
| Enum | Backed string enums for permissions, roles, statuses | `app/Enums/PermissionsEnum.php` |
| Vue Page | UI rendering, VeeValidate+Yup forms, PrimeVue components | `resources/js/Pages/Users/Index.vue` |
| Composable (API client) | Axios calls to API endpoints | `resources/js/Composables/useUserClient.ts` |
| Composable (shared state) | Module-level ref state, auth helpers | `resources/js/Composables/useAuth.ts` |
| Pinia Store | POS module state only | `resources/js/Composables/usePosStore.ts` |
| TypeScript Types | Interfaces mirroring API resources | `resources/js/Types/user-types.ts` |
| Layout | Page shell (sidebar/shift bar) | `resources/js/Layouts/admin.vue`, `resources/js/Layouts/pos.vue` |
| Inertia Middleware | Share `auth.user`, `auth.settings`, `alertsSummary` to every page | `app/Http/Middleware/HandleInertiaRequests.php` |
| Kernel | Register middleware groups (Laravel 10 structure) | `app/Http/Kernel.php` |
| Routes | Web (Inertia) + API (v1 JSON) definitions | `routes/web.php`, `routes/api.php` |

## Pattern Overview

**Overall:** Modular full-stack MVC with Service layer + Inertia.js SPA bridge

**Key Characteristics:**
- Every domain module mirrors the same full-stack layer set (Controller ×2, Service, Form Request ×2, Resource, Vue Page, Composable, Types)
- Laravel 12 framework retaining Laravel 10 directory structure (`app/Http/Middleware/`, `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, `app/Providers/`)
- Two parallel HTTP surfaces: Web (Inertia SSR render) and API v1 (JSON via Sanctum)
- Single source of truth for authorization: `app/Enums/PermissionsEnum.php` enforced at controllers, form requests, policies, and Vue `v-can` directive
- Frontend split into two Vite apps: main admin app and a separate login app (Options API, Noir theme)

## Layers

**Routing Layer:**
- Purpose: Map URLs to controllers, group middleware
- Location: `routes/web.php`, `routes/api.php`
- Contains: Web routes under `auth`/`guest` middleware; API routes under `auth:sanctum` with `v1` prefix and `api.v1.` name prefix
- Depends on: `app/Http/Kernel.php` middleware groups
- Used by: All HTTP requests

**Controller Layer:**
- Purpose: Authorize, delegate to services, format response (Inertia render or JSON Resource)
- Location: `app/Http/Controllers/{Module}/` (Web), `app/Http/Controllers/Api/{Module}/` (API), `app/Http/Controllers/Auth/`, `app/Http/Controllers/Pos/`
- Contains: `final` controller classes with constructor-injected services
- Depends on: Services, Form Requests, Resources, `PermissionsEnum`
- Used by: Routing layer
- Constraint: No business logic in controllers — delegate to services

**Service Layer:**
- Purpose: All business logic, DB transactions, activity logging, status transitions
- Location: `app/Services/{Module}Service.php`
- Contains: `final` service classes with `list()`, `create()`, `update()`, `delete()`, plus workflow methods (`submit`, `approve`, `openShift`, etc.)
- Depends on: Models, `DB` facade, `activity()` helper, `Cache`
- Used by: Controllers (both Web and API share the same service)
- Pattern: `TRANSITION_MAP` constant + `validateTransition()` for state-machine entities; `lockForUpdate()` inside transactions for stock-sensitive ops

**Form Request Layer:**
- Purpose: Validation rules + authorization per request
- Location: `app/Http/Requests/{Module}/` (Web), `app/Http/Requests/Api/{Module}/` (API)
- Contains: Request classes with `authorize(): bool` (using `PermissionsEnum::X->value`) and `rules(): array`
- Depends on: `PermissionsEnum`, `Rule` facade
- Used by: Controllers via method injection

**Resource Layer:**
- Purpose: JSON transformation for API responses
- Location: `app/Http/Resources/{Module}/`
- Contains: `{Module}Resource` (extends `JsonResource`) and `{Module}Collection` (extends `ResourceCollection`) per module
- Depends on: Models, nested Resources
- Used by: API controllers
- Pattern: `whenLoaded()` for conditional relations; collections override `paginationInformation()` and expose `meta` manually; dates as `->toISOString()`, money as `(float)`, enums as `->value`

**Model Layer:**
- Purpose: Eloquent entities, relationships, casts, activity logging, media
- Location: `app/Models/*.php`
- Contains: `final` model classes using `LogsActivity`, `SoftDeletes`, `InteractsWithMedia` (where applicable)
- Depends on: `spatie/activitylog`, `spatie/medialibrary`, related models
- Used by: Services
- Pattern: `casts()` method (not `$casts`), `$fillable` only (never `$guarded`), `$appends` for computed attrs (`full_name`, `expiry_status`), `boot()` with `Route::bind(...withTrashed())`

**Frontend Layer:**
- Purpose: Render UI, handle forms, call API
- Location: `resources/js/Pages/{Module}/`, `resources/js/Composables/`, `resources/js/Types/`, `resources/js/Layouts/`
- Contains: Vue 3 `<script setup lang="ts">` pages, composables, TS types, layouts
- Depends on: Inertia.js, PrimeVue, VeeValidate+Yup, Ziggy, vue-i18n, Pinia (POS only)
- Used by: Browser via Inertia root template `resources/views/layouts/app.blade.php`

## Data Flow

### Primary Web Request Path (List Page)

1. Browser navigates to `/users` → `routes/web.php:206` maps to `UserController::index`
2. `UserController::index` authorizes via `$this->authorize(PermissionsEnum::USERS_VIEW, auth()->user())` (`app/Http/Controllers/UserController.php:36`)
3. Extracts query params via `request()->string()/integer()` and calls `$this->userService->list(...)` (`app/Http/Controllers/UserController.php:40`)
4. `UserService::list()` builds query with `when()` filters, `onlyTrashed()` for archived, `->paginate()->withQueryString()` (`app/Services/UserService.php:18`)
5. Controller wraps paginator in `UserCollection` and renders `Inertia::render('Users/Index', [...])` (`app/Http/Controllers/UserController.php:53`)
6. `HandleInertiaRequests::share()` injects `auth.user`, `auth.settings`, `alertsSummary` into props (`app/Http/Middleware/HandleInertiaRequests.php:39`)
7. Vue page `resources/js/Pages/Users/Index.vue` receives props, renders DataTable with PrimeVue, uses `useAuth().can()` for action visibility

### Primary Web Mutation Path (Create)

1. Form submit → VeeValidate `handleSubmit` → `router.post(route('users.store'), values, { onSuccess, onError })`
2. `routes/web.php:208` → `UserController::store(StoreUserRequest $request)`
3. Form Request validates and authorizes (`app/Http/Requests/Users/StoreUserRequest.php`)
4. Controller calls `$this->userService->create($request->validated())`
5. `UserService::create()` wraps in `DB::transaction()`, creates user, assigns roles/stores, returns `$user->load(['roles', 'stores'])` (`app/Services/UserService.php:54`)
6. Controller redirects `redirect()->route('users')`
7. Inertia follows redirect, re-renders list page

### API Request Path

1. Client composable (`useUserClient`) calls axios against `route('api.v1.users.store')`
2. `routes/api.php:65` → `Api\UserController::store(StoreUserRequest $request)` (`app/Http/Controllers/Api/UserController.php:30`)
3. Form Request validates (`app/Http/Requests/Api/Users/StoreUserRequest.php`)
4. Service processes (shared service class with Web layer)
5. Controller returns `(new UserResource($user))->response()->setStatusCode(201)` (`app/Http/Resources/User/UserResource.php`)

### State Transition Flow (e.g., Purchase Order)

1. User clicks "Submit" → `router.patch(route('purchase-orders.submit', order))`
2. `PurchaseOrdersController::submit()` → `PurchaseOrderService::submit($order)`
3. Service calls `validateTransition()` against `TRANSITION_MAP`, throws `InvalidArgumentException` if invalid
4. On success: updates status inside `DB::transaction()`, logs activity via `activity()`
5. Controller redirects back; on `InvalidArgumentException` catch → `redirect()->back()->with('error', $e->getMessage())`

### Media Upload Flow (Two-Phase)

1. User selects image → `useMediaClient` uploads to `POST /products/media/pending` → `ProductMediaController::store` → `PendingMediaService` stores temp upload, returns temp ID
2. On product save → `ProductService::create/update` calls `PendingMediaService::commit()` to attach media to product via spatie/laravel-medialibrary
3. `CustomPathGeneratorService` hashes media paths using `md5($media->id . config('app.key'))`

**State Management:**
- Backend: Stateless request-scoped via service injection; `Setting::get()` wraps `Cache::rememberForever()`; `HandleInertiaRequests` caches settings and alerts per user
- Frontend admin: Inertia props + composables with module-level refs (no Pinia); `useLayout()`/`usePosLayout()` use module-level refs for shared sidebar/shift state
- Frontend POS: Pinia `usePosStore` (Composition API style) — the only Pinia store

## Key Abstractions

**Module Pattern:**
- Purpose: Each domain module is a vertical slice with identical layer structure
- Examples: Users (`app/Http/Controllers/UserController.php` + `app/Services/UserService.php` + `app/Http/Requests/Users/` + `app/Http/Resources/User/` + `resources/js/Pages/Users/` + `resources/js/Composables/useUserClient.ts` + `resources/js/Types/user-types.ts`), Brands, Products, SalesOrders, PurchaseOrders, Customers, Vendors, Stores, Roles, Categories, MeasurementUnits, CashRegisters, CashRegisterShifts, StockTransfers, StockAdjustments, Batches, ReceptionOrders, Catalog
- Pattern: Mirror sibling files exactly when adding a new module

**PermissionsEnum:**
- Purpose: Single source of truth for all authorization
- Examples: `app/Enums/PermissionsEnum.php` — cases like `USERS_VIEW = 'user.view'`, `BRANDS_DELETE = 'brand.delete'`
- Pattern: `{MODULE}_{ACTION}` case name, dot-notation value; enforced in Web controllers (`$this->authorize(PermissionsEnum::X)`), API controllers (`$this->authorize(PermissionsEnum::X->value, auth()->user())`), form requests (`$this->user()?->can(PermissionsEnum::X->value)`), policies, and Vue `v-can="'user.view'"`

**TRANSITION_MAP State Machine:**
- Purpose: Enforce valid status transitions for order/shift entities
- Examples: `app/Services/PurchaseOrderService.php`, `app/Services/SalesOrderService.php`, `app/Services/CashRegisterShiftService.php`
- Pattern: `private const TRANSITION_MAP = ['draft' => ['sent','paid','cancelled'], ...];` + `validateTransition()` throwing `InvalidArgumentException`

**Two-Phase Media Upload:**
- Purpose: Decouple image upload from entity save
- Examples: `app/Services/PendingMediaService.php`, `app/Models/PendingMediaUpload.php`, `resources/js/Composables/useMediaClient.ts`
- Pattern: Upload to `pending_media_uploads` → receive temp ID → commit to product on save via `PendingMediaService::commit()`

**Inertia Shared Props:**
- Purpose: Provide auth, settings, alerts to every page
- Examples: `app/Http/Middleware/HandleInertiaRequests.php:39`
- Pattern: `share()` returns `auth.user` (with roles, permissions), `auth.settings` (grouped by category, cached forever), `alertsSummary` (stock alert counts, cached 300s per user)

**Resource Collection with Manual Meta:**
- Purpose: Consistent pagination metadata shape
- Examples: `app/Http/Resources/User/UserCollection.php`, `app/Http/Resources/Product/ProductCollection.php`
- Pattern: Override `paginationInformation()` to return empty, manually include `meta` with `current_page`, `last_page`, `per_page`, `total`

## Entry Points

**Web HTTP Entry:**
- Location: `public/index.php` → `app/Http/Kernel.php` → `routes/web.php`
- Triggers: Browser navigation, Inertia visits
- Responsibilities: Session auth, Inertia rendering, CSRF

**API HTTP Entry:**
- Location: `public/index.php` → `app/Http/Kernel.php` → `routes/api.php`
- Triggers: Axios calls from composables, external API consumers
- Responsibilities: Sanctum token auth, JSON responses, `v1` versioned

**Frontend Main App Entry:**
- Location: `resources/js/app.ts`
- Triggers: `resources/views/layouts/app.blade.php` `@vite(['resources/js/app.ts'])`
- Responsibilities: `createInertiaApp`, Pinia, vue-i18n (es default), PrimeVue (Aura + custom blue `#00539b`), `ZiggyVue`, `Can` directive, `configureYupLocale()`, ToastService, ConfirmationService

**Frontend Login App Entry:**
- Location: `resources/js/login/index.js`
- Triggers: `resources/views/layouts/login.blade.php`
- Responsibilities: Separate Vue app, Options API, Noir (zinc) theme, no Inertia/Pinia/i18n, uses `useApi()` axios client directly

**Console Entry:**
- Location: `app/Console/Kernel.php`
- Triggers: `php artisan`, cron
- Responsibilities: Scheduled tasks, commands

## Architectural Constraints

- **Laravel 10 structure lock:** Middleware in `app/Http/Middleware/`, kernel in `app/Http/Kernel.php`, exception handler in `app/Exceptions/Handler.php`, providers in `app/Providers/` (no `bootstrap/app.php` Laravel 11+ structure). Do not migrate.
- **Final classes:** All controllers, services, models, policies, factories, form requests, resources are `final class`.
- **No business logic in controllers:** Controllers only authorize, call service, format response.
- **No inline validation:** All validation in Form Request classes.
- **Pinia scoped to POS:** Only `usePosStore` (`resources/js/Composables/usePosStore.ts`) uses Pinia. Admin pages use Inertia props + module-level refs.
- **Two Vite apps:** Main app (`resources/js/app.ts`) and login app (`resources/js/login/index.js`) are separate bundles with different PrimeVue themes.
- **Login uses `username` field:** `LoginController::username()` returns `'username'`, not `email`.
- **Business rule violations:** Throw `InvalidArgumentException` (no custom exception classes).
- **Authorization duality:** Web controllers use `$this->authorize(PermissionsEnum::X)` (no user param); API controllers use `$this->authorize(PermissionsEnum::X->value, auth()->user())` (requires `->value` and explicit user). This difference is intentional.
- **Soft deletes:** Models use `SoftDeletes` trait; route binding via `Route::bind('model', fn ($v) => Model::withTrashed()->findOrFail($v))` in `boot()`; restore routes use `->withTrashed()`.
- **Global state:** `HandleInertiaRequests` caches settings (`Cache::rememberForever('settings', ...)`) and alerts (`Cache::remember('alerts_summary_user_' . $user->id, 300, ...)`). `Setting::get()` also wraps `Cache::rememberForever()`.
- **Threading:** Standard PHP-FPM request lifecycle — no async, no worker threads. Stock-sensitive operations use `lockForUpdate()` inside `DB::transaction()`.
- **Circular imports:** Not detected — layers are strictly directional (Controller → Service → Model).

## Anti-Patterns

### Business Logic in Controllers

**What happens:** Occasionally logic like query parameter extraction or relationship loading creeps into controller methods.
**Why it's wrong:** Breaks the service-layer separation; logic becomes untestable and duplicated between Web and API controllers.
**Do this instead:** Put all business logic in `app/Services/{Module}Service.php`. Controllers should only authorize, extract params via `request()->string()/integer()`, call service, and format response. See `app/Http/Controllers/UserController.php` for the correct pattern.

### Using Inertia `useForm` for Create/Edit

**What happens:** New code might reach for Inertia's `useForm` for create/edit forms.
**Why it's wrong:** Project standardizes on VeeValidate + Yup for validation UX; Inertia `useForm` lacks the field-level error display and Yup schema integration the project relies on.
**Do this instead:** Use `useForm` from `vee-validate` with `toTypedSchema()` from `@vee-validate/yup`. Reserve Inertia `useForm` exclusively for delete/restore with empty body. See `resources/js/Pages/Brands/Index.vue` and `.claude/rules/vue-frontend.md`.

### Hardcoding URLs

**What happens:** String URLs like `'/users'` in API calls.
**Why it's wrong:** Breaks when route prefixes change; loses named-route safety.
**Do this instead:** Use Ziggy's `route()` helper: `route('api.v1.users.store')`, `route('users.edit', user.id)`. Never hardcode URLs.

### Custom Exception Classes for Business Rules

**What happens:** Creating domain exception classes for invalid transitions or rule violations.
**Why it's wrong:** Project convention is to throw `InvalidArgumentException` so controllers can catch uniformly with `redirect()->back()->with('error', $e->getMessage())`.
**Do this instead:** `throw new InvalidArgumentException('Only draft orders can be updated.');` in services.

## Error Handling

**Strategy:** Laravel's `app/Exceptions/Handler.php` (Laravel 10 structure) with `$dontFlash` for password fields.

**Patterns:**
- Validation errors: Form Request auto-redirects (web) or returns 422 JSON (API); frontend calls `setErrors(errs)` from VeeValidate and focuses first `.p-invalid` field
- Business rule violations: Service throws `InvalidArgumentException`; controller catches and `redirect()->back()->with('error', $e->getMessage())`
- API errors: Standard Laravel JSON exception rendering with status codes
- Frontend toasts: `toast.add({ severity: 'error', summary: t('Error'), detail: ..., life: 3000 })` on `onError` callbacks
- Known issue: `storage/logs/laravel.log` has permission issues — use `getJson()` instead of `get()` for forbidden assertions in tests to avoid log write failures

## Cross-Cutting Concerns

**Logging:** `spatie/laravel-activitylog` via `LogsActivity` trait on all models (`getActivitylogOptions()` with `logFillable`, `logOnlyDirty`, `useLogName`, `dontSubmitEmptyLogs`); explicit logging via `activity('log_name')->performedOn()->causedBy()->withProperties()->log()`. User model adds `->logExcept(['password'])`.

**Validation:** Form Request classes exclusively; array-format rules; `Rule::unique()->ignore()` for updates; `withValidator()` for cross-field validation; authorization via `$this->user()?->can(PermissionsEnum::X->value)`.

**Authentication:** Session-based for web (`auth` middleware); Sanctum token-based for API (`auth:sanctum`); login uses `username` field; `LoginController` at `app/Http/Controllers/Auth/LoginController.php`.

**Authorization:** `PermissionsEnum` (single source) enforced at five layers — Web controller, API controller, Form Request, Policy (auto-discovered), Vue `v-can` directive (reactive). `useAuth().can()/canAny()/canAll()` for programmatic checks.

**Settings:** `Setting::get($key, $default)` wraps `Cache::rememberForever()`; grouped by category; shared to frontend via `HandleInertiaRequests` as `auth.settings`; `SettingsService` at `app/Services/SettingsService.php`.

**Internationalization:** vue-i18n with `resources/lang/en.json` and `resources/lang/es.json`; default locale `es`, fallback `es`; plain English string keys; Yup locale tied to vue-i18n via `configureYupLocale()` in `app.ts`.

---

*Architecture analysis: 2026-06-21*