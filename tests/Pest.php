<?php

declare(strict_types=1);

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as TestCaseHelper;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature')
    ->beforeEach(function () {
        /** @var TestCaseHelper $test */
        $test = $this; // @phpstan-ignore-line

        $test->seed(RoleSeeder::class);
        $test->seed(PermissionSeeder::class);
    });
