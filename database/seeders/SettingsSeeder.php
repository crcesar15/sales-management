<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingGroups = [
            'general' => [
                [
                    'key' => 'business_name',
                    'value' => 'My Store',
                    'name' => 'Business Name',
                ],
                [
                    'key' => 'business_address',
                    'value' => '',
                    'name' => 'Business Address',
                ],
                [
                    'key' => 'business_phone',
                    'value' => '',
                    'name' => 'Business Phone',
                ],
                [
                    'key' => 'timezone',
                    'value' => 'UTC',
                    'name' => 'Timezone',
                ],
                [
                    'key' => 'expiry_alert_days',
                    'value' => '30',
                    'name' => 'Expiry Alert Days',
                ],
            ],
            'tax' => [
                [
                    'key' => 'tax_rate',
                    'value' => '0',
                    'name' => 'Tax Rate (%)',
                ],
            ],
            'finance' => [
                [
                    'key' => 'currency',
                    'value' => 'USD',
                    'name' => 'Currency Code',
                ],
                [
                    'key' => 'currency_symbol',
                    'value' => '$',
                    'name' => 'Currency Symbol',
                ],
                [
                    'key' => 'decimal_precision',
                    'value' => '2',
                    'name' => 'Decimal Precision',
                ],
            ],
        ];

        foreach ($settingGroups as $group => $settings) {
            foreach ($settings as $setting) {
                Setting::query()->firstOrCreate(
                    ['key' => $setting['key']],
                    [
                        'value' => $setting['value'],
                        'name' => $setting['name'],
                        'group' => $group,
                    ]
                );
            }
        }
    }
}
