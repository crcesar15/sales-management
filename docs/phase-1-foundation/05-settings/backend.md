# Settings — Backend

## Implementation Steps

### 1. Model

```
app/Models/Setting.php
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'name', 'group'];

    // ─── Static Helper Methods ────────────────────────────────────────────────

    /**
     * Get a setting value by key, with optional default.
     * Value is cast to the appropriate type automatically.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::tags(['settings'])->rememberForever(
            "settings.{$key}",
            fn () => static::where('key', $key)->value('value')
        );

        if ($value === null) {
            return $default;
        }

        return static::castValue($key, $value);
    }

    /**
     * Set a setting value by key and flush the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => (string) $value]);
        Cache::tags(['settings'])->flush();
    }

    /**
     * Get all settings for a group as a key-value array.
     */
    public static function group(string $group): array
    {
        return Cache::tags(['settings'])->rememberForever(
            "settings.group.{$group}",
            fn () => static::where('group', $group)->pluck('value', 'key')->toArray()
        );
    }

    // ─── Type Casting ─────────────────────────────────────────────────────────

    private static array $booleanKeys = ['show_logo'];
    private static array $integerKeys = ['low_stock_default_threshold', 'expiry_alert_days'];
    private static array $floatKeys   = ['tax_rate'];

    private static function castValue(string $key, string $value): mixed
    {
        if (in_array($key, static::$booleanKeys)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, static::$integerKeys)) {
            return (int) $value;
        }

        if (in_array($key, static::$floatKeys)) {
            return (float) $value;
        }

        return $value;
    }
}
```

---

### 2. Seeder

```
database/seeders/SettingsSeeder.php
```

```php
<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'store_name',    'value' => 'My Store', 'name' => 'Store Name',    'group' => 'general'],
            ['key' => 'store_address', 'value' => '',         'name' => 'Store Address', 'group' => 'general'],
            ['key' => 'store_phone',   'value' => '',         'name' => 'Store Phone',   'group' => 'general'],
            ['key' => 'timezone',      'value' => 'UTC',      'name' => 'Timezone',      'group' => 'general'],

            // Tax
            ['key' => 'tax_rate', 'value' => '0', 'name' => 'Tax Rate (%)', 'group' => 'tax'],

            // Receipt
            ['key' => 'receipt_header', 'value' => '',      'name' => 'Receipt Header', 'group' => 'receipt'],
            ['key' => 'receipt_footer', 'value' => '',      'name' => 'Receipt Footer', 'group' => 'receipt'],
            ['key' => 'show_logo',      'value' => 'false', 'name' => 'Show Logo',      'group' => 'receipt'],

            // Inventory
            ['key' => 'low_stock_default_threshold', 'value' => '5',  'name' => 'Low Stock Default Threshold', 'group' => 'inventory'],
            ['key' => 'expiry_alert_days',           'value' => '30', 'name' => 'Expiry Alert Days',           'group' => 'inventory'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
```

Register in `DatabaseSeeder`:
```php
$this->call(SettingsSeeder::class);
```

---

### 3. Service Class

```
app/Services/SettingsService.php
```

```php
<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Update multiple settings at once and flush cache.
     * Only updates keys that already exist in the database.
     */
    public function updateMany(array $data): void
    {
        $validKeys = Setting::whereIn('key', array_keys($data))->pluck('key');

        foreach ($validKeys as $key) {
            Setting::where('key', $key)->update(['value' => (string) $data[$key]]);
        }

        Cache::tags(['settings'])->flush();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['updated_keys' => $validKeys->toArray()])
            ->log('settings_updated');
    }

    /**
     * Update settings for a specific group.
     */
    public function updateGroup(string $group, array $data): void
    {
        $validKeys = Setting::where('group', $group)
            ->whereIn('key', array_keys($data))
            ->pluck('key');

        foreach ($validKeys as $key) {
            Setting::where('key', $key)->update(['value' => (string) $data[$key]]);
        }

        Cache::tags(['settings'])->flush();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['group' => $group, 'updated_keys' => $validKeys->toArray()])
            ->log('settings_updated');
    }

    /**
     * Get all settings as a nested array grouped by group.
     */
    public function all(): array
    {
        return Setting::all()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key'))
            ->toArray();
    }
}
```

---

### 4. Controller

```
app/Http/Controllers/SettingController.php
```

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateTaxSettingsRequest;
use App\Http\Requests\Settings\UpdateReceiptSettingsRequest;
use App\Http\Requests\Settings\UpdateInventorySettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingsService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => $this->service->all(),
            'groups'   => ['general', 'tax', 'receipt', 'inventory'],
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        $this->service->updateGroup('general', $request->validated());

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $request->validated(),
        ]);
    }

    public function updateTax(UpdateTaxSettingsRequest $request): JsonResponse
    {
        $this->service->updateGroup('tax', $request->validated());

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $request->validated(),
        ]);
    }

    public function updateReceipt(UpdateReceiptSettingsRequest $request): JsonResponse
    {
        $this->service->updateGroup('receipt', $request->validated());

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $request->validated(),
        ]);
    }

    public function updateInventory(UpdateInventorySettingsRequest $request): JsonResponse
    {
        $this->service->updateGroup('inventory', $request->validated());

        return response()->json([
            'message'  => 'Settings updated successfully.',
            'settings' => $request->validated(),
        ]);
    }

    public function public(): JsonResponse
    {
        return response()->json([
            'store_name' => Setting::get('store_name'),
            'timezone'   => Setting::get('timezone'),
        ]);
    }
}
```

---

### 5. Form Requests

```
app/Http/Requests/Settings/UpdateGeneralSettingsRequest.php
```

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'store_name'    => ['required', 'string', 'max:100'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_phone'   => ['nullable', 'string', 'max:30'],
            'timezone'      => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
```

```
app/Http/Requests/Settings/UpdateTaxSettingsRequest.php
```

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
```

```
app/Http/Requests/Settings/UpdateReceiptSettingsRequest.php
```

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'receipt_header' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'show_logo'      => ['required', 'boolean'],
        ];
    }
}
```

```
app/Http/Requests/Settings/UpdateInventorySettingsRequest.php
```

```php
<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        return [
            'low_stock_default_threshold' => ['required', 'integer', 'min:1', 'max:9999'],
            'expiry_alert_days'           => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
```

---

### 6. Routes

```php
// routes/web.php
use App\Http\Controllers\SettingController;

// Public route — no auth
Route::get('/settings/public', [SettingController::class, 'public'])->name('settings.public');

// Admin-only routes
Route::middleware(['auth', 'can:settings.manage'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/general',   [SettingController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('/settings/tax',       [SettingController::class, 'updateTax'])->name('settings.tax.update');
    Route::put('/settings/receipt',   [SettingController::class, 'updateReceipt'])->name('settings.receipt.update');
    Route::put('/settings/inventory', [SettingController::class, 'updateInventory'])->name('settings.inventory.update');
});
```

---

### 7. Usage by Other Modules

```php
// POS — get current tax rate
$taxRate = Setting::get('tax_rate'); // returns float e.g. 10.5

// Receipt generator
$showLogo = Setting::get('show_logo');  // returns bool true/false
$header   = Setting::get('receipt_header'); // returns string

// Inventory alert
$threshold = Setting::get('low_stock_default_threshold'); // returns int 5
$expiryDays = Setting::get('expiry_alert_days'); // returns int 30

// Store info for receipts
$storeName    = Setting::get('store_name');
$storeAddress = Setting::get('store_address');
$storePhone   = Setting::get('store_phone');
```

---

### 8. Activity Logging

Log all settings updates:

```php
activity('settings')
    ->causedBy(auth()->user())
    ->withProperties([
        'group'        => $group,
        'updated_keys' => $keys,
    ])
    ->log('settings_updated');
```

Log events: `settings_updated`

---

## Good Practices
- Always use `Cache::tags(['settings'])->flush()` after any setting change — never leave stale cached values
- Use the `Setting::get($key, $default)` static helper everywhere in the codebase — avoid direct `DB::` queries for settings
- Store all values as strings in the DB; perform casting in the model's `castValue()` method
- Validate each group's keys with a dedicated `FormRequest` — this makes validation maintainable as the settings grow
- Use `firstOrCreate` in the seeder so re-seeding does not overwrite admin-configured values
- If the cache driver does not support tags, replace `Cache::tags(['settings'])->flush()` with explicit `Cache::forget("settings.{$key}")` calls per key
