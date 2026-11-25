<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function spent(): int
    {
        return (int) Transaction::ofType(TransactionType::Expense)
            ->where('category_id', $this->category_id)
            ->inMonth($this->year, $this->month)
            ->sum('amount');
    }
}
