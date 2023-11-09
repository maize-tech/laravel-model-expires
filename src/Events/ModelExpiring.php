<?php

namespace Maize\ModelExpires\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ModelExpiring
{
    use Dispatchable;

    public function __construct(
        public Model $model
    ) {
    }
}
