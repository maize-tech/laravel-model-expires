<?php

namespace Maize\ModelExpires;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Maize\ModelExpires\Events\ExpiredModelsDeleted;
use Maize\ModelExpires\Scopes\ExpirationScope;
use Maize\ModelExpires\Support\Config;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withExpired(bool $withExpired = true)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutExpired()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlyExpired()
 */
trait HasExpiration
{
    public static function bootHasExpiration(): void
    {
        static::addGlobalScope(new ExpirationScope);

        static::created(
            callback: fn ($model) => $model->setExpiresAt(
                expiresAt: static::defaultExpiresAt(),
                deletesAt: static::defaultDeletesAt(),
            )
        );

        static::deleted(
            callback: fn ($model) => $model->expiration()->delete()
        );
    }

    public function expiration(): MorphOne
    {
        return $this->morphOne(Config::getExpirationModel(), 'model');
    }

    public function setExpiresAt(
        ?Carbon $expiresAt = null,
        ?Carbon $deletesAt = null
    ): self {
        $this
            ->expiration()
            ->updateOrCreate([
                'model_id' => $this->getKey(),
                'model_type' => $this->getMorphClass(),
            ], [
                'expires_at' => $expiresAt?->startOfDay(),
                'deletes_at' => $deletesAt?->startOfDay(),
            ]);

        return $this;
    }

    protected static function defaultExpiresAt(): ?Carbon
    {
        return Config::defaultExpiresAt();
    }

    protected static function defaultDeletesAt(): ?Carbon
    {
        return Config::defaultDeletesAt();
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this
            ->expiration()
            ->value('expires_at');
    }

    public function getDeletesAt(): ?Carbon
    {
        return $this
            ->expiration()
            ->value('deletes_at');
    }

    public function isExpired(): bool
    {
        return (bool) $this->getExpiresAt()?->isPast();
    }

    public function getDaysLeftToExpiration(): ?int
    {
        $expiresAt = $this->getExpiresAt();

        if (is_null($expiresAt)) {
            return null;
        }

        if ($expiresAt->isPast()) {
            return 0;
        }

        return now()->startOfDay()->diffInDays(
            $expiresAt->startOfDay()
        );
    }

    public function getDaysLeftToDeletion(): ?int
    {
        $deletesAt = $this->getDeletesAt();

        if (is_null($deletesAt)) {
            return null;
        }

        if ($deletesAt->isPast()) {
            return 0;
        }

        return $deletesAt
            ->startOfDay()
            ->diffInDays(
                now()->startOfDay()
            );
    }

    public function canExpire(): bool
    {
        return ! is_null(
            $this->getExpiresAt()
        );
    }

    public function sendModelExpiringNotification(): void
    {
        $notification = $this->getModelExpiringNotification();

        if (is_null($notification)) {
            return;
        }

        Notification::route(
            channel: 'mail',
            route: $this->getModelExpiringNotifiables()
        )->notify(
            notification: new $notification($this)
        );
    }

    public function getModelExpiringNotifiables(): array
    {
        return Config::getModelExpiringNotifiables();
    }

    public function getModelExpiringNotification(): ?string
    {
        return Config::getModelExpiringNotification();
    }

    public static function fireExpiringEventBeforeDays(): array
    {
        return [];
    }

    public static function deleteExpired(int $chunkSize = 1000): int
    {
        $total = 0;

        static::onlyExpired()
            ->whereRelation('expiration', 'deletes_at', '<=', now()->startOfDay())
            ->chunkById($chunkSize, function (Collection $models) use (&$total) {
                $models->each->delete();

                $total += $models->count();

                ExpiredModelsDeleted::dispatch(static::class, $total);
            });

        return $total;
    }

    public static function massDeleteExpired(int $chunkSize = 1000): int
    {
        $query = tap(
            value: static::onlyExpired()
                ->whereRelation('expiration', 'deletes_at', '<=', now()->startOfDay()),
            callback: fn (Builder $query) => $query->when(
                ! $query->getQuery()->limit,
                fn (Builder $query) => $query->limit($chunkSize)
            )
        );

        $total = 0;

        do {
            $total += $count = $query->delete();

            if ($count > 0) {
                ExpiredModelsDeleted::dispatch(static::class, $total);
            }
        } while ($count > 0);

        return $total;
    }
}
