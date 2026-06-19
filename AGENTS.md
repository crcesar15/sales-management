# AGENTS.md

This is a sales management application. opencode loads this file automatically as project instructions. The full project guide lives in `CLAUDE.md` and the detailed rules live in `.claude/rules/` — both are also loaded via `opencode.json` `instructions`, so read them before making changes.

## Stack

- Laravel 12 (kept Laravel 10 directory structure)
- Inertia.js v1 server + v2 client
- Vue 3 + TypeScript
- PrimeVue 4
- Tailwind CSS 3
- Pest 3
- spatie/laravel-permission + spatie/laravel-activitylog + spatie/laravel-medialibrary

## Module Pattern

Every module follows the same full-stack pattern. When working on a feature, check sibling files and mirror this structure:

| Layer | Location |
|-------|----------|
| Web Controller | `app/Http/Controllers/{Module}/` |
| API Controller | `app/Http/Controllers/Api/{Module}/` |
| Service | `app/Services/{Module}Service.php` |
| Web Form Request | `app/Http/Requests/{Module}/` |
| API Form Request | `app/Http/Requests/Api/{Module}/` |
| Resource | `app/Http/Resources/{Module}/` |
| Vue Pages | `resources/js/Pages/{Module}/` |
| Composable | `resources/js/Composables/use{Module}Client.ts` |
| TypeScript Types | `resources/js/Types/{module}-types.ts` |

## Non-Negotiable Conventions

- Keep Laravel 10 structure: middleware in `app/Http/Middleware/`, kernel in `app/Http/Kernel.php`, providers in `app/Providers/`. Do not migrate to the Laravel 12 streamlined structure.
- Mark controller and service classes `final`.
- Use Form Request classes for all validation; never inline validation in controllers.
- Authorize via `PermissionsEnum` in controllers and form requests (Web controllers use `$this->authorize(...)`, API controllers use `$this->authorize(...->value, auth()->user())`).
- Put business logic in services, wrap critical operations in `DB::transaction()`.
- Use `casts()` method on models, not the `$casts` property.
- All models use the `LogsActivity` trait with standardized `getActivitylogOptions()`.
- Throw `InvalidArgumentException` for business rule violations (not custom exception classes).
- Vue forms use VeeValidate + Yup (`useForm` from `vee-validate`, `toTypedSchema` from `@vee-validate/yup`), not Inertia's `useForm` (Inertia `useForm` is only for delete/restore with empty body).
- PrimeVue components are imported directly from `primevue`.
- Use Ziggy's `route()` helper; never hardcode URLs.
- TypeScript types mirror API resources and use named exports.
- Run `composer lint` (Pint + PHPStan) after all PHP changes are complete — not mid-implementation.
- Run `npm run lint` / `npm run type-check` after frontend changes.
- Do not add extra migrations for modifications; modify existing migrations during development and use `migrate:fresh`.

## Documentation

- Read `CLAUDE.md` for the full project guide.
- Read `.claude/rules/*.md` for the detailed, per-layer conventions:
  - `laravel-backend.md` — controllers, services, form requests, resources, models, migrations, factories, policies, enums
  - `vue-frontend.md` — page structure, Inertia props, forms, composables, PrimeVue, i18n, TypeScript, styling
  - `routes-and-api.md` — web/API route naming, API response format, Ziggy usage
  - `authorization.md` — `PermissionsEnum` format and enforcement at each layer
  - `testing.md` — Pest conventions, running tests, factory usage, known issues
  - `commands.md` — build, dev, lint, artisan, and database commands

## Subagents

Project-specific subagents are available via `@mention` (defined in `.opencode/agent/`):

- `@crud-generator` — scaffold a full CRUD stack for a model
- `@module-scaffold` — scaffold a complete Inertia module from scratch
- `@permission-setup` — wire up permissions, roles, and menu for a module
- `@refactoring` — align an existing module with project patterns
- `@test-writer` — write Pest 3 feature tests for a module
- `@vue-page-builder` — build Inertia Vue 3 pages with PrimeVue + Tailwind

## MCP

The `laravel-boost` MCP server (database schema, query, docs search, logs) is configured in `opencode.json` and runs via `php artisan boost:mcp`.