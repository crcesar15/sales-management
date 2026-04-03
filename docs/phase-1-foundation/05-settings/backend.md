# Settings — Backend

## Implementation Steps

### 1. Model

```
app/Models/Setting.php
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Setting extends Model
{
    use LogsActivity;

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

    private static array $floatKeys = ['tax_rate'];

    private static function castValue(string $key, string $value): mixed
    {
        if (in_array($key, static::$floatKeys)) {
            return (float) $value;
        }

        return $value;
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('setting')
            ->dontSubmitEmptyLogs();
    }
}
```

---

### 2. Service Class

```
app/Services/SettingsService.php
```

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SettingsService
{
    /**
     * Update settings for a specific group.
     * Only updates keys that already exist in the database.
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

### 3. Controller

```
app/Http/Controllers/SettingController.php
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateTaxSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SettingController extends Controller
{
    public function __construct(
        private readonly SettingsService $service
    ) {}

    public function index(): Response
    {
        $this->authorize(PermissionsEnum::SETTINGS_MANAGE, auth()->user());

        return Inertia::render('Settings/Index', [
            'settings' => $this->service->all(),
            'groups'   => ['general', 'tax'],
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->service->updateGroup('general', $request->validated());

        return redirect()->route('settings');
    }

    public function updateTax(UpdateTaxSettingsRequest $request): RedirectResponse
    {
        $this->service->updateGroup('tax', $request->validated());

        return redirect()->route('settings');
    }
}
```

---

### 4. Form Requests

```
app/Http/Requests/Settings/UpdateGeneralSettingsRequest.php
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SETTINGS_MANAGE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'business_name'    => ['required', 'string', 'max:100'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'business_phone'   => ['nullable', 'string', 'max:30'],
            'timezone'         => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
```

```
app/Http/Requests/Settings/UpdateTaxSettingsRequest.php
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SETTINGS_MANAGE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
```

---

### 5. Routes

```php
// routes/web.php
use App\Http\Controllers\SettingController;

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::put('/settings/general', [SettingController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('/settings/tax', [SettingController::class, 'updateTax'])->name('settings.tax.update');
});
```

---

### 6. Usage by Other Modules

```php
// Get business info
$businessName = Setting::get('business_name');    // returns string
$businessAddress = Setting::get('business_address'); // returns string
$businessPhone = Setting::get('business_phone');     // returns string

// Get tax rate (e.g., for POS calculations)
$taxRate = Setting::get('tax_rate'); // returns float e.g. 10.5

// Get timezone
$timezone = Setting::get('timezone'); // returns string e.g. 'UTC'

// With defaults
$taxRate = Setting::get('tax_rate', 0); // returns 0 if not set
```

---

### 7. Activity Logging

The `Setting` model uses the `LogsActivity` trait to automatically log changes. When a setting value is updated via `Setting::set()` or the service, the change is recorded in the activity log.

Log name: `setting`

---

## Good Practices
- Always use `Cache::tags(['settings'])->flush()` after any setting change — never leave stale cached values
- Use the `Setting::get($key, $default)` static helper everywhere in the codebase — avoid direct `DB::` queries for settings
- Store all values as strings in the DB; perform casting in the model's `castValue()` method
- Validate each group's keys with a dedicated `FormRequest` — this makes validation maintainable as the settings grow
- If the cache driver does not support tags, replace `Cache::tags(['settings'])->flush()` with explicit `Cache::forget("settings.{$key}")` calls per key
