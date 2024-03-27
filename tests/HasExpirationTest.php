<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Maize\ModelExpires\Models\Expiration;
use Maize\ModelExpires\Tests\Support\Models\Tenant;
use Maize\ModelExpires\Tests\Support\Models\User;

use function Spatie\PestPluginTestTime\testTime;

it('can create model with default expiration', function (string $model, ?int $expiresAt, ?int $deletesAt) {
    testTime()->freeze();

    $model::factory()->count(5)->create();

    expect($model::count())->toBe(5);

    $expirations = Expiration::get();

    expect($expirations)->toHaveCount(5);

    $expirations->each(
        fn ($expiration) => expect($expiration->expires_at?->timestamp)->toBe($expiresAt)
    );

    $expirations->each(
        fn ($expiration) => expect($expiration->deletes_at?->timestamp)->toBe($deletesAt)
    );
})->with([
    [
        'model' => User::class,
        'expiresAt' => null,
        'deletesAt' => null,
    ],
    [
        'model' => Tenant::class,
        'expiresAt' => fn () => now()->startOfDay()->addDays(365)->timestamp,
        'deletesAt' => null,
    ],
]);

it('can set expires_at', function (string $model, int $index, int $days, ?Carbon $default) {
    $users = $model::factory()->count(3)->create();

    $expirations = Expiration::get();

    expect($users[0]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);
    expect($users[1]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);
    expect($users[2]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);

    $date = now()->addDays($days);

    $users[$index]->setExpiresAt($date);

    $expirations = Expiration::get();
    expect($expirations[$index]->expires_at)->toBeInstanceOf(Carbon::class);
    expect($expirations[$index]->expires_at->timestamp)->toBe($date->timestamp);

    foreach (Arr::except([0, 1, 2], $index) as $i) {
        expect($expirations[$i]->expires_at?->timestamp)->toBe($default?->timestamp);
    }
})->with([
    ['model' => User::class, 'index' => 0, 'days' => 2, 'default' => null],
    ['model' => User::class, 'index' => 1, 'days' => 4, 'default' => null],
    ['model' => User::class, 'index' => 2, 'days' => 20, 'default' => null],
    ['model' => User::class, 'index' => 2, 'days' => -10, 'default' => null],
    ['model' => Tenant::class, 'index' => 0, 'days' => 2, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 1, 'days' => 4, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 2, 'days' => 20, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 2, 'days' => -10, 'default' => fn () => now()->startOfDay()->addDays(365)],
]);

it('can get expires_at', function (string $model, int $index, int $days, ?Carbon $default) {
    testTime()->freeze();

    $users = $model::factory()->count(3)->create();

    expect($users[0]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);
    expect($users[1]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);
    expect($users[2]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);

    $date = now()->addDays($days);

    $users[$index]->setExpiresAt($date);

    expect($users[$index]->getExpiresAt())->toBeInstanceOf(Carbon::class);
    expect($users[$index]->getExpiresAt()->timestamp)->toBe($date->timestamp);

    foreach (Arr::except([0, 1, 2], $index) as $i) {
        expect($users[$i]->getExpiresAt()?->timestamp)->toBe($default?->timestamp);
    }
})->with([
    ['model' => User::class, 'index' => 0, 'days' => 2, 'default' => null],
    ['model' => User::class, 'index' => 1, 'days' => 4, 'default' => null],
    ['model' => User::class, 'index' => 2, 'days' => 20, 'default' => null],
    ['model' => User::class, 'index' => 2, 'days' => -10, 'default' => null],
    ['model' => Tenant::class, 'index' => 0, 'days' => 2, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 1, 'days' => 4, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 2, 'days' => 20, 'default' => fn () => now()->startOfDay()->addDays(365)],
    ['model' => Tenant::class, 'index' => 2, 'days' => -10, 'default' => fn () => now()->startOfDay()->addDays(365)],
]);

it('can get isExpired', function (string $model, int $index, int $months) {
    testTime()->freeze();

    $users = $model::factory()->count(3)->create();

    expect($users[0]->isExpired())->toBeFalse();
    expect($users[1]->isExpired())->toBeFalse();
    expect($users[2]->isExpired())->toBeFalse();

    $date = now()->addMonths($months);

    $users[$index]->setExpiresAt($date);

    testTime()->addMonths(11);

    expect($users[$index]->isExpired())->toBeTrue();
    foreach (Arr::except([0, 1, 2], $index) as $i) {
        expect($users[$i]->isExpired())->toBeFalse();
    }
})->with([
    ['model' => User::class, 'index' => 0, 'months' => -1],
    ['model' => User::class, 'index' => 1, 'months' => 5],
    ['model' => User::class, 'index' => 2, 'months' => 10],
    ['model' => Tenant::class, 'index' => 0, 'months' => -1],
    ['model' => Tenant::class, 'index' => 1, 'months' => 5],
    ['model' => Tenant::class, 'index' => 2, 'months' => 10],
]);

it('can get getDaysLeftToExpiration', function (string $model, int $index, int $days, ?int $default, int $remainingIndex, ?int $remaining) {
    testTime()->freeze();

    $users = $model::factory()->count(3)->create();

    expect($users[0]->getDaysLeftToExpiration())->toBe($default);
    expect($users[1]->getDaysLeftToExpiration())->toBe($default);
    expect($users[2]->getDaysLeftToExpiration())->toBe($default);

    $date = now()->addDays($days);
    $users[$index]->setExpiresAt($date);

    testTime()->addDays(4);

    expect($users[$index]->getDaysLeftToExpiration())->toBe($remainingIndex);
    foreach (Arr::except([0, 1, 2], $index) as $i) {
        expect($users[$i]->getDaysLeftToExpiration())->toBe($remaining);
    }
})->with([
    ['model' => User::class, 'index' => 0, 'days' => -1, 'default' => null, 'remainingIndex' => 0, 'remaining' => null],
    ['model' => User::class, 'index' => 1, 'days' => 5, 'default' => null, 'remainingIndex' => 1, 'remaining' => null],
    ['model' => User::class, 'index' => 2, 'days' => 10, 'default' => null, 'remainingIndex' => 6, 'remaining' => null],
    ['model' => Tenant::class, 'index' => 0, 'days' => -1, 'default' => 365, 'remaining' => 0, 'remainingIndex' => 361],
    ['model' => Tenant::class, 'index' => 1, 'days' => 5, 'default' => 365, 'remaining' => 1, 'remainingIndex' => fn () => 361],
    ['model' => Tenant::class, 'index' => 2, 'days' => 10, 'default' => 365, 'remaining' => 6, 'remainingIndex' => fn () => 361],
]);

it('can get canExpire', function (string $model, int $index, int $days, bool $default) {
    testTime()->freeze();

    $users = $model::factory()->count(3)->create();

    expect($users[0]->canExpire())->toBe($default);
    expect($users[1]->canExpire())->toBe($default);
    expect($users[2]->canExpire())->toBe($default);

    $date = now()->addDays($days);
    $users[$index]->setExpiresAt($date);

    expect($users[$index]->canExpire())->toBe(true);
    foreach (Arr::except([0, 1, 2], $index) as $i) {
        expect($users[$i]->canExpire())->toBe($default);
    }
})->with([
    ['model' => User::class, 'index' => 0, 'days' => -1, 'default' => false],
    ['model' => User::class, 'index' => 1, 'days' => 5, 'default' => false],
    ['model' => User::class, 'index' => 2, 'days' => 10, 'default' => false],
    ['model' => Tenant::class, 'index' => 0, 'days' => -1, 'default' => true],
    ['model' => Tenant::class, 'index' => 1, 'days' => 5, 'default' => true],
    ['model' => Tenant::class, 'index' => 2, 'days' => 10, 'default' => true],
]);
