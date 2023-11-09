<?php

namespace Maize\ModelExpires\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ExpiredModelsDeleted
{
    use Dispatchable;

    public function __construct(
        public string $model,
        public int $count
    ) {
    }
}
