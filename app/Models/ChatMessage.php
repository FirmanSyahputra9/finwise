<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatMessage extends Model
{
    public const STICKERS = ['😻', '😹', '😿', '😽', '🐱', '🐈‍⬛', '🐾', '❤️', '👍', '🎉', '😂', '🥰'];

    public const STICKER_PREFIX = '__finwise_sticker:';

    protected $fillable = ['connection_id', 'group_id', 'sender_id', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted'];
    }

    /** @return list<array{type: string, path: string, caption: string}> */
    public function imageAttachments(): array
    {
        $payload = json_decode($this->body, true);
        if (! is_array($payload)) {
            return [];
        }

        if (($payload['__finwise_attachment'] ?? null) === 'image') {
            $paths = [$payload['path'] ?? null];
        } elseif (($payload['__finwise_attachment'] ?? null) === 'images' && is_array($payload['items'] ?? null)) {
            $paths = $payload['items'];
        } else {
            return [];
        }

        $caption = is_string($payload['caption'] ?? null) ? $payload['caption'] : '';

        return collect($paths)
            ->filter(fn ($path) => is_string($path) && preg_match('#^chat-attachments/[a-f0-9-]+\.webp$#i', $path))
            ->map(fn (string $path) => ['type' => 'image', 'path' => $path, 'caption' => $caption])
            ->values()
            ->all();
    }

    /** @return array{type: string, path: string, caption: string}|null */
    public function imageAttachment(): ?array
    {
        return $this->imageAttachments()[0] ?? null;
    }

    public function sticker(): ?string
    {
        if (! str_starts_with($this->body, self::STICKER_PREFIX)) {
            return null;
        }

        $sticker = substr($this->body, strlen(self::STICKER_PREFIX));

        return in_array($sticker, self::STICKERS, true) ? $sticker : null;
    }

    public function deleteAttachmentFile(): void
    {
        collect($this->imageAttachments())->each(fn (array $attachment) => Storage::disk('local')->delete($attachment['path']));
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChatConnection::class, 'connection_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }
}
