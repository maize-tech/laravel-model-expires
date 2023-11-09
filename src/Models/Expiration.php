<?php

namespace Maize\ModelExpires\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expiration extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'model_id',
        'model_type',
        'expires_at',
        'deletes_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'deletes_at' => 'datetime',
    ];
}
