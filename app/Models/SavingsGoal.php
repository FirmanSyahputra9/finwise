<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    use BelongsToUser;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['target_amount' => 'integer', 'saved_amount' => 'integer', 'target_date' => 'date'];
    }
}
