<?php

namespace Maize\ModelExpires\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Maize\ModelExpires\HasExpiration;
use Maize\ModelExpires\Tests\Support\Events\UserDeletedEvent;
use Maize\ModelExpires\Tests\Support\Factories\UserFactory;

class User extends Authenticatable
{
    use HasExpiration;
    use HasFactory;

    protected $fillable = [];

    protected $dispatchesEvents = [
        'deleted' => UserDeletedEvent::class,
    ];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    public static function fireExpiringEventBeforeDays(): array
    {
        return [
            5,
            10,
        ];
    }
}
