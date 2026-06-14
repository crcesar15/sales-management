---
name: module-scaffold
description: >-
  Scaffolds a complete Inertia/Laravel module following the project's full-stack
  module pattern. Activate when the user asks to add a new module, create CRUD for
  a model, scaffold a feature, or when the task touches controllers, services,
  form requests, resources, Vue pages, composables, and types together.
---

# Module Scaffold

## When to Apply

Activate this skill when:

- Adding a new domain/module to the application
- Creating full CRUD for a new or existing model
- Scaffolding a feature that needs backend + frontend + API
- The task involves Web Controller + API Controller + Service + Form Requests + Resources + Vue Pages + Composable + Types

## Module Pattern

Every module follows this full-stack pattern:

| Layer | Location |
|-------|----------|
| Migration | `database/migrations/` |
| Model | `app/Models/{Model}.php` |
| Factory | `database/factories/{Model}Factory.php` |
| Web Controller | `app/Http/Controllers/{Module}Controller.php` or `app/Http/Controllers/{Module}/` |
| API Controller | `app/Http/Controllers/Api/{Module}/` |
| Web Form Request | `app/Http/Requests/{Module}/` |
| API Form Request | `app/Http/Requests/Api/{Module}/` |
| Resource | `app/Http/Resources/{Module}/` |
| Service | `app/Services/{Module}Service.php` |
| Vue Pages | `resources/js/Pages/{Module}/` (`Index.vue`, `Create/Index.vue`, `Edit/Index.vue`) |
| Composable | `resources/js/Composables/use{Module}Client.ts` |
| TypeScript Types | `resources/js/Types/{module}-types.ts` |
| Sidebar | `resources/js/Layouts/Composables/useMenuItems.ts` |

## Scaffolding Checklist

### 1. Permissions

- Add cases to `app/Enums/PermissionsEnum.php` using `{MODULE}_{ACTION}` format.
- Standard actions per module: `VIEW`, `CREATE`, `EDIT`, `DELETE`, `RESTORE`.
- Example:
  ```php
  case VENDORS_VIEW = 'vendor.view';
  case VENDORS_CREATE = 'vendor.create';
  case VENDORS_EDIT = 'vendor.edit';
  case VENDORS_DELETE = 'vendor.delete';
  case VENDORS_RESTORE = 'vendor.restore';
  ```
- Register the permission strings in `database/seeders/PermissionSeeder.php`.
- Run `php artisan db:seed --class=PermissionSeeder` after changes.

### 2. Sidebar Menu

- Add an entry in `resources/js/Layouts/Composables/useMenuItems.ts` with `can`:
  ```typescript
  {
    key: "vendors",
    label: t("Vendors"),
    icon: "fa fa-truck",
    to: "vendors",
    can: "vendor.view",
  }
  ```

### 3. Database Layer

- Create migration: `php artisan make:migration --no-interaction create_{table}_table`
- Create model: `php artisan make:model --no-interaction {Model}`
- Create factory: `php artisan make:factory --no-interaction {Model}Factory`
- Use `casts()` method on the model.
- Add PHPDoc generics for relationships.
- Use `$fillable` and define relationships.

### 4. Backend Layer

- **Service** (`app/Services/{Module}Service.php`):
  - Mark class `final`.
  - Use constructor property promotion.
  - Wrap writes in `DB::transaction()`.
  - Use `when()` for conditional filters.
  - Eager load needed relations.
  - Return models loaded with needed relations.

- **Web Form Requests** (`app/Http/Requests/{Module}/`):
  - `Store{Model}Request`, `Update{Model}Request`.
  - Use array-format validation rules.
  - Authorize via `PermissionsEnum`:
    ```php
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::VENDORS_CREATE->value) ?? false;
    }
    ```

- **API Form Requests** (`app/Http/Requests/Api/{Module}/`):
  - Mirror web requests for API endpoints.

- **Web Controller** (`app/Http/Controllers/{Module}Controller.php`):
  - Mark class `final`.
  - Constructor-inject the service.
  - Authorize via `PermissionsEnum`.
  - `index()` returns `Inertia::render('{Module}/Index', [...])`.
  - `create()` returns `Inertia::render('{Module}/Create/Index', [...])`.
  - `store()` calls service and redirects with `redirect()->route('{module}.index')`.
  - `edit()` returns `Inertia::render('{Module}/Edit/Index', [...])`.
  - `update()` calls service and redirects.
  - `destroy()` calls service and redirects.

- **API Controller** (`app/Http/Controllers/Api/{Module}/{Model}Controller.php`):
  - Mark class `final`.
  - Return Eloquent Resources.
  - Use `setStatusCode(201)` for created responses.
  - Include `restore` for soft-deleted resources.

- **Resources** (`app/Http/Resources/{Module}/`):
  - `{Model}Resource` extends `JsonResource`.
  - `{Model}Collection` extends `ResourceCollection` and includes pagination `meta`.
  - Format dates as ISO strings.
  - Use `whenLoaded()` for conditional relationships.

- **Routes** (`routes/web.php` and `routes/api.php`):
  - Web routes use resource-style names: `{module}.index`, `{module}.create`, etc.
  - API routes grouped under `Route::prefix('v1')` and named `api.v1.{module}.{action}`.
  - API routes protected by `auth:sanctum`.

### 5. Frontend Layer

- **Vue Pages** (`resources/js/Pages/{Module}/`):
  - `Index.vue` list view with PrimeVue `DataTable` in lazy mode.
  - `Create/Index.vue` and `Edit/Index.vue` forms using VeeValidate + Yup.
  - Use `defineOptions({ layout: AppLayout })` with `AppLayout` from `@layouts/admin.vue`.
  - Use `router.visit()` for server-side filtering/pagination/sorting.

- **Composable** (`resources/js/Composables/use{Module}Client.ts`):
  - Wrap the shared `useApi()` composable.
  - Provide typed methods for CRUD API calls.

- **Types** (`resources/js/Types/{module}-types.ts`):
  - `{Model}` base interface.
  - `{Model}Response` extends base with `id`, timestamps, relations.
  - `{Model}Payload` for create/update payloads.
  - Named exports only.

## Example File Names

For a `Vendor` module:

```
app/Enums/PermissionsEnum.php
app/Models/Vendor.php
database/factories/VendorFactory.php
database/migrations/2024_01_01_000000_create_vendors_table.php
app/Services/VendorService.php
app/Http/Controllers/VendorsController.php
app/Http/Controllers/Api/Vendors/VendorController.php
app/Http/Requests/Vendors/StoreVendorRequest.php
app/Http/Requests/Vendors/UpdateVendorRequest.php
app/Http/Requests/Api/Vendors/StoreVendorRequest.php
app/Http/Requests/Api/Vendors/UpdateVendorRequest.php
app/Http/Resources/Vendors/VendorResource.php
app/Http/Resources/Vendors/VendorCollection.php
resources/js/Pages/Vendors/Index.vue
resources/js/Pages/Vendors/Create/Index.vue
resources/js/Pages/Vendors/Edit/Index.vue
resources/js/Composables/useVendorClient.ts
resources/js/Types/vendor-types.ts
resources/js/Layouts/Composables/useMenuItems.ts
routes/web.php
routes/api.php
database/seeders/PermissionSeeder.php
```

## Verification

After scaffolding:

1. Run `php artisan db:seed --class=PermissionSeeder`.
2. Run `php artisan route:list --except-vendor` to verify routes.
3. Run `composer lint` after all PHP files are in place.
4. Run `npm run type-check` after TypeScript changes.
5. Run `php artisan test --compact` or add a Pest feature test for the new module.
