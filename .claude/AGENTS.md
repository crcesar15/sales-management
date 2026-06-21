<!-- GSD:project-start source:PROJECT.md -->

## Project

**Sales Management — Refactor & Completion**

A Laravel 12 + Inertia.js + Vue 3 + PrimeVue sales-management application covering products, inventory, purchase orders, reception orders, sales orders, customers, vendors, cash registers, and stores. The backend and most admin modules are built and functional, but three user-facing surfaces are missing or stubbed: the **POS interface**, the **main dashboard**, and **reports**. Additionally, the existing code has accumulated critical issues (security gaps, broken resources, known bugs, convention drift) and a large unused API layer that must be addressed before new feature work.

**Core Value:** A salesman or manager can complete an end-to-end sale through the POS and immediately see the result on the dashboard and reports — while every existing module behaves consistently with the project's documented conventions and is free of the critical defects listed in `.planning/codebase/CONCERNS.md`.

### Constraints

- **Tech stack**: Keep Laravel 12 (Laravel 10 directory structure) + Inertia + Vue 3 + PrimeVue 4 + Tailwind 3. Do not migrate to Laravel 12 streamlined structure.
- **Conventions**: All new/changed code must follow `.claude/rules/*.md` (final classes, Form Requests, PermissionsEnum authorization, casts() method, LogsActivity trait, VeeValidate + Yup, PrimeVue direct imports, Ziggy `route()`).
- **No new migrations for modifications**: Modify existing migrations during development and use `migrate:fresh`. New migrations only for genuinely new tables (e.g. report snapshots if needed).
- **API surface**: Only the ~10 endpoints actually used by Inertia pages may remain after refactor. New dynamic fetches should prefer Inertia partial-reload / deferred props over new API endpoints where feasible.
- **POS UX**: Must use `PosLayout`, `usePosStore` (Pinia), and integrate with the existing `CashRegisterShiftService` / `CashRegisterService` backend — not reimplement them.
- **Reports**: Read-only — no write operations. Reuse existing services for aggregation; do not duplicate business logic.
- **Backend rules**: Throw `InvalidArgumentException` for business rule violations (not custom exceptions). Wrap critical operations in `DB::transaction()`. Use `lockForUpdate()` for stock/concurrency-sensitive operations.
- **Frontend rules**: VeeValidate + Yup for forms (not Inertia `useForm` except delete/restore). `v-can` directive for permission gating. `t()` from vue-i18n for all user-visible text.

<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->

## Technology Stack

## Languages

- PHP 8.3.10 — Backend application logic, all code under `app/`, `config/`, `database/`, `routes/`, `tests/`
- TypeScript 5.9.3 — Frontend Vue 3 SPA under `resources/js/`
- Vue 3.5.32 (SFC `.vue` files) — UI components and pages under `resources/js/Pages/`, `resources/js/Layouts/`, `resources/js/Components/`
- SCSS — Styling entry at `resources/sass/app.scss` (compiled via Vite)
- SQL — Migrations under `database/migrations/` (anonymous class format)
- JSON — i18n translation files at `resources/lang/en.json`, `resources/lang/es.json`

## Runtime

- PHP 8.3.10 (CLI NTS) — requires `^8.3` per `composer.json`
- Node.js 22.23.0 — required for Vite build/dev tooling
- Laravel Framework 12.56.0 (running on retained Laravel 10 directory structure)
- Composer 2.x — PHP dependency manager; lockfile `composer.lock` present
- npm 10.9.8 — JS dependency manager; lockfile `package-lock.json` present

## Frameworks

- Laravel Framework 12.56.0 — Backend application framework (`composer.json` requires `^12.0`)
- Inertia.js v1.3.4 server (`inertiajs/inertia-laravel`) — Bridges Laravel to Vue SPA without separate API for page rendering
- Inertia.js v2.3.21 client (`@inertiajs/vue3`) — Vue-side Inertia integration
- Vue 3.5.32 — Frontend UI framework (Composition API + `<script setup lang="ts">`)
- PrimeVue 4.5.5 — UI component library; components imported directly from `primevue`
- Tailwind CSS 3.4.19 — Utility-first CSS; config at `tailwind.config.js`
- Vite 7.3.2 — Asset bundler/dev server; config at `vite.config.ts`
- Pest 3.8.6 — PHP test framework (`pestphp/pest` + `pestphp/pest-plugin-laravel` 3.2.0); config via `tests/Pest.php`, `phpunit.xml`
- PHPUnit 11.5.50 — Underlying test runner (used by Pest)
- Vite 7.3.2 with `@vitejs/plugin-vue` 6.0.6 and `laravel-vite-plugin` 2.1.0
- Sass 1.56.1 — SCSS compilation
- PostCSS with Tailwind plugin (inline in `vite.config.ts`)
- Laravel Pint 1.29.0 — PHP formatter/linter; config `pint.json`; preset `laravel` with strict rules (`declare_strict_types`, `final_class`, `final_internal_class`, `final_public_method_for_abstract_class`, `global_namespace_import`, `ordered_class_elements`, `date_time_immutable`, `mb_str_functions`, `modernize_types_casting`)
- PHPStan 2.1.46 — Static analysis at level 8; config `phpstan.neon.dist` with Larastan + Carbon extensions
- Larastan 3.9.3 — Laravel-specific PHPStan extension
- Rector 2.x (`driftingly/rector-laravel` 2.2.0) — Automated refactoring; config `rector.php`; Laravel sets up to level 120
- Laravel IDE Helper 3.6 — Generates `_ide_helper.php`, `_ide_helper_models.php`
- ESLint 9.39.4 — Flat config at `eslint.config.js`; Vue 3 strongly-recommended + TypeScript recommended
- @typescript-eslint 8.58.2 — TypeScript ESLint rules; enforces `consistent-type-imports` (inline), `consistent-type-definitions` (interface)
- Prettier 3.8.3 — Code formatter; config `.prettierrc` (`semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140`)
- vue-tsc 3.2.6 — Vue TypeScript type checking (`npm run type-check` → `vue-tsc --noEmit`)
- Laravel Tinker 2.11.1 — REPL
- Laravel Sail 1.56.0 — Docker dev environment (available, not used per AGENTS.md: "No Docker")
- Laravel Boost 2.4.1 — MCP server for AI-assisted development (`php artisan boost:mcp`); config `boost.json`, `.mcp.json`
- Laravel Debugbar 3.16.5 — Dev-only debug bar; registered in `config/app.php` providers
- Laravel Ignition 2.12.0 (Spatie) — Error page improvements

## Key Dependencies

- `spatie/laravel-permission` 6.25.0 — Role/permission RBAC; config `config/permission.php`; enums `app/Enums/PermissionsEnum.php`, `app/Enums/RolesEnum.php`
- `spatie/laravel-activitylog` 4.12.3 — Audit trail via `LogsActivity` trait on all models + `activity()` helper in services
- `spatie/laravel-medialibrary` 11.21.0 — Media/file management; config `config/media-library.php`; custom path generator `app/Services/CustomPathGeneratorService.php`
- `spatie/image` 3.9.4 + `spatie/image-optimizer` 1.8.1 — Image manipulation/optimization (used by medialibrary)
- `tightenco/ziggy` 2.6.2 — Exposes named routes to JS; client `ziggy-js` 2.6.2; `route()` helper used in all Vue pages
- `laravel/sanctum` 4.3.1 — API token authentication; config `config/sanctum.php`; middleware `auth:sanctum` on API routes
- `laravel/ui` 4.6.3 — Auth scaffolding (login controllers/views)
- `guzzlehttp/guzzle` 7.10.0 — HTTP client (dependency of framework/medialibrary; no direct external API usage detected in app code)
- `@inertiajs/vue3` 2.3.21 — Inertia Vue adapter; `@inertiajs/progress` 0.2.7 for progress bar
- `primevue` 4.5.5 + `@primeuix/themes` 1.2.5 — UI components + theming (Aura theme, custom blue palette `#00539b`)
- `vee-validate` 4.15.1 + `@vee-validate/yup` 4.15.1 + `yup` 1.7.1 — Form validation (replaces Inertia `useForm` for create/edit)
- `pinia` 3.0.4 — State management (POS module only: `usePosStore`)
- `vue-i18n` 9.14.5 — Internationalization (default locale `es`, fallback `es`)
- `axios` 1.15.0 — HTTP client for internal API calls via `useApi()` composable
- `chart.js` 4.5.1 — Charting (dashboards)
- `moment-timezone` 0.6.1 — Date/time with timezone support (`useDatetimeFormatter`)
- `vue-advanced-cropper` 2.8.9 + `vue-cropperjs` 5.0.0 — Image cropping for product media
- `@fortawesome/fontawesome-free` 6.7.2 — Icons (`fa fa-xxx` prefix)
- `tailwindcss-primeui` 0.3.4 — Tailwind plugin for PrimeVue integration
- `fumeapp/modeltyper` 3.10.0 — Generates TypeScript types from Eloquent models (`php artisan model:typer`)
- `barryvdh/laravel-debugbar` 3.16.5 — Dev debug bar
- `barryvdh/laravel-ide-helper` 3.6 — IDE autocompletion helpers
- `fakerphp/faker` 1.24.1 — Test data generation in factories
- `mockery/mockery` 1.6.12 — Mocking for tests
- `nunomaduro/collision` 8.9.2 — Pretty error reporting (Pest)

## Configuration

- `.env` file present (contains environment configuration — NOT read here per security policy)
- `.env.example` template at `.env.example` (59 lines) defines: `APP_*`, `DB_*` (mysql default), `CACHE_DRIVER=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `FILESYSTEM_DISK=local`, `MAIL_*` (SMTP/Mailpit), `AWS_*`, `PUSHER_*`, `REDIS_*`, `VITE_*`
- Timezone: `UTC` (`config/app.php`); locale `en` (app), `es` (frontend i18n default)
- Cipher: `AES-256-CBC` for encryption
- `vite.config.ts` — Two entry points: `resources/js/app.ts` (main Aura-themed app), `resources/js/login/index.js` (separate Noir-themed login app, Options API), `resources/sass/app.scss`
- Path aliases in `vite.config.ts` and `tsconfig.json`: `@/` → `resources/js/`, `@components/`, `@pages/`, `@composables/`, `@app-types/`, `@layouts/`, `@directives/`, `@stores/`, `@plugins/`
- `tsconfig.json` — `target: ESNext`, `strict: true`, `moduleResolution: bundler`
- `tailwind.config.js` — `darkMode: ["class", ".app-dark"]` (dual trigger), `tailwindcss-primeui` plugin
- `phpunit.xml` — Test env: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `CACHE_DRIVER=array`, `SESSION_DRIVER=array`
- `pint.json` — Pint preset `laravel` with aggressive strict rules
- `phpstan.neon.dist` — Level 8, Larastan + Carbon extensions; excludes specific vendor-generated files
- `rector.php` — Laravel sets up to level 120, dead code/code quality/type declaration/privatization sets
- `eslint.config.js` — ESLint 9 flat config; `vue/component-api-style` enforces `script-setup`; `vue/block-order` enforces `[script, template, style]`; `vue/multi-word-component-names` with exceptions for Index, Home, Login, Error, Admin, Pos
- `.prettierrc` — Prettier config
- `.editorconfig` — 4-space indent PHP, 2-space JSON/JS/TS/Vue/YAML, LF line endings
- `boost.json` — Laravel Boost config: enabled skills (`laravel-best-practices`, `pest-testing`, `inertia-vue-development`, `tailwindcss-development`, `medialibrary-development`)
- `.mcp.json` — MCP server config: `laravel-boost` via `php artisan boost:mcp`

## Platform Requirements

- PHP 8.3+ with extensions: pdo_mysql, gd (image driver, `config/media-library.php`), mbstring
- Node.js 22+ and npm 10+
- Composer 2.x
- MySQL/MariaDB for local dev (default `DB_CONNECTION=mysql`, host `127.0.0.1:3306`)
- Optional: Redis (`REDIS_HOST`), FFmpeg/FFprobe (`FFMPEG_PATH` for video thumbnails in medialibrary), image optimizers (jpegoptim, pngquant, optipng, svgo, gifsicle, cwebp, avifenc — configured in `config/media-library.php`)
- Run dev: `composer run dev` (Laravel + Vite dev server) or `npm run dev` (Vite only)
- PHP 8.3+ server (FPM/Swoole/Octane-capable; Octane reset listener disabled in `config/permission.php`)
- MySQL 8+ (charset `utf8mb4`, collation `utf8mb4_unicode_ci`, strict mode)
- Web server with PHP support (Nginx + PHP-FPM typical)
- Filesystem: local disk (default `public` disk for media via symlink `public/storage` → `storage/app/public`)
- No Docker/CI/CD/Makefile configured in this project (per AGENTS.md)
- Build assets: `npm run build` before deploy

<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->

## Conventions

## Naming Patterns

- PascalCase for classes: `BrandService.php`, `StoreBrandRequest.php`, `BrandController.php`, `BrandCollection.php`, `BrandPolicy.php`, `BrandFactory.php`, `PermissionsEnum.php`
- Suffix by layer: `*Service`, `*Controller`, `*Request`, `*Collection` / `*Resource`, `*Policy`, `*Factory`, `*Enum`
- kebab-case migration filenames: `2026_02_08_002614_create_activity_log_table.php`
- kebab-case for type/composable files: `brand-types.ts`, `useBrandClient.ts`
- PascalCase for Vue pages and components: `Index.vue`, `ItemEditor.vue`, `admin.vue` / `pos.vue` layouts are lowercase
- Module pages under `Pages/{Module}/Index.vue`, `Pages/{Module}/Create/Index.vue`, `Pages/{Module}/Edit/Index.vue`, `Pages/{Module}/Show/Index.vue`, page-local components under `Pages/{Module}/Components/` or `Pages/{Module}/List/`
- camelCase methods: `list()`, `create()`, `validateTransition()`, `recalculateStock()`
- `scopeXxx(Builder $query, ...)` for Eloquent local scopes: `scopeSearch`, `scopeActive`, `scopeExpiringSoon`
- camelCase: `fetchBrandsApi`, `storeBrandApi`, `applyFilters`, `resetFilters`, `onPage`, `onSort`
- Composable entry points named `useXxx`: `useBrandClient`, `useAuth`, `useApi`, `useCurrencyFormatter`
- camelCase for locals/refs: `filterPopover`, `sortField`, `selectedBrand`
- `const` ref variables match prop names: `const filter = ref(...)`, `const status = ref(...)`
- PascalCase interfaces, named exports only: `export interface Brand`, `export interface BrandResponse extends Brand`
- Naming convention `{Entity}`, `{Entity}Response`, `{Entity}Payload` — base type has columns/relations, `Response` adds `id`, timestamps, serialized relations
- Always use `type` keyword for type-only imports: `import { type BrandResponse } from "@/Types/brand-types"`
- All backed string enums (`enum FooEnum: string`)
- `PermissionsEnum`: UPPER_SNAKE case names, dot-notation values (`BRANDS_VIEW = 'brand.view'`)
- `RolesEnum`: UPPER names, descriptive values (`ADMIN = 'super administrator'`)
- Domain enums (`SalesOrderStatus`, `CashMovementType`): snake_case values matching DB enum columns exactly

## Code Style

- Tool: Laravel Pint (`pint.json`)
- `declare(strict_types=1)` required at top of every PHP file
- 4-space indent (PHP), LF line endings, trim trailing whitespace (`.editorconfig`)
- `concat_space` with one space, `new_with_parentheses: false`
- `ordered_class_elements` enforces element ordering: `use_trait → case → constant → property → construct → destruct → magic → phpunit → method_abstract → method_public_static → method_public → ...`
- `global_namespace_import`: import classes, constants, and functions from global namespace
- `fully_qualified_strict_types`: fully-qualified names only in imports/PHPDoc
- PHPStan level 8 (strictest) with Larastan + Carbon extensions (`phpstan.neon.dist`)
- Rector configured for Laravel up to level 120 (`rector.php`) with Laravel code-quality sets
- Run `composer lint` (= `vendor/bin/pint --dirty` + `vendor/bin/phpstan analyse`) after all PHP changes are complete — never mid-implementation
- Key Pint rules: `final_class`, `final_internal_class`, `final_public_method_for_abstract_class`, `strict_comparison`, `mb_str_functions`, `modernize_types_casting`, `date_time_immutable`, `protected_to_private`, `no_superfluous_elseif`, `no_useless_else`
- Tool: Prettier (`.prettierrc`) — `{ semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140, htmlWhitespaceSensitivity: "ignore", endOfLine: "lf" }`
- 2-space indent for JSON/JS/TS/Vue/YAML (`.editorconfig`)
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

- Group order: framework/external first, then `App\` namespaces
- All global-namespace classes imported (no `\Illuminate\Http\Request` inline)
- Example (`app/Http/Controllers/BrandController.php`):
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

- Throw `InvalidArgumentException` (NOT custom exception classes) for business rule violations
- Example: `throw new InvalidArgumentException('Only draft orders can be updated.');`
- Services with status transitions define a `TRANSITION_MAP` constant + private `validateTransition()` that throws `InvalidArgumentException` for invalid transitions (`app/Services/SalesOrderService.php`, `app/Services/PurchaseOrderService.php`, `app/Services/StockTransferService.php`)
- Web controllers wrap service calls that may throw in try/catch and redirect back with error:
- API controllers return JSON with status codes via Eloquent Resources (`->setStatusCode(201)`, `response()->noContent()`)
- Always Form Request classes (never inline validation in controllers)
- Authorization in `authorize(): bool` using `$this->user()?->can(PermissionsEnum::X->value) ?? false`
- Array-format rules (never pipe-delimited): `'name' => ['required', 'string', 'max:50', 'unique:brands,name']`
- Use `Rule::unique()->ignore($model)` for unique-on-update
- Use `withValidator()` for cross-field validation (not `after()`)
- VeeValidate `setErrors(errs)` to display backend validation errors
- Toast on error: `toast.add({ severity: "error", summary: t("Error"), detail: ..., life: 3000 })`
- Focus first invalid field after error: `nextTick(() => document.querySelector<HTMLInputElement>(".p-invalid")?.focus())`
- `useApi()` (`resources/js/Composables/useApi.ts`) axios client sets `loading.value = false` in response error interceptor and re-rejects

## Logging

- All models use `LogsActivity` trait with standardized `getActivitylogOptions()`:
- `User` model adds `->logExcept(['password'])` to exclude sensitive fields
- Explicit activity logging via `activity('log_name')->performedOn($model)->causedBy(auth()->user())->withProperties([...])->log("...")`
- `storage/logs/laravel.log` has permission issues — tests use `getJson()` instead of `get()` for forbidden assertions to avoid log write failures

## Comments

- Section dividers in test files use `/* ─── Section Name ─── */` or `/* | Section Name | */` blocks
- PHPDoc `@return`, `@param`, `@extends`, `@var` annotations on all public methods/classes for PHPStan level 8
- Vue `<script setup>` uses short inline comments like `// Set Layout`, `// Props from Inertia`, `// Submit`
- Minimal — TypeScript types self-document
- PHPDoc required on generics: `/** @return HasMany<Product, $this> */` for relationships, `/** @extends Factory<Brand> */` on factories, `/** @return LengthAwarePaginator<int, Brand> */` on `list()` methods

## Function Design

- Named arguments at call sites: `$this->brandService->list(status: $status, orderBy: ..., filter: $filter)`
- Constructor property promotion for DI: `public function __construct(private readonly BrandService $brandService) {}`
- `list()` uses a consistent signature across services: `list(string $status = 'all', ?string $filter = null, string $orderBy = 'name', string $orderDirection = 'asc', int $perPage = 20)`
- Services return models, paginators, or void
- Controllers return typed responses: `InertiaResponse`, `RedirectResponse`, `JsonResponse`, `Response`
- Collections return `array<string, mixed>` with `data` + `meta` keys
- Composable functions return typed objects: `Promise<AxiosResponse<T>>`
- Vue event handlers typed with PrimeVue types: `DataTablePageEvent`, `DataTableSortEvent`

## Module Design

- Named exports only for types and composables: `export interface Brand`, `export function useBrandClient()`
- Default export for Vue directive: `export default canDirective` (`resources/js/Directives/can.ts`)
- Composables return an object bag of functions + `loading` ref
- Not used — import directly from module files (e.g., `@composables/useApi`, `@app-types/brand-types`)

## Key Architectural Conventions

- Web controllers: `$this->authorize(PermissionsEnum::X)` (no user param — uses authenticated user)
- API controllers: `$this->authorize(PermissionsEnum::X->value, auth()->user())` (requires `->value` and explicit user — different from Web)
- Form Requests: `return $this->user()?->can(PermissionsEnum::X->value) ?? false`
- Policies: auto-discovered (NOT in `AuthServiceProvider`), each method checks `$user->can(PermissionsEnum::X->value)`
- Vue templates: `v-can="'brand.create'"` directive (reactive — watches `useAuth().permissions`)
- Vue composables: `const { can, canAny, canAll } = useAuth()`

<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->

## Architecture

## System Overview

```text

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

- Every domain module mirrors the same full-stack layer set (Controller ×2, Service, Form Request ×2, Resource, Vue Page, Composable, Types)
- Laravel 12 framework retaining Laravel 10 directory structure (`app/Http/Middleware/`, `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, `app/Providers/`)
- Two parallel HTTP surfaces: Web (Inertia SSR render) and API v1 (JSON via Sanctum)
- Single source of truth for authorization: `app/Enums/PermissionsEnum.php` enforced at controllers, form requests, policies, and Vue `v-can` directive
- Frontend split into two Vite apps: main admin app and a separate login app (Options API, Noir theme)

## Layers

- Purpose: Map URLs to controllers, group middleware
- Location: `routes/web.php`, `routes/api.php`
- Contains: Web routes under `auth`/`guest` middleware; API routes under `auth:sanctum` with `v1` prefix and `api.v1.` name prefix
- Depends on: `app/Http/Kernel.php` middleware groups
- Used by: All HTTP requests
- Purpose: Authorize, delegate to services, format response (Inertia render or JSON Resource)
- Location: `app/Http/Controllers/{Module}/` (Web), `app/Http/Controllers/Api/{Module}/` (API), `app/Http/Controllers/Auth/`, `app/Http/Controllers/Pos/`
- Contains: `final` controller classes with constructor-injected services
- Depends on: Services, Form Requests, Resources, `PermissionsEnum`
- Used by: Routing layer
- Constraint: No business logic in controllers — delegate to services
- Purpose: All business logic, DB transactions, activity logging, status transitions
- Location: `app/Services/{Module}Service.php`
- Contains: `final` service classes with `list()`, `create()`, `update()`, `delete()`, plus workflow methods (`submit`, `approve`, `openShift`, etc.)
- Depends on: Models, `DB` facade, `activity()` helper, `Cache`
- Used by: Controllers (both Web and API share the same service)
- Pattern: `TRANSITION_MAP` constant + `validateTransition()` for state-machine entities; `lockForUpdate()` inside transactions for stock-sensitive ops
- Purpose: Validation rules + authorization per request
- Location: `app/Http/Requests/{Module}/` (Web), `app/Http/Requests/Api/{Module}/` (API)
- Contains: Request classes with `authorize(): bool` (using `PermissionsEnum::X->value`) and `rules(): array`
- Depends on: `PermissionsEnum`, `Rule` facade
- Used by: Controllers via method injection
- Purpose: JSON transformation for API responses
- Location: `app/Http/Resources/{Module}/`
- Contains: `{Module}Resource` (extends `JsonResource`) and `{Module}Collection` (extends `ResourceCollection`) per module
- Depends on: Models, nested Resources
- Used by: API controllers
- Pattern: `whenLoaded()` for conditional relations; collections override `paginationInformation()` and expose `meta` manually; dates as `->toISOString()`, money as `(float)`, enums as `->value`
- Purpose: Eloquent entities, relationships, casts, activity logging, media
- Location: `app/Models/*.php`
- Contains: `final` model classes using `LogsActivity`, `SoftDeletes`, `InteractsWithMedia` (where applicable)
- Depends on: `spatie/activitylog`, `spatie/medialibrary`, related models
- Used by: Services
- Pattern: `casts()` method (not `$casts`), `$fillable` only (never `$guarded`), `$appends` for computed attrs (`full_name`, `expiry_status`), `boot()` with `Route::bind(...withTrashed())`
- Purpose: Render UI, handle forms, call API
- Location: `resources/js/Pages/{Module}/`, `resources/js/Composables/`, `resources/js/Types/`, `resources/js/Layouts/`
- Contains: Vue 3 `<script setup lang="ts">` pages, composables, TS types, layouts
- Depends on: Inertia.js, PrimeVue, VeeValidate+Yup, Ziggy, vue-i18n, Pinia (POS only)
- Used by: Browser via Inertia root template `resources/views/layouts/app.blade.php`

## Data Flow

### Primary Web Request Path (List Page)

### Primary Web Mutation Path (Create)

### API Request Path

### State Transition Flow (e.g., Purchase Order)

### Media Upload Flow (Two-Phase)

- Backend: Stateless request-scoped via service injection; `Setting::get()` wraps `Cache::rememberForever()`; `HandleInertiaRequests` caches settings and alerts per user
- Frontend admin: Inertia props + composables with module-level refs (no Pinia); `useLayout()`/`usePosLayout()` use module-level refs for shared sidebar/shift state
- Frontend POS: Pinia `usePosStore` (Composition API style) — the only Pinia store

## Key Abstractions

- Purpose: Each domain module is a vertical slice with identical layer structure
- Examples: Users (`app/Http/Controllers/UserController.php` + `app/Services/UserService.php` + `app/Http/Requests/Users/` + `app/Http/Resources/User/` + `resources/js/Pages/Users/` + `resources/js/Composables/useUserClient.ts` + `resources/js/Types/user-types.ts`), Brands, Products, SalesOrders, PurchaseOrders, Customers, Vendors, Stores, Roles, Categories, MeasurementUnits, CashRegisters, CashRegisterShifts, StockTransfers, StockAdjustments, Batches, ReceptionOrders, Catalog
- Pattern: Mirror sibling files exactly when adding a new module
- Purpose: Single source of truth for all authorization
- Examples: `app/Enums/PermissionsEnum.php` — cases like `USERS_VIEW = 'user.view'`, `BRANDS_DELETE = 'brand.delete'`
- Pattern: `{MODULE}_{ACTION}` case name, dot-notation value; enforced in Web controllers (`$this->authorize(PermissionsEnum::X)`), API controllers (`$this->authorize(PermissionsEnum::X->value, auth()->user())`), form requests (`$this->user()?->can(PermissionsEnum::X->value)`), policies, and Vue `v-can="'user.view'"`
- Purpose: Enforce valid status transitions for order/shift entities
- Examples: `app/Services/PurchaseOrderService.php`, `app/Services/SalesOrderService.php`, `app/Services/CashRegisterShiftService.php`
- Pattern: `private const TRANSITION_MAP = ['draft' => ['sent','paid','cancelled'], ...];` + `validateTransition()` throwing `InvalidArgumentException`
- Purpose: Decouple image upload from entity save
- Examples: `app/Services/PendingMediaService.php`, `app/Models/PendingMediaUpload.php`, `resources/js/Composables/useMediaClient.ts`
- Pattern: Upload to `pending_media_uploads` → receive temp ID → commit to product on save via `PendingMediaService::commit()`
- Purpose: Provide auth, settings, alerts to every page
- Examples: `app/Http/Middleware/HandleInertiaRequests.php:39`
- Pattern: `share()` returns `auth.user` (with roles, permissions), `auth.settings` (grouped by category, cached forever), `alertsSummary` (stock alert counts, cached 300s per user)
- Purpose: Consistent pagination metadata shape
- Examples: `app/Http/Resources/User/UserCollection.php`, `app/Http/Resources/Product/ProductCollection.php`
- Pattern: Override `paginationInformation()` to return empty, manually include `meta` with `current_page`, `last_page`, `per_page`, `total`

## Entry Points

- Location: `public/index.php` → `app/Http/Kernel.php` → `routes/web.php`
- Triggers: Browser navigation, Inertia visits
- Responsibilities: Session auth, Inertia rendering, CSRF
- Location: `public/index.php` → `app/Http/Kernel.php` → `routes/api.php`
- Triggers: Axios calls from composables, external API consumers
- Responsibilities: Sanctum token auth, JSON responses, `v1` versioned
- Location: `resources/js/app.ts`
- Triggers: `resources/views/layouts/app.blade.php` `@vite(['resources/js/app.ts'])`
- Responsibilities: `createInertiaApp`, Pinia, vue-i18n (es default), PrimeVue (Aura + custom blue `#00539b`), `ZiggyVue`, `Can` directive, `configureYupLocale()`, ToastService, ConfirmationService
- Location: `resources/js/login/index.js`
- Triggers: `resources/views/layouts/login.blade.php`
- Responsibilities: Separate Vue app, Options API, Noir (zinc) theme, no Inertia/Pinia/i18n, uses `useApi()` axios client directly
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

### Using Inertia `useForm` for Create/Edit

### Hardcoding URLs

### Custom Exception Classes for Business Rules

## Error Handling

- Validation errors: Form Request auto-redirects (web) or returns 422 JSON (API); frontend calls `setErrors(errs)` from VeeValidate and focuses first `.p-invalid` field
- Business rule violations: Service throws `InvalidArgumentException`; controller catches and `redirect()->back()->with('error', $e->getMessage())`
- API errors: Standard Laravel JSON exception rendering with status codes
- Frontend toasts: `toast.add({ severity: 'error', summary: t('Error'), detail: ..., life: 3000 })` on `onError` callbacks
- Known issue: `storage/logs/laravel.log` has permission issues — use `getJson()` instead of `get()` for forbidden assertions in tests to avoid log write failures

## Cross-Cutting Concerns

<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->

## Project Skills

| Skill | Description | Path |
|-------|-------------|------|
| commit | >- Creates git commits following project conventions. Activate when the user asks to commit changes, uses /commit, or says something like "commit this", "make a commit", or "let's commit". | `.claude/skills/commit/SKILL.md` |
| inertia-vue-development | "Develops Inertia.js v2 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation." | `.claude/skills/inertia-vue-development/SKILL.md` |
| laravel-best-practices | "Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns." | `.claude/skills/laravel-best-practices/SKILL.md` |
| medialibrary-development | Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths. | `.claude/skills/medialibrary-development/SKILL.md` |
| pest-testing | "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit) or architecture tests. Covers: it()/expect() syntax, datasets, mocking, browser testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for editing factories, seeders, migrations, controllers, models, or non-test PHP code." | `.claude/skills/pest-testing/SKILL.md` |
| tailwindcss-development | "Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS." | `.claude/skills/tailwindcss-development/SKILL.md` |
| creating-dashboards | Creates comprehensive dashboard and analytics interfaces that combine data visualization, KPI cards, real-time updates, and interactive layouts. Use this skill when building business intelligence dashboards, monitoring systems, executive reports, or any interface that requires multiple coordinated data displays with filters, metrics, and visualizations working together. | `.agents/skills/creating-dashboards/SKILL.md` |
| prd | 'Generate high-quality Product Requirements Documents (PRDs) for software systems and AI-powered features. Includes executive summaries, user stories, technical specifications, and risk analysis.' | `.agents/skills/prd/SKILL.md` |
| ui-ux-pro-max | "UI/UX design intelligence for web and mobile. Includes 50+ styles, 161 color palettes, 57 font pairings, 161 product types, 99 UX guidelines, and 25 chart types across 10 stacks (React, Next.js, Vue, Svelte, SwiftUI, React Native, Flutter, Tailwind, shadcn/ui, and HTML/CSS). Actions: plan, build, create, design, implement, review, fix, improve, optimize, enhance, refactor, and check UI/UX code. Projects: website, landing page, dashboard, admin panel, e-commerce, SaaS, portfolio, blog, and mobile app. Elements: button, modal, navbar, sidebar, card, table, form, and chart. Styles: glassmorphism, claymorphism, minimalism, brutalism, neumorphism, bento grid, dark mode, responsive, skeuomorphism, and flat design. Topics: color systems, accessibility, animation, layout, typography, font pairing, spacing, interaction states, shadow, and gradient. Integrations: shadcn/ui MCP for component search and examples." | `.agents/skills/ui-ux-pro-max/SKILL.md` |
| vue-best-practices | MUST be used for Vue.js tasks. Strongly recommends Composition API with `<script setup>` and TypeScript as the standard approach. Covers Vue 3, SSR, Volar, vue-tsc. Load for any Vue, .vue files, Vue Router, Pinia, or Vite with Vue work. ALWAYS use Composition API unless the project explicitly requires Options API. | `.agents/skills/vue-best-practices/SKILL.md` |
| vueuse-functions | Apply VueUse composables where appropriate to build concise, maintainable Vue.js / Nuxt features. | `.agents/skills/vueuse-functions/SKILL.md` |
| module-scaffold | >- Scaffolds a complete Inertia/Laravel module following the project's full-stack module pattern. Activate when the user asks to add a new module, create CRUD for a model, scaffold a feature, or when the task touches controllers, services, form requests, resources, Vue pages, composables, and types together. | `.cursor/skills/module-scaffold/SKILL.md` |
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->

## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:

- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->

## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
