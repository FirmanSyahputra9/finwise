<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = ['connection_id', 'group_id', 'sender_id', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }
}
