<?php

namespace Maize\ModelExpires\Tests\Support\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Maize\ModelExpires\HasExpiration;
use Maize\ModelExpires\Support\Config;
use Maize\ModelExpires\Tests\Support\Factories\TenantFactory;

class Tenant extends Model
{
    use HasExpiration;
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }

    protected static function defaultExpiresAt(): ?CarbonInterface
    {
        return Config::defaultExpiresAt(365);
    }

    protected static function defaultDeletesAt(): ?CarbonInterface
    {
        return Config::defaultDeletesAt(60);
    }
}
