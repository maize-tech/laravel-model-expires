<?php

use Illuminate\Support\Facades\Event;
use Maize\ModelExpires\Commands\ModelExpiresDeleteCommand;
use Maize\ModelExpires\Events\ExpiredModelsDeleted;
use Maize\ModelExpires\Tests\Support\Events\UserDeletedEvent;
use Maize\ModelExpires\Tests\Support\Models\User;

use function Pest\Laravel\artisan;
use function Spatie\PestPluginTestTime\testTime;

it('should fire ExpiredModelsDeleted event', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.model.deletes_after_days', 10);

    User::factory()->count(10)->create();

    testTime()->addDays(365);

    Event::fake();

    artisan(ModelExpiresDeleteCommand::class);

    Event::assertDispatched(
        ExpiredModelsDeleted::class,
        fn (ExpiredModelsDeleted $event) => $event->model === User::class && $event->count === 10
    );
});

it('should delete all deletable models', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.model.deletes_after_days', 10);

    $users = User::factory()->count(10)->create();

    $users[0]->setExpiresAt();
    $users[1]->setExpiresAt();

    testTime()->addDays(365);

    Event::fake();

    artisan(ModelExpiresDeleteCommand::class);

    Event::assertDispatched(UserDeletedEvent::class);

    Event::assertDispatched(
        ExpiredModelsDeleted::class,
        fn (ExpiredModelsDeleted $event) => $event->model === User::class && $event->count === 8
    );

    expect(User::count())->toBe(2);
});

it('should mass delete expired models when option is enabled', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.model.deletes_after_days', 10);

    User::factory()->count(10)->create();

    testTime()->addDays(365);

    Event::fake();

    artisan(ModelExpiresDeleteCommand::class, [
        '--mass' => true,
    ]);

    Event::assertNotDispatched(UserDeletedEvent::class);

    Event::assertDispatched(
        ExpiredModelsDeleted::class,
        fn (ExpiredModelsDeleted $event) => $event->model === User::class && $event->count === 10
    );

    expect(User::count())->toBe(0);
});

it('should chunk model deletion', function () {
    testTime()->freeze();
    config()->set('model-expires.model.expires_after_days', 5);
    config()->set('model-expires.model.deletes_after_days', 10);

    User::factory()->count(10)->create();

    testTime()->addDays(365);

    Event::fake();

    artisan(ModelExpiresDeleteCommand::class, [
        '--chunk' => 5,
    ]);

    Event::assertDispatchedTimes(
        ExpiredModelsDeleted::class,
        2
    );
});
