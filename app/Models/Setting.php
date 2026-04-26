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

    protected $fillable = [
        'key',
        'value',
        'name',
        'group',
    ];

    // ─── Type Casting ─────────────────────────────────────────────────────────

    /** @var array<int, string> */
    private static array $floatKeys = ['tax_rate'];

    /** @var array<int, string> */
    private static array $intKeys = ['expiry_alert_days'];

    // ─── Static Helper Methods ────────────────────────────────────────────────

    /**
     * Get a setting value by key, with optional default.
     * Value is cast to the appropriate type automatically.
     *
     * @phpstan-return ($default is null ? (string|float|null) : (string|float|null))
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::tags(['settings'])->rememberForever(
            "settings.{$key}",
            fn () => self::where('key', $key)->value('value')
        );

        if ($value === null) {
            return $default;
        }

        return self::castValue($key, $value);
    }

    /**
     * Set a setting value by key and flush the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        self::where('key', $key)->update(['value' => (string) $value]);
        Cache::tags(['settings'])->flush();
    }

    /**
     * Get all settings for a group as a key-value array.
     *
     * @return array<string, string|null>
     */
    public static function group(string $group): array
    {
        return Cache::tags(['settings'])->rememberForever(
            "settings.group.{$group}",
            fn () => self::where('group', $group)->pluck('value', 'key')->toArray()
        );
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

    private static function castValue(string $key, string $value): mixed
    {
        if (in_array($key, self::$floatKeys)) {
            return (float) $value;
        }

        if (in_array($key, self::$intKeys)) {
            return (int) $value;
        }

        return $value;
    }
}
