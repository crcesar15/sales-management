<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Flush any leftover settings cache entries from previous tests.
    Cache::flush();
});

it('returns the configured value from Setting::get() under the array driver', function () {
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '13',
        'group' => 'sales',
    ]);

    // Prime the cache.
    expect(Setting::get('tax_rate'))->toBe(13.0);

    // Second read must come from the cache (no new query) and return the same value.
    expect(Setting::get('tax_rate'))->toBe(13.0);
});

it('invalidates the single-key cache immediately after Setting::set()', function () {
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '13',
        'group' => 'sales',
    ]);

    expect(Setting::get('tax_rate'))->toBe(13.0);

    Setting::set('tax_rate', 20);

    expect(Setting::get('tax_rate'))->toBe(20.0);
});

it('invalidates the group cache after Setting::set()', function () {
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '13',
        'group' => 'sales',
    ]);
    Setting::factory()->create([
        'key' => 'currency_code',
        'value' => 'BOB',
        'group' => 'sales',
    ]);

    // Prime the group cache.
    expect(Setting::group('sales'))->toBe(['tax_rate' => '13', 'currency_code' => 'BOB']);

    Setting::set('tax_rate', 20);

    // Group cache must reflect the new value immediately.
    expect(Setting::group('sales'))->toBe(['tax_rate' => '20', 'currency_code' => 'BOB']);
});

it('does not use Cache::tags() (driver-agnostic invalidation)', function () {
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '13',
        'group' => 'sales',
    ]);

    // If the model still uses Cache::tags(), this throws BadMethodCallException
    // under the array driver. The call should succeed transparently.
    expect(fn () => Setting::get('tax_rate'))->not->toThrow(BadMethodCallException::class);
    expect(fn () => Setting::set('tax_rate', 20))->not->toThrow(BadMethodCallException::class);
});