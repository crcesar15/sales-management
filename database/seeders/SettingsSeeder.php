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
            'company' => [
                [
                    'key' => 'company_name',
                    'value' => 'My Application',
                    'name' => 'Company Name',
                ],
                [
                    'key' => 'company_logo_url',
                    'value' => 'null',
                    'name' => 'Company Logo URL',
                ],
                [
                    'key' => 'company_address',
                    'value' => '123 Main Street, City, Country',
                    'name' => 'Company Address',
                ],
                [
                    'key' => 'company_tax_id',
                    'value' => '123456789',
                    'name' => 'Company Tax ID',
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
                    'key' => 'default_tax_rate',
                    'value' => '0.13',
                    'name' => 'Default Tax Rate',
                ],
                [
                    'key' => 'tax_inclusive_prices',
                    'value' => 'false',
                    'name' => 'Prices Include Tax',
                ],
            ],
            'inventory' => [
                [
                    'key' => 'inventory_tracking_enabled',
                    'value' => 'true',
                    'name' => 'Inventory Tracking Enabled',
                ],
                [
                    'key' => 'low_stock_threshold',
                    'value' => '5',
                    'name' => 'Low Stock Threshold',
                ],
                [
                    'key' => 'default_unit_of_measure',
                    'value' => 'pcs',
                    'name' => 'Default Unit of Measure',
                ],
                [
                    'key' => 'allow_negative_stock',
                    'value' => 'false',
                    'name' => 'Allow Negative Stock',
                ],
            ],
            'orders' => [
                [
                    'key' => 'sales_prefix',
                    'value' => 'SO-',
                    'name' => 'Sales Order Prefix',
                ],
                [
                    'key' => 'purchase_prefix',
                    'value' => 'PO-',
                    'name' => 'Purchase Order Prefix',
                ],
                [
                    'key' => 'order_number_padding',
                    'value' => '5',
                    'name' => 'Order Number Padding',
                ],
                [
                    'key' => 'default_sales_order_status',
                    'value' => 'draft',
                    'name' => 'Default Sales Order Status',
                ],
                [
                    'key' => 'default_purchase_order_status',
                    'value' => 'pending',
                    'name' => 'Default Purchase Order Status',
                ],
            ],
            'vendor' => [
                [
                    'key' => 'catalog_view_mode',
                    'value' => 'grouped_by_vendor',
                    'name' => 'Catalog View Mode',
                ],
                [
                    'key' => 'allow_vendor_price_override',
                    'value' => 'true',
                    'name' => 'Allow Vendor Price Override',
                ],
                [
                    'key' => 'default_vendor_payment_terms',
                    'value' => 'Net 30',
                    'name' => 'Vendor Payment Terms',
                ],
            ],
            'store' => [
                [
                    'key' => 'multi_store_enabled',
                    'value' => 'true',
                    'name' => 'Enable Multi-Store',
                ],
                [
                    'key' => 'default_store_id',
                    'value' => '1',
                    'name' => 'Default Store ID',
                ],
                [
                    'key' => 'stock_transfer_requires_approval',
                    'value' => 'true',
                    'name' => 'Stock Transfer Requires Approval',
                ],
            ],
            'user' => [
                [
                    'key' => 'allow_user_registration',
                    'value' => 'false',
                    'name' => 'Allow User Registration',
                ],
                [
                    'key' => 'default_user_role',
                    'value' => 'clerk',
                    'name' => 'Default User Role',
                ],
                [
                    'key' => 'restrict_user_to_store',
                    'value' => 'true',
                    'name' => 'Restrict User to Store',
                ],
            ],
            'email' => [
                [
                    'key' => 'email_from_address',
                    'value' => 'no-reply@yourapp.com',
                    'name' => 'Email From Address',
                ],
                [
                    'key' => 'email_order_notifications',
                    'value' => 'true',
                    'name' => 'Enable Order Notification Emails',
                ],
                [
                    'key' => 'admin_email',
                    'value' => 'admin@yourapp.com',
                    'name' => 'Admin Email',
                ],
            ],
            'system' => [
                [
                    'key' => 'timezone',
                    'value' => 'America/New_York',
                    'name' => 'Timezone',
                ],
                [
                    'key' => 'date_format',
                    'value' => 'YYYY-MM-DD',
                    'name' => 'Date Format',
                ],
                [
                    'key' => 'datetime_format',
                    'value' => 'YYYY-MM-DD HH:mm ',
                    'name' => 'Datetime Format',
                ],
                [
                    'key' => 'decimal_precision',
                    'value' => '2',
                    'name' => 'Decimal Precision',
                ],
                [
                    'key' => 'api_access_enabled',
                    'value' => 'true',
                    'name' => 'Enable API Access',
                ],
            ],
        ];

        foreach ($settingGroups as $group => $settings) {
            foreach ($settings as $setting) {
                Setting::query()->create(
                    [
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'name' => $setting['name'],
                        'group' => $group,
                    ]
                );
            }
        }
    }
}
