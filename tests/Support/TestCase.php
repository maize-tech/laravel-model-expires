<?php

namespace Maize\ModelExpires\Tests\Support;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maize\ModelExpires\ModelExpiresServiceProvider;
use Maize\ModelExpires\Tests\Support\Models\Tenant;
use Maize\ModelExpires\Tests\Support\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ModelExpiresServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../../database/migrations/create_expirations_table.php.stub';
        $migration->up();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Relation::morphMap([
            'user' => User::class,
            'tenant' => Tenant::class,
        ]);
    }
}
