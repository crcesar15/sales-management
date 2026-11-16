<?php

declare(strict_types=1);

namespace Database\Seeders\InitialData;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\CashRegister;
use App\Models\MeasurementUnit;
use App\Models\Setting;
use App\Models\Store;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PharmacySpanishSeeder extends Seeder
{
    /** @var list<PermissionsEnum> */
    private const SALESMAN_PERMISSIONS = [
        PermissionsEnum::POS_ACCESS,
        PermissionsEnum::SHIFTS_OPEN,
        PermissionsEnum::SHIFTS_CLOSE,
        PermissionsEnum::CASH_MOVEMENTS_CREATE,
        PermissionsEnum::CUSTOMERS_VIEW,
        PermissionsEnum::CUSTOMERS_CREATE,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedRolesAndPermissions();
            $this->seedSettings();
            $this->seedMeasurementUnits();
            $this->seedStoreAndCashRegister();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('settings');
    }

    private function seedRolesAndPermissions(): void
    {
        Role::query()->firstOrCreate(['name' => RolesEnum::ADMIN->value, 'guard_name' => 'web']);
        $salesman = Role::query()->firstOrCreate(['name' => RolesEnum::SALESMAN->value, 'guard_name' => 'web']);

        app(PermissionSeeder::class)->run();
        $salesman->givePermissionTo(array_map(
            fn (PermissionsEnum $permission): Permission => Permission::query()->where('name', $permission->value)->where('guard_name', 'web')->firstOrFail(),
            self::SALESMAN_PERMISSIONS,
        ));
    }

    private function seedSettings(): void
    {
        $settings = [
            'general' => [
                ['business_name', 'Farmacia', 'Nombre del negocio'],
                ['business_address', '', 'Direccion del negocio'],
                ['business_phone', '', 'Telefono del negocio'],
                ['timezone', 'America/La_Paz', 'Zona horaria'],
                ['expiry_alert_days', '30', 'Dias de alerta de vencimiento'],
                ['date_format', 'DD/MM/YYYY', 'Formato de fecha'],
                ['datetime_format', 'DD/MM/YYYY HH:mm', 'Formato de fecha y hora'],
            ],
            'tax' => [['tax_rate', '0', 'Impuesto adicional (%)']],
            'finance' => [
                ['currency', 'BOB', 'Codigo de moneda'],
                ['currency_symbol', 'Bs.', 'Simbolo de moneda'],
                ['decimal_precision', '2', 'Precision decimal'],
            ],
        ];

        foreach ($settings as $group => $groupSettings) {
            foreach ($groupSettings as [$key, $value, $name]) {
                Setting::query()->firstOrCreate(['key' => $key], compact('value', 'name', 'group'));
            }
        }
    }

    private function seedMeasurementUnits(): void
    {
        foreach ([
            'Unidad' => 'Unid.', 'Tableta' => 'Tab.', 'Comprimido' => 'Comp.', 'Capsula' => 'Cap.', 'Gragea' => 'Grag.',
            'Sobre' => 'Sob.', 'Ampolla' => 'Amp.', 'Vial' => 'Vial.', 'Supositorio' => 'Sup.', 'Ovulo' => 'Ovu.',
            'Frasco' => 'Fco.', 'Tubo' => 'Tub.', 'Pote' => 'Pot.', 'Aerosol' => 'Aer.', 'Inhalador' => 'Inh.',
            'Caja' => 'Cja.', 'Blister' => 'Blis.', 'Paquete' => 'Paq.', 'Par' => 'Par.', 'Parche' => 'Parch.',
        ] as $name => $abbreviation) {
            MeasurementUnit::withTrashed()->firstOrCreate(['name' => $name], ['abbreviation' => $abbreviation]);
        }
    }

    private function seedStoreAndCashRegister(): void
    {
        $store = Store::withTrashed()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Casa Matriz', 'status' => 'active'],
        );

        CashRegister::query()->firstOrCreate(
            ['store_id' => $store->id, 'code' => 'CJ1'],
            ['name' => 'Caja 1', 'status' => 'active', 'is_default' => true],
        );
    }
}
