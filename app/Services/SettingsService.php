<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SettingsService
{
    /**
     * Update settings for a specific group.
     * Only updates keys that already exist in the database.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGroup(string $group, array $data): void
    {
        $validKeys = Setting::where('group', $group)
            ->whereIn('key', array_keys($data))
            ->pluck('key');

        foreach ($validKeys as $key) {
            Setting::where('key', $key)->update(['value' => (string) $data[$key]]);
        }

        Cache::tags(['settings'])->flush();
    }

    /**
     * Get all settings as a nested array grouped by group.
     */
    /**
     * @return array<string, array<string, string>>
     */
    public function all(): array
    {
        return Setting::all()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key'))
            ->toArray();
    }
}
