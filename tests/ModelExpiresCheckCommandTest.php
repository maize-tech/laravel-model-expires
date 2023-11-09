<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Maize\ModelExpires\Commands\ModelExpiresCheckCommand;
use Maize\ModelExpires\Events\ModelExpiring;
use Maize\ModelExpires\Listeners\SendModelExpiringNotification;
use Maize\ModelExpires\Notifications\ModelExpiringNotification;
use Maize\ModelExpires\Tests\Support\Models\User;

use function Pest\Laravel\artisan;
use function Spatie\PestPluginTestTime\testTime;

it('should fire ExpiringModel event if expires is equal', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);

    $users = User::factory()->count(10)->create();

    $users[0]->setExpiresAt();
    $users[1]->setExpiresAt();

    Event::fake();

    artisan(ModelExpiresCheckCommand::class);

    Event::assertDispatchedTimes(
        ModelExpiring::class,
        8
    );
});

it('should fire ExpiringModel event multiple times', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 10);

    $users = User::factory()->count(10)->create();

    $users[0]->setExpiresAt();
    $users[1]->setExpiresAt();

    Event::fake();

    artisan(ModelExpiresCheckCommand::class);

    Event::assertDispatchedTimes(
        ModelExpiring::class,
        8
    );

    testTime()->addDays(5);

    Event::fake();

    artisan(ModelExpiresCheckCommand::class);

    Event::assertDispatchedTimes(
        ModelExpiring::class,
        8
    );
});

it('should not fire ExpiringModel event if expires is different', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);

    User::factory()->count(10)->create();

    testTime()->addDays(10);

    Event::fake();

    artisan(ModelExpiresCheckCommand::class);

    Event::assertDispatchedTimes(
        ModelExpiring::class,
        0
    );
});

it('should activate SendModelExpiringNotification listener', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);

    User::factory()->count(10)->create();

    Event::fake();

    artisan(ModelExpiresCheckCommand::class);

    Event::assertListening(
        ModelExpiring::class,
        [SendModelExpiringNotification::class, 'handle']
    );
});

it('should not send ModelExpiringNotification notification when disabled', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.expiring_notification.enabled', false);

    User::factory()->count(10)->create();

    Notification::fake();

    artisan(ModelExpiresCheckCommand::class);

    Notification::assertNothingSent();
});

it('should send ModelExpiringNotification notification', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.expiring_notification.notifiables', [
        'test@example.com',
    ]);

    User::factory()->count(10)->create();

    Notification::fake();

    artisan(ModelExpiresCheckCommand::class);

    Notification::assertSentTimes(
        ModelExpiringNotification::class,
        10
    );
});
