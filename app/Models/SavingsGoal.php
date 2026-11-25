<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsGoal extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['target_amount' => 'integer', 'saved_amount' => 'integer', 'target_date' => 'date'];
    }
}
