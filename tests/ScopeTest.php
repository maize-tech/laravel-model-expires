<?php

use Maize\ModelExpires\Tests\Support\Models\Tenant;
use Maize\ModelExpires\Tests\Support\Models\User;

use function Spatie\PestPluginTestTime\testTime;

it('can get only expired model', function ($model) {
    testTime()->freeze();

    $models = $model::factory()->count(5)->create();

    expect($model::count(5))->toBe(5);

    expect($model::query()->onlyExpired()->count())->toBe(0);

    $models[2]->setExpiresAt(
        now()->addDays(-3)
    );

    expect($model::query()->onlyExpired()->count())->toBe(1);
})->with([
    ['model' => User::class],
    ['model' => Tenant::class],
]);

it('can exclude expired model', function ($model) {
    testTime()->freeze();

    $models = $model::factory()->count(5)->create();

    expect($model::count(5))->toBe(5);

    expect($model::query()->withoutExpired()->count())->toBe(5);

    $models[2]->setExpiresAt(
        now()->addDays(-3)
    );

    expect($model::query()->withoutExpired()->count())->toBe(4);
})->with([
    ['model' => User::class],
    ['model' => Tenant::class],
]);
