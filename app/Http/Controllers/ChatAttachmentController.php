<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Support\ChatConversationUuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController
{
    public function __invoke(Request $request, string $uuid): StreamedResponse
    {
        $userId = (int) $request->user()->id;
        $attachmentIndex = null;
        $message = ChatMessage::query()
            ->where(function ($query) use ($userId) {
                $query->whereHas('connection', fn ($connection) => $connection->where('status', 'accepted')
                    ->where(fn ($members) => $members->where('user_one_id', $userId)->orWhere('user_two_id', $userId)))
                    ->orWhereHas('group.members', fn ($members) => $members->where('users.id', $userId));
            })
            ->orderBy('id')
            ->cursor()
            ->first(function (ChatMessage $candidate) use ($uuid, &$attachmentIndex) {
                foreach ($candidate->imageAttachments() as $index => $attachment) {
                    if (hash_equals(ChatConversationUuid::from('attachment', (int) $candidate->id, $index), strtolower($uuid))) {
                        $attachmentIndex = $index;

                        return true;
                    }
                }

                return false;
            });

        abort_unless($message && $attachmentIndex !== null, 404);
        $attachment = $message->imageAttachments()[$attachmentIndex];

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment['path']), 404);

        $path = $attachment['path'];

        $headers = [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="putraganteng-'.$uuid.'.webp"';
        }

        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);
            if (! is_resource($stream)) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}
