# Technology Stack

**Analysis Date:** 2026-06-21

## Languages

**Primary:**
- PHP 8.3.10 — Backend application logic, all code under `app/`, `config/`, `database/`, `routes/`, `tests/`
- TypeScript 5.9.3 — Frontend Vue 3 SPA under `resources/js/`
- Vue 3.5.32 (SFC `.vue` files) — UI components and pages under `resources/js/Pages/`, `resources/js/Layouts/`, `resources/js/Components/`

**Secondary:**
- SCSS — Styling entry at `resources/sass/app.scss` (compiled via Vite)
- SQL — Migrations under `database/migrations/` (anonymous class format)
- JSON — i18n translation files at `resources/lang/en.json`, `resources/lang/es.json`

## Runtime

**Environment:**
- PHP 8.3.10 (CLI NTS) — requires `^8.3` per `composer.json`
- Node.js 22.23.0 — required for Vite build/dev tooling
- Laravel Framework 12.56.0 (running on retained Laravel 10 directory structure)

**Package Manager:**
- Composer 2.x — PHP dependency manager; lockfile `composer.lock` present
- npm 10.9.8 — JS dependency manager; lockfile `package-lock.json` present

## Frameworks

**Core:**
- Laravel Framework 12.56.0 — Backend application framework (`composer.json` requires `^12.0`)
- Inertia.js v1.3.4 server (`inertiajs/inertia-laravel`) — Bridges Laravel to Vue SPA without separate API for page rendering
- Inertia.js v2.3.21 client (`@inertiajs/vue3`) — Vue-side Inertia integration
- Vue 3.5.32 — Frontend UI framework (Composition API + `<script setup lang="ts">`)
- PrimeVue 4.5.5 — UI component library; components imported directly from `primevue`
- Tailwind CSS 3.4.19 — Utility-first CSS; config at `tailwind.config.js`
- Vite 7.3.2 — Asset bundler/dev server; config at `vite.config.ts`

**Testing:**
- Pest 3.8.6 — PHP test framework (`pestphp/pest` + `pestphp/pest-plugin-laravel` 3.2.0); config via `tests/Pest.php`, `phpunit.xml`
- PHPUnit 11.5.50 — Underlying test runner (used by Pest)

**Build/Dev:**
- Vite 7.3.2 with `@vitejs/plugin-vue` 6.0.6 and `laravel-vite-plugin` 2.1.0
- Sass 1.56.1 — SCSS compilation
- PostCSS with Tailwind plugin (inline in `vite.config.ts`)

**Code Quality (PHP):**
- Laravel Pint 1.29.0 — PHP formatter/linter; config `pint.json`; preset `laravel` with strict rules (`declare_strict_types`, `final_class`, `final_internal_class`, `final_public_method_for_abstract_class`, `global_namespace_import`, `ordered_class_elements`, `date_time_immutable`, `mb_str_functions`, `modernize_types_casting`)
- PHPStan 2.1.46 — Static analysis at level 8; config `phpstan.neon.dist` with Larastan + Carbon extensions
- Larastan 3.9.3 — Laravel-specific PHPStan extension
- Rector 2.x (`driftingly/rector-laravel` 2.2.0) — Automated refactoring; config `rector.php`; Laravel sets up to level 120
- Laravel IDE Helper 3.6 — Generates `_ide_helper.php`, `_ide_helper_models.php`

**Code Quality (JS/TS):**
- ESLint 9.39.4 — Flat config at `eslint.config.js`; Vue 3 strongly-recommended + TypeScript recommended
- @typescript-eslint 8.58.2 — TypeScript ESLint rules; enforces `consistent-type-imports` (inline), `consistent-type-definitions` (interface)
- Prettier 3.8.3 — Code formatter; config `.prettierrc` (`semi: true, singleQuote: false, tabWidth: 2, trailingComma: "all", printWidth: 140`)
- vue-tsc 3.2.6 — Vue TypeScript type checking (`npm run type-check` → `vue-tsc --noEmit`)

**Dev Tooling:**
- Laravel Tinker 2.11.1 — REPL
- Laravel Sail 1.56.0 — Docker dev environment (available, not used per AGENTS.md: "No Docker")
- Laravel Boost 2.4.1 — MCP server for AI-assisted development (`php artisan boost:mcp`); config `boost.json`, `.mcp.json`
- Laravel Debugbar 3.16.5 — Dev-only debug bar; registered in `config/app.php` providers
- Laravel Ignition 2.12.0 (Spatie) — Error page improvements

## Key Dependencies

**Critical (Backend):**
- `spatie/laravel-permission` 6.25.0 — Role/permission RBAC; config `config/permission.php`; enums `app/Enums/PermissionsEnum.php`, `app/Enums/RolesEnum.php`
- `spatie/laravel-activitylog` 4.12.3 — Audit trail via `LogsActivity` trait on all models + `activity()` helper in services
- `spatie/laravel-medialibrary` 11.21.0 — Media/file management; config `config/media-library.php`; custom path generator `app/Services/CustomPathGeneratorService.php`
- `spatie/image` 3.9.4 + `spatie/image-optimizer` 1.8.1 — Image manipulation/optimization (used by medialibrary)
- `tightenco/ziggy` 2.6.2 — Exposes named routes to JS; client `ziggy-js` 2.6.2; `route()` helper used in all Vue pages
- `laravel/sanctum` 4.3.1 — API token authentication; config `config/sanctum.php`; middleware `auth:sanctum` on API routes
- `laravel/ui` 4.6.3 — Auth scaffolding (login controllers/views)
- `guzzlehttp/guzzle` 7.10.0 — HTTP client (dependency of framework/medialibrary; no direct external API usage detected in app code)

**Critical (Frontend):**
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

**Infrastructure:**
- `fumeapp/modeltyper` 3.10.0 — Generates TypeScript types from Eloquent models (`php artisan model:typer`)
- `barryvdh/laravel-debugbar` 3.16.5 — Dev debug bar
- `barryvdh/laravel-ide-helper` 3.6 — IDE autocompletion helpers
- `fakerphp/faker` 1.24.1 — Test data generation in factories
- `mockery/mockery` 1.6.12 — Mocking for tests
- `nunomaduro/collision` 8.9.2 — Pretty error reporting (Pest)

## Configuration

**Environment:**
- `.env` file present (contains environment configuration — NOT read here per security policy)
- `.env.example` template at `.env.example` (59 lines) defines: `APP_*`, `DB_*` (mysql default), `CACHE_DRIVER=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `FILESYSTEM_DISK=local`, `MAIL_*` (SMTP/Mailpit), `AWS_*`, `PUSHER_*`, `REDIS_*`, `VITE_*`
- Timezone: `UTC` (`config/app.php`); locale `en` (app), `es` (frontend i18n default)
- Cipher: `AES-256-CBC` for encryption

**Build:**
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

**Development:**
- PHP 8.3+ with extensions: pdo_mysql, gd (image driver, `config/media-library.php`), mbstring
- Node.js 22+ and npm 10+
- Composer 2.x
- MySQL/MariaDB for local dev (default `DB_CONNECTION=mysql`, host `127.0.0.1:3306`)
- Optional: Redis (`REDIS_HOST`), FFmpeg/FFprobe (`FFMPEG_PATH` for video thumbnails in medialibrary), image optimizers (jpegoptim, pngquant, optipng, svgo, gifsicle, cwebp, avifenc — configured in `config/media-library.php`)
- Run dev: `composer run dev` (Laravel + Vite dev server) or `npm run dev` (Vite only)

**Production:**
- PHP 8.3+ server (FPM/Swoole/Octane-capable; Octane reset listener disabled in `config/permission.php`)
- MySQL 8+ (charset `utf8mb4`, collation `utf8mb4_unicode_ci`, strict mode)
- Web server with PHP support (Nginx + PHP-FPM typical)
- Filesystem: local disk (default `public` disk for media via symlink `public/storage` → `storage/app/public`)
- No Docker/CI/CD/Makefile configured in this project (per AGENTS.md)
- Build assets: `npm run build` before deploy

---

*Stack analysis: 2026-06-21*