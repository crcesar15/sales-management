<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 *
 * @mixin \Eloquent
 */
final class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'name',
    ];

    public static function populateSettings(): void
    {
        $settings = [
            [
                'key' => 'site-name',
                'value' => 'My Application',
                'name' => 'Site Name',
            ],
            [
                'key' => 'currency-symbol',
                'value' => '$',
                'name' => 'Currency Symbol',
            ],
            [
                'key' => 'timezone',
                'value' => 'America/La_Paz',
                'name' => 'Timezone',
            ],
            [
                'key' => 'datetime-format',
                'value' => 'YYYY-MM-DD HH:mm',
                'name' => 'Timezone',
            ],
        ];

        foreach ($settings as $setting) {
            self::query()->updateOrCreate(['key' => $setting['key']], ['value' => $setting['value'], 'name' => $setting['name']]);
        }
    }
}
