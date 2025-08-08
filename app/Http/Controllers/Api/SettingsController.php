<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Setting;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Setting::query();

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'desc')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var array<array<string>> $settings */
        $settings = $request->array('settings');

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }

        Cache::forget('settings');

        return response()->json(['message' => 'Settings updated successfully'], 200);
    }
}
