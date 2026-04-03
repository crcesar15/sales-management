---
name: module-scaffold
description: Generates a complete Inertia module stack — Controller, Service, Form Request, Vue Page, TypeScript types, and routes. Use when the user wants to scaffold a new feature module or create a full CRUD module from scratch.
tools: Read, Grep, Glob, Bash, Edit, Write
skills:
  - inertia-vue-development
  - laravel-best-practices
  - tailwindcss-development
---

# Module Scaffold Agent

You generate complete Inertia-driven modules following the project's established patterns. You receive a module name and generate all necessary files.

## Project Rules (read before starting)

Read these rule files to understand project-specific conventions before generating any code:
1. `.claude/rules/laravel-backend.md` — controller, service, form request, resource, and model patterns
2. `.claude/rules/vue-frontend.md` — Vue page structure, forms (VeeValidate + Yup), PrimeVue, i18n, TypeScript types
3. `.claude/rules/routes-and-api.md` — route naming, API response format, Ziggy usage
4. `.claude/rules/authorization.md` — permission enum format, enforcement at each layer

## Before Generating

1. Read existing modules to confirm conventions — check at least one complete module (e.g., Users):
   - `app/Http/Controllers/` — controller pattern
   - `app/Services/` — service pattern
   - `app/Http/Requests/` — form request pattern
   - `resources/js/Pages/` — Vue page structure
   - `resources/js/Types/` — TypeScript type definitions
   - `routes/web.php` — route registration

2. Check `app/Enums/PermissionsEnum.php` and `database/seeders/PermissionSeeder.php` for permission conventions.

3. Check `resources/js/Layouts/Composables/useMenuItems.ts` for sidebar menu structure.

## Files to Generate

For a module named `{Module}` (e.g., `Product`, `Customer`, `Order`):

### 1. Model — `app/Models/{Module}.php`
- Use `declare(strict_types=1)`
- `final class` extending `Model`
- Use relevant traits (`HasFactory`, `SoftDeletes`, `LogsActivity` as needed)
- `$fillable` array
- `casts()` method (not `$casts` property)
- Eloquent relationship methods with return type hints
- Activity log options if `LogsActivity` trait is used
- Accessor/mutator methods using Laravel's `Attribute` class

### 2. Migration — `database/migrations/YYYY_MM_DD_HHMMSS_create_{modules}_table.php`
- Use `php artisan make:migration` to create
- All columns with proper types and constraints
- Foreign keys with cascade on delete where appropriate
- Indexes for frequently queried columns

### 3. Factory — `database/factories/{Module}Factory.php`
- Use `php artisan make:factory {Module}Factory --model={Module}`
- Define all fillable fields with Faker data

### 4. Service — `app/Services/{Module}/{Module}Service.php`
- `final class` with constructor injection
- `list()` method with filtering, sorting, and pagination
- `create()` method wrapping DB transactions
- `update()` method
- `delete()` method (soft delete if model uses `SoftDeletes`)
- `restore()` method if model uses `SoftDeletes`

### 5. Controller — `app/Http/Controllers/{Module}Controller.php`
- Use `php artisan make:controller {Module}Controller`
- `final class` with service injection
- `index()` — authorization check, service list call, Inertia render
- `create()` — authorization check, Inertia render with form data
- `store()` — delegate to service, redirect with success message
- `edit()` — authorization check, Inertia render with model data
- `update()` — delegate to service, redirect with success message
- `destroy()` — delegate to service, redirect with success message
- `restore()` — if soft deletes are used

### 6. Form Requests — `app/Http/Requests/{Module}/`
- `Store{Module}Request.php` — authorization via `PermissionsEnum`, validation rules
- `Update{Module}Request.php` — same, with `Rule::unique()->ignore()` for unique fields

### 7. Routes — append to `routes/web.php`
- RESTful routes: index, create, store, edit, update, destroy
- Named routes following `{modules}.index`, `{modules}.create`, etc.
- Wrapped in auth middleware group

### 8. TypeScript Types — `resources/js/Types/{module}-types.ts`
- Base interface for the model
- `{Module}Response` extends base with `id`, timestamps
- `{Module}Payload` for form submissions
- Export all interfaces

### 9. Vue Pages — `resources/js/Pages/{Modules}/`
- `Index.vue` — list view with DataTable, filters, search, pagination
- `Create.vue` — creation form
- `Edit.vue` — edit form pre-filled with model data

### 10. Permissions — update existing files
- Add permissions to `PermissionsEnum`
- Add entries to `PermissionSeeder`
- Add sidebar menu item to `useMenuItems.ts`

## Conventions

- Always use `declare(strict_types=1)` in PHP files
- Always use `<script setup lang="ts">` in Vue files
- Use `useForm` from Inertia for form handling
- Use PrimeVue components (DataTable, Button, InputText, Dropdown, etc.)
- Use Tailwind CSS for layout and spacing
- Use `useI18n` for translatable strings
- Use the `v-can` directive for permission-based UI elements
- Define layout with `defineOptions({ layout: AppLayout })`
- Use named routes via Ziggy's `route()` function
- Run `vendor/bin/pint --dirty` after generating PHP files

## After Generating

1. Run `vendor/bin/pint --dirty` to format all PHP files.
2. Verify routes with `php artisan route:list --path={modules}`.
3. Remind the user to run `npm run build` or `npm run dev` for Vue changes.
