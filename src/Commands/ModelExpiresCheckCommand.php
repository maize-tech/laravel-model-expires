<?php

namespace Maize\ModelExpires\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Maize\ModelExpires\Events\ModelExpiring;
use Maize\ModelExpires\HasExpiration;
use Maize\ModelExpires\Support\Config;

class ModelExpiresCheckCommand extends Command
{
    protected $signature = 'expires:check
                                {--chunk=1000 : The number of models to retrieve per chunk}';

    public $description = 'Send an event for expiring models';

    public function handle(): int
    {
        $chunkSize = $this->option('chunk');

        $models = $this->models();

        if ($models->isEmpty()) {
            $this->info('No expiring models found.');

            return self::FAILURE;
        }

        $models->each(function ($model) use ($chunkSize) {
            $total = 0;

            $query = $model::query();

            collect(
                $model::fireExpiringEventBeforeDays()
            )->each(
                fn ($days) => $query->orWhereRelation('expiration', 'expires_at', '=', now()->addDays($days)->startOfDay())
            );

            $query->chunkById($chunkSize, function (Collection $models) use (&$total) {
                $models->each(
                    fn (Model $model) => ModelExpiring::dispatch($model)
                );

                $total += $models->count();
            });

            $this->line("{$model}: {$total} expiring");
        });

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
