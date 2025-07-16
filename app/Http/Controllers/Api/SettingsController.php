<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    //Get all roles
    public function index(Request $request): ApiCollection
    {
        $query = Setting::query();

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'desc';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    //Update a role
    public function update(Request $request): JsonResponse
    {
        $settings = $request->get('settings');

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }

        return new JsonResponse(['message' => 'Settings updated successfully'], 200);
    }
}
