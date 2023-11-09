<?php

namespace Maize\ModelExpires\Listeners;

use Maize\ModelExpires\Events\ModelExpiring;
use Maize\ModelExpires\Support\Config;

class SendModelExpiringNotification
{
    public function handle(ModelExpiring $event): void
    {
        if (! Config::getExpiringNotificationEnabled()) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $event->model->sendModelExpiringNotification();
    }
}
