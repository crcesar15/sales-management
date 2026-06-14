# Development Commands

## Build & Dev

```bash
composer run dev          # Laravel + Vite dev server (preferred)
npm run dev               # Vite dev server only
npm run build             # Production build
```

## Code Quality (run after all PHP changes are complete, not mid-implementation)

```bash
composer lint                              # Pint (--dirty) + PHPStan (run this, not individually)
npm run lint                               # ESLint check on resources/js
npm run lint:fix                           # ESLint auto-fix
npm run format                             # Prettier on resources/js
npm run type-check                         # TypeScript check (vue-tsc --noEmit)
```

## Artisan

- Always pass `--no-interaction` to Artisan commands
- Create files with `php artisan make:` commands, not manually
- List commands: `php artisan list`
- Check parameters: `php artisan [command] --help`
- Inspect routes: `php artisan route:list --except-vendor` (filter with `--name=users`, `--path=api`)

## Database

```bash
php artisan migrate                        # Run pending migrations
php artisan migrate:rollback               # Rollback last batch
php artisan db:seed --class=PermissionSeeder  # Re-seed permissions after enum changes
```

## Config & Tooling

- **PostToolUse hooks** auto-run linting: Pint + PHPStan on PHP file edits, `npx lint --fix` on JS/TS/Vue edits
- **ESLint**: flat config (ESLint 9) with Vue 3 strongly-recommended + TypeScript recommended
  - Enforces `script-setup` + composition API via `vue/component-api-style`
  - Block order: `[script, template, style]` via `vue/block-order`
  - `vue/multi-word-component-names` with exceptions for Index, Home, Login, Error, Admin, Pos
  - `@typescript-eslint/consistent-type-imports` (inline-type-imports)
  - `@typescript-eslint/consistent-type-definitions` (interface)
- **Pint**: aggressive rules including `declare_strict_types`, `strict_comparison`, `final_class`, `final_internal_class`, `final_public_method_for_abstract_class`, `global_namespace_import`, `ordered_class_elements`, `date_time_immutable`, `mb_str_functions`, `modernize_types_casting`
- **PHPStan**: level 8 (strictest) with Larastan and Carbon extensions
- **Rector**: configured for Laravel up to level 120 with Laravel-specific sets
- **Prettier**: `{ semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140 }`
- **EditorConfig**: 4-space indent for PHP, 2-space for JSON/JS/TS/Vue/YAML, LF line endings, trim trailing whitespace
- **ModelTyper** (`fumeapp/modeltyper`): generates TypeScript types from Eloquent models via `php artisan model:typer`
- **Two Vite entry points**: `resources/js/app.ts` (main app with Aura theme) and `resources/js/login/index.js` (separate login app with Noir theme)
- **No Docker, no CI/CD, no Makefile** in this project