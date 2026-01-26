# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sales Management System built with Laravel 12 (PHP 8.3+) backend and Vue 3 + TypeScript frontend using Inertia.js for SPA-like experience.

## Commands

### Development
```bash
npm run dev          # Start Vite dev server with HMR
php artisan serve    # Start Laravel dev server
```

### Build
```bash
npm run build        # Build frontend assets for production
```

### Testing
```bash
./vendor/bin/pest                    # Run all tests
./vendor/bin/pest tests/Feature      # Run feature tests only
./vendor/bin/pest --filter=TestName  # Run specific test
```

### Code Quality
```bash
./vendor/bin/pint                    # Fix PHP code style (Laravel Pint)
./vendor/bin/pint --test             # Check PHP style without fixing
./vendor/bin/phpstan analyse         # Run static analysis (level max)
./vendor/bin/rector                  # Run Rector refactoring
./vendor/bin/rector --dry-run        # Preview Rector changes
npm run type-check                   # TypeScript type checking
npx eslint resources/js              # Lint Vue/TypeScript files
```

### Laravel
```bash
php artisan migrate                  # Run migrations
php artisan model:typer              # Generate TypeScript types from models
php artisan ide-helper:models        # Generate IDE helper for models
```

## Architecture

### Backend Structure
- **Controllers**: Web controllers in `app/Http/Controllers/`, API controllers in `app/Http/Controllers/Api/`
- **Routes**: Web routes use Inertia rendering (`routes/web.php`), API routes use Sanctum auth (`routes/api.php`)
- **Enums**: Permission system uses `PermissionsEnum` with dot notation (e.g., `product.view`, `brand.create`)
- **Services**: Business logic in `app/Services/` (e.g., `VariantService`)
- **Media**: Uses Spatie Media Library with custom `MediaLibrary` service

### Frontend Structure
- **Entry**: `resources/js/app.ts` - Vue app setup with PrimeVue, i18n, Inertia
- **Pages**: `resources/js/Pages/` - Inertia page components organized by feature
- **Layouts**: `resources/js/Layouts/admin.vue` - Main admin layout
- **Composables**: `resources/js/Composables/` - Vue composables for API clients (e.g., `useProductClient`, `useBrandClient`)
- **Types**: `resources/js/Types/` - TypeScript type definitions mirroring Laravel models
- **Directives**: Custom `v-can` directive for permission-based element visibility

### Path Aliases (TypeScript/Vite)
```
@/           → resources/js/
@components  → resources/js/Components/
@pages       → resources/js/Pages/
@composables → resources/js/Composables/
@app-types   → resources/js/Types/
@layouts     → resources/js/Layouts/
@directives  → resources/js/Directives/
```

### Key Integrations
- **Inertia.js**: SPA without API, pages rendered server-side with `Inertia::render()`
- **PrimeVue**: UI component library with Aura theme preset
- **Ziggy**: Laravel routes available in JavaScript via `route()` helper
- **Spatie Permissions**: Role-based access control
- **Spatie Media Library**: File uploads and media management
- **vue-i18n**: Internationalization (Spanish default)

## Code Style

### PHP
- Strict types required (`declare(strict_types=1)`)
- Classes should be `final` where possible
- Use strict comparison (`===`)
- Follow Pint Laravel preset with custom rules in `pint.json`
- Class element ordering: traits → constants → properties → construct → methods

### TypeScript/Vue
- Double quotes for strings
- Semicolons required
- Max line length: 140 characters
- Use camelCase for props in templates (`vue/attribute-hyphenation: never`)
- Prefix unused variables with underscore

## Testing

Uses Pest PHP testing framework with SQLite in-memory database for tests.