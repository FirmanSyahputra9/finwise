<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToUser;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'opening_balance' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Saldo dihitung dari transaksi, bukan disimpan, supaya tidak pernah out of sync. */
    public function getBalanceAttribute(): int
    {
        $sum = fn (string $type) => (int) $this->transactions()->where('type', $type)->sum('amount');

        $transferIn = (int) Transaction::where('to_account_id', $this->id)->sum('amount');

        return $this->opening_balance
            + $sum('income')
            - $sum('expense')
            - $sum('transfer')
            + $transferIn;
    }
}
