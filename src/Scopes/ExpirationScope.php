<?php

namespace Maize\ModelExpires\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExpirationScope implements Scope
{
    /** @var string[] */
    protected array $extensions = [
        'WithoutExpired',
        'OnlyExpired',
    ];

    public function apply(Builder $builder, Model $model): void
    {
        //
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addWithoutExpired(Builder $builder): void
    {
        $builder->macro('withoutExpired', function (Builder $builder) {
            return $builder
                ->withoutGlobalScope($this)
                ->whereRelation('expiration', 'expires_at', '=', null)
                ->orWhereRelation('expiration', 'expires_at', '>', now()->startOfDay());
        });
    }

    protected function addOnlyExpired(Builder $builder): void
    {
        $builder->macro('onlyExpired', function (Builder $builder) {
            return $builder
                ->withoutGlobalScope($this)
                ->whereRelation('expiration', 'expires_at', '<=', now()->startOfDay());
        });
    }
}
