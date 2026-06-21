# External Integrations

**Analysis Date:** 2026-06-21

## APIs & External Services

**No external third-party API integrations detected.**

This application is a self-contained sales management system. All "API" calls are internal — the Laravel backend serves both the Inertia-rendered web pages (`routes/web.php`) and a JSON API (`routes/api.php`, prefixed `v1`, protected by `auth:sanctum`) consumed by the same Vue SPA via Axios (`resources/js/Composables/useApi.ts`).

- **Internal JSON API** — Same-origin API at `/api/v1/*`; consumed via Axios client in `resources/js/Composables/useApi.ts` (baseURL `${window.location.hostname}/api/`, `withCredentials: true`, XSRF token header). Composable wrappers: `useBrandClient.ts`, `useCustomerClient.ts`, `useProductClient.ts`, `useVendorClient.ts`, `useUserClient.ts`, `useRoleClient.ts`, `usePurchaseOrderClient.ts`, `usePosClient.ts`, `useMediaClient.ts`, `useSettingClient.ts`, `useActivityLogClient.ts`, `usePermissionClient.ts`, `useCategoryClient.ts`, `useMeasurementUnitClient.ts`, `useVariantClient.ts`.
- **No external SDK imports** — No Stripe, PayPal, MercadoPago, Supabase, AWS SDK, Twilio, or similar service imports found in `app/` or `resources/js/`.
- **Payments are internal** — `app/Enums/PaymentMethod.php` defines cash/transfer-style methods (internal record-keeping via `SalesOrderPayment` model), not external payment gateway calls.

**Configured-but-unused external service slots** (Laravel defaults present in config, not actively integrated):
- **Mailgun** — `config/services.php` `mailgun` block (key: `MAILGUN_DOMAIN`, `MAILGUN_SECRET`); no Mailable classes or mail-sending code found in `app/`.
- **Postmark** — `config/services.php` `postmark` block (key: `POSTMARK_TOKEN`); not used.
- **AWS SES** — `config/services.php` `ses` block (keys: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`); not used.
- **Pusher** — `config/broadcasting.php` `pusher` block (keys: `PUSHER_APP_*`); `BROADCAST_DRIVER` defaults to `null` in `.env.example`; `BroadcastServiceProvider` is commented out in `config/app.php` providers. Only a default user channel authorization is registered in `routes/channels.php`.

## Data Storage

**Databases:**
- **MySQL** — Primary database; default connection `mysql` (`config/database.php`).
  - Connection env vars: `DB_CONNECTION` (default `mysql`), `DB_HOST` (default `127.0.0.1`), `DB_PORT` (default `3306`), `DB_DATABASE` (default `laravel`), `DB_USERNAME`, `DB_PASSWORD`
  - Client/ORM: Laravel Eloquent ORM; charset `utf8mb4`, collation `utf8mb4_unicode_ci`, strict mode enabled
  - Alternative connections configured: `sqlite`, `pgsql`, `sqlsrv` (sqlite used in tests via `phpunit.xml` with `:memory:`)
- **Redis** — Optional, configured in `config/database.php` `redis` block; env vars `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`. Used for cache store (`config/cache.php` `redis` store) and queue (`config/queue.php` `redis` connection). Not required — defaults to `file` cache, `sync` queue, `file` session.
- **Memcached** — Optional, configured in `config/cache.php`; env var `MEMCACHED_HOST`. Not required.

**File Storage:**
- **Local filesystem** — Default disk `local` (env `FILESYSTEM_DISK=local`); media disk `public` (env `MEDIA_DISK=public`) per `config/media-library.php`. Storage symlink: `public/storage` → `storage/app/public` (configured in `config/filesystems.php` `links`).
- **AWS S3** — Configured in `config/filesystems.php` `s3` disk (keys: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`); not the default and no evidence of active use.
- **spatie/laravel-medialibrary** — Manages media (product images, purchase order proof-of-payment). Custom path generator `app/Services/CustomPathGeneratorService.php` hashes paths via `md5($media->id . config('app.key'))`. Two-phase upload via `app/Services/PendingMediaService.php` (temp upload → commit on save). Image conversions (thumb) configured on models implementing `HasMedia`.

**Caching:**
- Laravel Cache facade — Default driver `file` (env `CACHE_DRIVER=file`). Stores: `array`, `database`, `file`, `memcached`, `redis`, `dynamodb`, `apc`, `octane`.
- **Settings cache** — `Setting::get()` wraps `Cache::rememberForever()` for cached application settings retrieval (`app/Models/Setting.php`, used by `app/Services/SettingsService.php`). Called in `BatchService`, `StockAlertService`, `SalesOrderService`, `Batch` model.
- **Permission cache** — `spatie/laravel-permission` caches permissions/roles for 24 hours (`config/permission.php` `cache.expiration_time`), key `spatie.permission.cache`, flushed on role/permission updates.

## Authentication & Identity

**Auth Provider:**
- **Custom (Laravel built-in)** — No external identity provider (no Auth0, Okta, Firebase, etc.).
  - Implementation: Session-based auth for web (`config/auth.php` guard `web` → driver `session`, provider `users` → Eloquent `App\Models\User`). Login uses `username` field, not `email` (`LoginController::username()` returns `'username'`).
  - API auth: Laravel Sanctum (`laravel/sanctum` 4.3.1) — token-based for `routes/api.php` under `auth:sanctum` middleware; config `config/sanctum.php`. Stateful domains include localhost and app URL.
  - Authorization: `spatie/laravel-permission` 6.25.0 — RBAC via roles + permissions defined in `app/Enums/PermissionsEnum.php` (dot notation, e.g. `brand.view`) and `app/Enums/RolesEnum.php` (descriptive values). Enforcement in controllers, form requests, and policies (`app/Policies/`). Frontend `v-can` directive (`resources/js/Directives/can.ts`) and `useAuth()` composable.
  - Password hashing: bcrypt (config `config/hashing.php`); test rounds `4` (`phpunit.xml`).
  - Password resets: `config/auth.php` `passwords.users` (table `password_reset_tokens`, expire 60 min, throttle 60 sec).

## Monitoring & Observability

**Error Tracking:**
- `spatie/laravel-ignition` 2.12.0 — Local dev error page with actionable solutions; not a remote error tracking service.
- `barryvdh/laravel-debugbar` 3.16.5 — Local dev debug bar (registered in `config/app.php` providers, aliased as `Debugbar`).
- No remote error tracking (Sentry, Bugsnag, etc.) detected.

**Logs:**
- Laravel logging via Monolog; config `config/logging.php`. Default channel `stack`. Supports `single`, `daily`, `slack`, `syslog`, `errorlog`, `null` drivers.
- **Activity log** — `spatie/laravel-activitylog` 4.12.3 stores audit trail in `activity_log` table (migrations `database/migrations/2026_02_08_002614_create_activity_log_table.php` and additions). All models use `LogsActivity` trait with standardized `getActivitylogOptions()` (`logFillable`, `logOnlyDirty`, `useLogName`, `dontSubmitEmptyLogs`). Services log explicit events via `activity()` helper (e.g., `app/Services/PurchaseOrderService.php`, `app/Services/BatchService.php`, `app/Services/StockAdjustmentService.php`, `app/Services/CashRegisterShiftService.php`).
- Known issue: `storage/logs/laravel.log` has permission issues — testing rules advise using `getJson()` instead of `get()` for forbidden assertions to avoid log write failures (see `.claude/rules/testing.md`).

## CI/CD & Deployment

**Hosting:**
- Not specified — no Docker, CI/CD, or Makefile in project (per AGENTS.md). Standard Laravel deployment (PHP server + web root `public/`).

**CI Pipeline:**
- None configured. No `.github/workflows/`, no `.gitlab-ci.yml`, no `Jenkinsfile`. A `.github/` directory exists but contains no CI workflows (verified by absence of workflow files).
- Lint commands run manually: `composer lint` (Pint + PHPStan), `npm run lint`, `npm run type-check`, `npm run format`.
- PostToolUse hooks auto-run linting on file edits (Pint+PHPStan on PHP, `npx lint --fix` on JS/TS/Vue) per `.claude/rules/commands.md`.

## Environment Configuration

**Required env vars** (from `.env.example` — values NOT read, only keys):
- App: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- Database: `DB_CONNECTION` (default `mysql`), `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Cache/Session/Queue: `CACHE_DRIVER` (default `file`), `SESSION_DRIVER` (default `file`), `QUEUE_CONNECTION` (default `sync`), `SESSION_LIFETIME`
- Filesystem/Broadcast: `FILESYSTEM_DISK` (default `local`), `BROADCAST_DRIVER` (default `log` in example, `null` in broadcasting config)
- Mail: `MAIL_MAILER` (default `smtp`), `MAIL_HOST` (default `mailpit`), `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- Optional Redis: `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`
- Optional AWS (S3/SES/SQS): `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT`
- Optional Pusher: `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_HOST`, `PUSHER_PORT`, `PUSHER_SCHEME`, `PUSHER_APP_CLUSTER`
- Optional Media: `MEDIA_DISK` (default `public`), `IMAGE_DRIVER` (default `gd`), `FFMPEG_PATH`, `FFPROBE_PATH`, `MEDIA_DOWNLOADER_SSL`
- Optional Sanctum: `SANCTUM_STATEFUL_DOMAINS`, `SANCTUM_TOKEN_PREFIX`
- Vite-exposed: `VITE_APP_NAME`, `VITE_PUSHER_APP_KEY`, `VITE_PUSHER_HOST`, `VITE_PUSHER_PORT`, `VITE_PUSHER_SCHEME`, `VITE_PUSHER_APP_CLUSTER`

**Secrets location:**
- `.env` file (gitignored, present in working dir — contents NOT inspected)
- `.env.testing`, `.env.backup`, `.env.production` also gitignored
- No vault/secret manager integration detected

## Webhooks & Callbacks

**Incoming:**
- None. No webhook endpoints in `routes/web.php` or `routes/api.php`. No signature-verification middleware usage detected beyond the standard `ValidateSignature` middleware (present in `app/Http/Middleware/` but not assigned to routes).

**Outgoing:**
- None. No outbound webhook/notification code detected. The app does not call external services on events — activity logging writes to the local `activity_log` database table only.

---

*Integration audit: 2026-06-21*