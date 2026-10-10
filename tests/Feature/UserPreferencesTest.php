<?php

declare(strict_types=1);

use App\Enums\UserLocale;
use App\Enums\UserTheme;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

it('requires authentication to update preferences', function () {
    patch(route('user-preferences.update'), ['locale' => UserLocale::ENGLISH->value])
        ->assertRedirect(route('login'));
});

it('updates the authenticated users locale and theme', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patch(route('user-preferences.update'), [
            'locale' => UserLocale::ENGLISH->value,
            'theme' => UserTheme::DARK->value,
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->locale)->toBe(UserLocale::ENGLISH);
    expect($user->theme)->toBe(UserTheme::DARK);
});

it('validates user preference values', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->patchJson(route('user-preferences.update'), [
            'locale' => 'fr',
            'theme' => 'system',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['locale', 'theme']);
});

it('does not update another users preferences', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create([
        'locale' => UserLocale::SPANISH,
        'theme' => UserTheme::LIGHT,
    ]);

    actingAs($user)
        ->patch(route('user-preferences.update'), ['locale' => UserLocale::ENGLISH->value])
        ->assertRedirect();

    $otherUser->refresh();

    expect($otherUser->locale)->toBe(UserLocale::SPANISH);
    expect($otherUser->theme)->toBe(UserTheme::LIGHT);
});

it('shares preferences and applies the users locale to the request', function () {
    $user = User::factory()->create([
        'locale' => UserLocale::ENGLISH,
        'theme' => UserTheme::DARK,
    ]);

    actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('lang="en"', false)
        ->assertSee('class="app-dark"', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.locale', UserLocale::ENGLISH->value)
            ->where('auth.user.theme', UserTheme::DARK->value),
        );

    expect(app()->getLocale())->toBe(UserLocale::ENGLISH->value);
});
