<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Otomatis isi user_id saat create dan batasi query ke data milik user login.
 */
trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (auth()->check()) {
                $query->where($query->getModel()->getTable().'.user_id', auth()->id());
            }
        });

        static::creating(function ($model) {
            if (! $model->user_id && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
