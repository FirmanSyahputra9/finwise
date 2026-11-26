<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatCall extends Model
{
    public const ACTIVE_STATUSES = ['ringing', 'accepted'];

    protected $fillable = [
        'uuid', 'connection_id', 'caller_id', 'callee_id', 'status',
        'caller_seen_at', 'callee_seen_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'caller_seen_at' => 'datetime',
            'callee_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ChatConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChatConnection::class, 'connection_id');
    }

    /** @return BelongsTo<User, $this> */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /** @return BelongsTo<User, $this> */
    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }

    /** @return HasMany<ChatCallSignal, $this> */
    public function signals(): HasMany
    {
        return $this->hasMany(ChatCallSignal::class);
    }
}
