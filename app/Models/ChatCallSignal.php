<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatCallSignal extends Model
{
    protected $fillable = ['chat_call_id', 'sender_id', 'type', 'payload'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }

    /** @return BelongsTo<ChatCall, $this> */
    public function call(): BelongsTo
    {
        return $this->belongsTo(ChatCall::class, 'chat_call_id');
    }
}
