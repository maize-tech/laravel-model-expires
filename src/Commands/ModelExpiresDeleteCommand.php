<?php

namespace Maize\ModelExpires\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Traits\Conditionable;
use Maize\ModelExpires\Events\ExpiredModelsDeleted;
use Maize\ModelExpires\HasExpiration;
use Maize\ModelExpires\Support\Config;

class ModelExpiresDeleteCommand extends Command
{
    use Conditionable;

    protected $signature = 'expires:delete
                                {--chunk=1000 : The number of models to delete per chunk}
                                {--mass : forces mass deletion of models without firing events}';

    public $description = 'Delete expired models';

    public function handle(): int
    {
        $chunkSize = $this->option('chunk');

        $models = $this->models();

        if ($models->isEmpty()) {
            $this->info('No expired models found.');

            return self::FAILURE;
        }

        Event::listen(ExpiredModelsDeleted::class, function ($event) {
            $this->line("{$event->model}: {$event->count} records");
        });

        $models->each(function ($model) use ($chunkSize) {
            $total = $this->when(
                $this->option('mass'),
                fn () => $model::massDeleteExpired($chunkSize),
                fn () => $model::deleteExpired($chunkSize),
            );

            if ($total === 0) {
                $this->line("{$model}: 0 records");
            }
        });

        Event::forget(ExpiredModelsDeleted::class);

        $this->comment('All done');

        return self::SUCCESS;
    }

    protected function models(): Collection
    {
        return Config::getExpirationModel()
            ->newQuery()
            ->groupBy('model_type')
            ->pluck('model_type')
            ->map(fn ($alias) => Relation::getMorphedModel($alias))
            ->filter(fn ($model) => class_exists($model))
            ->filter(fn ($model) => in_array(HasExpiration::class, class_uses_recursive($model)))
            ->values();
    }
}
