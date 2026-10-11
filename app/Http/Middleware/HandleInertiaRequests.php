<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
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
    public function share(Request $request): array
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

            $shared = [
                'auth' => [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                        'locale' => $user->locale?->value,
                        'theme' => $user->theme?->value,
                    ] : null,
                    'settings' => $formattedSettings,
                ],
            ];
        }

        return array_merge(parent::share($request), $shared);
    }
}
