# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sales management application built with Laravel 12 (upgraded from Laravel 10, keeping Laravel 10 directory structure), Inertia.js v1 (server) + v2 (client), Vue 3, PrimeVue 4, Tailwind CSS 3, and TypeScript. Uses Pest for testing and spatie/laravel-permission for authorization.

## Architecture

### Module Pattern

Every module follows this full-stack pattern:

| Layer | Location | Purpose |
|-------|----------|---------|
| Web Controller | `app/Http/Controllers/{Module}/` | Renders Inertia pages with `Inertia::render()` |
| API Controller | `app/Http/Controllers/Api/{Module}/` | Returns JSON via Eloquent Resources |
| Service | `app/Services/` | Business logic, DB transactions, activity logging |
| Form Request (Web) | `app/Requests/{Module}/` | Validation + authorization for web routes |
| Form Request (API) | `app/Http/Requests/Api/{Module}/` | Validation + authorization for API routes |
| Resource | `app/Http/Resources/` | JSON transformation for API responses |
| Vue Pages | `resources/js/Pages/{Module}/` | `Index.vue`, `Create/Index.vue`, `Edit/Index.vue` |
| Composable | `resources/js/Composables/` | API client calls (e.g., `useUserClient`) |
| Types | `resources/js/Types/` | TypeScript interfaces mirroring API resources |

### Laravel 10 Structure (Important)

This project uses Laravel 12 but retains the Laravel 10 directory structure:
- Middleware: `app/Http/Middleware/`
- Kernel: `app/Http/Kernel.php`
- Exception handler: `app/Exceptions/Handler.php`
- Console kernel: `app/Console/Kernel.php`
- Service providers: `app/Providers/`
- Do not migrate to the new Laravel structure unless explicitly requested

### Key Packages

- **spatie/laravel-permission** — Role/permission management
- **spatie/laravel-activitylog** — Audit trail (`LogsActivity` trait)
- **spatie/laravel-medialibrary** — File/media management
- **tightenco/ziggy** — Named routes in JavaScript
- **laravel/sanctum** — API authentication

## Conventions

- Always use Form Request classes for validation, never inline in controllers
- Web controllers use Inertia redirects (back, route); API controllers return Resources
- Use `php artisan make:` commands with `--no-interaction` to create new files
- Run `composer lint` after all PHP changes are complete (not mid-implementation)
- Use `search-docs` MCP tool before making code changes for version-specific documentation
- Check sibling files for conventions before creating new files in an existing directory

Detailed rules are in `.claude/rules/`.
