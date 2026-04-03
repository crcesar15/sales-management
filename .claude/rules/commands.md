# Development Commands

## Build & Dev

```bash
composer run dev          # Laravel + Vite dev server (preferred)
npm run dev               # Vite dev server only
npm run build             # Production build
```

## Code Quality (run after all PHP changes are complete, not mid-implementation)

```bash
composer lint                              # Pint + PHPStan (run this, not individually)
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
