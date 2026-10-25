<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Models\PendingMediaUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

it('uploads an image for users who can create products', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::PRODUCTS_CREATE->value);

    $response = actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('products.media.store'), [
            'file' => UploadedFile::fake()->image('product.jpg', 640, 480),
        ]);

    $response
        ->assertCreated()
        ->assertJsonStructure(['id', 'thumb_url', 'full_url']);

    expect(PendingMediaUpload::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('uploads an image for users who can edit products', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::PRODUCTS_EDIT->value);

    actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('products.media.store'), [
            'file' => UploadedFile::fake()->image('product.png'),
        ])
        ->assertCreated();
});

it('rejects unauthorised product media uploads', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('products.media.store'), [
            'file' => UploadedFile::fake()->image('product.jpg'),
        ])
        ->assertForbidden();
});

it('requires a supported image no larger than ten megabytes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::PRODUCTS_CREATE->value);

    actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('products.media.store'), [
            'file' => UploadedFile::fake()->create('document.pdf', 32, 'application/pdf'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('products.media.store'), [
            'file' => UploadedFile::fake()->create('large.jpg', 10 * 1024 + 1, 'image/jpeg'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});
