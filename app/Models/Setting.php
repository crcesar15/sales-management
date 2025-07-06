<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'name',
    ];

    public static function populateSettings()
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
                'value' => 'UTC-04:00',
                'name' => 'Timezone',
            ],
            [
                'key' => 'datetime-format',
                'value' => 'YYYY-MM-DD HH:mm',
                'name' => 'Timezone',
            ],
        ];

        foreach ($settings as $setting) {
            self::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'name' => $setting['name']]
            );
        }
    }
}
