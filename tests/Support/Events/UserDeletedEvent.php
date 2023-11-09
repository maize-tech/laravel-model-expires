<?php

namespace Maize\ModelExpires\Tests\Support\Events;

use Maize\ModelExpires\Tests\Support\Models\User;

class UserDeletedEvent
{
    public function __construct(
        public User $user,
    ) {
        //
    }
}
