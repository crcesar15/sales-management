<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\StockAlertService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts.app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     */
    public function share(Request $request): array // @phpstan-ignore-line
    {
        $shared = ['auth' => null];

        if (auth()->user() !== null) {
            /** @var Collection<int, Permission> $permissions */
            $permissions = auth()
                ->user()
                ->getPermissionsViaRoles();

            $settingGroups = Cache::rememberForever('settings', function () {
                return Setting::all()->groupBy('group')->toArray();
            });
            $formattedSettings = [];

            foreach ($settingGroups as $group => $settings) {
                foreach ($settings as $setting) {
                    $formattedSettings[$group][$setting['key']] = $setting['value'];
                }
            }

            $user = $request->user();
            $alertsSummary = $user?->can('stock_alert.view')
                ? Cache::remember('alerts_summary_user_' . $user->id, 300, function () use ($user) {
                    $storeId = $user->hasRole('Salesman') ? $user->stores()->first()?->id : null;

                    return app(StockAlertService::class)->getSummary($storeId);
                })
                : ['low_stock_count' => 0, 'expiry_count' => 0, 'total' => 0];

            $shared = [
                'auth' => [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ] : null,
                    'settings' => $formattedSettings,
                ],
                'alertsSummary' => $alertsSummary,
            ];
        }

        return array_merge(parent::share($request), $shared);
    }
}
