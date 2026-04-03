# Settings — Database

## Tables Used

### `settings`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `key` | VARCHAR(100) | UNIQUE — machine-readable key (e.g., `tax_rate`) |
| `value` | TEXT | NULLABLE — stored as string for all types |
| `name` | VARCHAR(150) | Human-readable label (e.g., `"Tax Rate (%)"`) |
| `group` | VARCHAR(50) | Grouping for UI display (e.g., `general`, `tax`, `receipt`, `inventory`) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

## Indexes
- `settings.key` — UNIQUE
- `settings.group` — INDEX (for grouped queries)

## Migration

```php
// database/migrations/xxxx_create_settings_table.php
public function up(): void
{
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('key', 100)->unique();
        $table->text('value')->nullable();
        $table->string('name', 150);
        $table->string('group', 50)->default('general');
        $table->timestamps();

        $table->index('group');
    });
}

public function down(): void
{
    Schema::dropIfExists('settings');
}
```

## Seeder Data

```php
// database/seeders/SettingsSeeder.php

$settings = [
    // general
    ['key' => 'business_name',    'value' => 'My Store', 'name' => 'Business Name',    'group' => 'general'],
    ['key' => 'business_address', 'value' => '',         'name' => 'Business Address', 'group' => 'general'],
    ['key' => 'business_phone',   'value' => '',         'name' => 'Business Phone',   'group' => 'general'],
    ['key' => 'timezone',         'value' => 'UTC',      'name' => 'Timezone',         'group' => 'general'],

    // tax
    ['key' => 'tax_rate', 'value' => '0', 'name' => 'Tax Rate (%)', 'group' => 'tax'],
];
```

## Query Patterns

```php
// Get a single setting by key
Setting::where('key', 'tax_rate')->value('value');

// Get all settings for a group
Setting::where('group', 'general')->pluck('value', 'key');

// Update a setting
Setting::where('key', 'tax_rate')->update(['value' => '10']);

// Get all settings grouped by group
Setting::all()->groupBy('group');
```

## Caching Strategy

Settings are cached under a tagged key to allow bulk invalidation:

```php
// Cache key pattern
'settings.{key}'        // individual key
'settings.group.{group}' // all keys in a group
```

The `Setting::get()` / `Setting::set()` facade/helper handles caching transparently. On any update, the entire settings cache is flushed:

```php
Cache::tags(['settings'])->flush();
```

> If using a cache driver that does not support tags (e.g., `file`, `database`), use a versioned cache key or `Cache::forget('settings.all')` instead.

## Type Reference

| Key | Stored Value | Cast To |
|---|---|---|
| `business_name` | string | string |
| `business_address` | string | string |
| `business_phone` | string | string |
| `timezone` | string | string |
| `tax_rate` | `"10.5"` | float |
