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
                'key' => 'site_name',
                'value' => 'My Application',
                'name' => 'Site Name',
            ],
            [
                'key' => 'currency_symbol',
                'value' => '$',
                'name' => 'Currency Symbol',
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
