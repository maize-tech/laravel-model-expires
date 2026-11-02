<?php

namespace Maize\ModelExpires\Support;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Arr;
use Maize\ModelExpires\Models\Expiration;

class Config
{
    public static function getExpirationModel(): Expiration
    {
        $model = config('model-expires.expiration_model')
            ?? Expiration::class;

        return new $model;
    }

    public static function defaultExpiresAt(?int $days = null): ?CarbonInterface
    {
        $days ??= config('model-expires.model.expires_after_days');

        if (is_null($days)) {
            return null;
        }

        if ($days < 1) {
            throw new Exception;
        }

        return now()
            ->startOfDay()
            ->addDays($days);
    }

    public static function defaultDeletesAt(?int $days = null): ?CarbonInterface
    {
        $days ??= config('model-expires.model.deletes_after_days');

        if (is_null($days)) {
            return null;
        }

        if ($days < 1) {
            throw new Exception;
        }

        return static::defaultExpiresAt()
            ?->startOfDay()
            ?->addDays($days);
    }

    public static function getExpiringNotificationEnabled(): bool
    {
        return config('model-expires.expiring_notification.enabled')
            ?? false;
    }

    public static function getModelExpiringNotification(): ?string
    {
        return config('model-expires.expiring_notification.notification');
    }

    public static function getModelExpiringNotifiables(): array
    {
        return Arr::wrap(
            config('model-expires.expiring_notification.notifiables')
        );
    }
}
