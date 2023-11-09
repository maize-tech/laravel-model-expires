<?php

namespace Maize\ModelExpires;

use Illuminate\Support\Facades\Event;
use Maize\ModelExpires\Commands\ModelExpiresCheckCommand;
use Maize\ModelExpires\Commands\ModelExpiresDeleteCommand;
use Maize\ModelExpires\Events\ModelExpiring;
use Maize\ModelExpires\Listeners\SendModelExpiringNotification;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ModelExpiresServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-model-expires')
            ->hasConfigFile()
            ->hasMigration('create_expirations_table')
            ->hasCommands([
                ModelExpiresCheckCommand::class,
                ModelExpiresDeleteCommand::class,
            ])
            ->hasInstallCommand(
                fn (InstallCommand $command) => $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('maize-tech/laravel-model-expires')
            );
    }

    public function packageBooted(): void
    {
        Event::listen(
            events: ModelExpiring::class,
            listener: [SendModelExpiringNotification::class, 'handle']
        );
    }
}
