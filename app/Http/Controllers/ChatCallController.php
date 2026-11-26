<?php

namespace App\Http\Controllers;

use App\Models\ChatCall;
use App\Models\ChatCallSignal;
use App\Models\ChatConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatCallController extends Controller
{
    public function start(int $connectionId): JsonResponse
    {
        $connection = $this->authorizedConnection($connectionId);
        $now = now();
        ChatCall::query()->where('connection_id', $connection->id)
            ->where('created_at', '<', $now->copy()->subDay())->delete();

        $staleCalls = ChatCall::query()->where('connection_id', $connection->id)
            ->whereIn('status', ChatCall::ACTIVE_STATUSES)
            ->where('updated_at', '<', $now->copy()->subSeconds(90))->get();
        foreach ($staleCalls as $staleCall) {
            $staleCall->update(['status' => 'ended']);
            $staleCall->signals()->delete();
        }

        abort_if(ChatCall::query()->where('connection_id', $connection->id)
            ->whereIn('status', ChatCall::ACTIVE_STATUSES)->exists(), 409);

        $calleeId = (int) $connection->user_one_id === (int) auth()->id() ? $connection->user_two_id : $connection->user_one_id;
        $call = ChatCall::create([
            'uuid' => (string) Str::uuid(),
            'connection_id' => $connection->id,
            'caller_id' => auth()->id(),
            'callee_id' => $calleeId,
            'status' => 'ringing',
            'caller_seen_at' => $now,
        ]);

        return response()->json(['id' => $call->uuid, 'status' => $call->status], 201);
    }

    public function incoming(): JsonResponse
    {
        $now = now();
        ChatCall::query()->where('callee_id', auth()->id())->where('created_at', '<', $now->copy()->subDay())->delete();
        ChatCall::query()->where('callee_id', auth()->id())->where('status', 'ringing')
            ->where('created_at', '<', $now->copy()->subSeconds(60))
            ->update(['status' => 'missed', 'updated_at' => $now]);

        $call = ChatCall::query()->with('caller:id,name')
            ->where('callee_id', auth()->id())->where('status', 'ringing')
            ->whereHas('connection', fn ($query) => $query->where('status', 'accepted')
                ->where(fn ($participants) => $participants->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id())))
            ->latest('id')->first();

        return response()->json(['call' => $call ? [
            'id' => $call->uuid,
            'connection_id' => $call->connection_id,
            'status' => $call->status,
            'caller' => $call->caller?->name,
        ] : null]);
    }

    public function accept(string $uuid): JsonResponse
    {
        $call = $this->authorizedCall($uuid);
        abort_unless((int) $call->callee_id === (int) auth()->id(), 403);
        abort_unless($call->status === 'ringing' && $call->created_at->gt(now()->subSeconds(60)), 409);
        $call->update(['status' => 'accepted', 'callee_seen_at' => now()]);

        return response()->json(['status' => $call->status]);
    }

    public function reject(string $uuid): JsonResponse
    {
        $call = $this->authorizedCall($uuid);
        abort_unless((int) $call->callee_id === (int) auth()->id(), 403);
        abort_unless($call->status === 'ringing', 409);
        $call->update(['status' => 'rejected']);
        $call->signals()->delete();

        return response()->json(['status' => $call->status]);
    }

    public function end(string $uuid): JsonResponse
    {
        $call = $this->authorizedCall($uuid);
        if (in_array($call->status, ChatCall::ACTIVE_STATUSES, true)) {
            $call->update(['status' => 'ended']);
            $call->signals()->delete();
        }

        return response()->json(['status' => $call->fresh()->status]);
    }

    public function signals(Request $request, string $uuid): JsonResponse
    {
        $call = $this->authorizedCall($uuid);
        if (! in_array($call->status, ChatCall::ACTIVE_STATUSES, true)) {
            return response()->json(['status' => $call->status, 'signals' => []]);
        }
        if ($call->status === 'ringing') {
            if ($call->created_at->lt(now()->subSeconds(60))) {
                $call->update(['status' => 'missed']);

                return response()->json(['status' => 'missed', 'signals' => []]);
            }

            return response()->json(['status' => $call->status, 'signals' => []]);
        }
        $after = max(0, (int) $request->query('after', 0));
        $userId = (int) auth()->id();
        $seenColumn = $userId === (int) $call->caller_id ? 'caller_seen_at' : 'callee_seen_at';
        $call->forceFill([$seenColumn => now()])->save();

        $lastCaller = $call->caller_seen_at;
        $lastCallee = $call->callee_seen_at;
        if ($lastCaller && $lastCallee && min(strtotime((string) $lastCaller), strtotime((string) $lastCallee)) < now()->subSeconds(90)->getTimestamp()) {
            $call->update(['status' => 'ended']);
            $call->signals()->delete();

            return response()->json(['status' => 'ended', 'signals' => []]);
        }

        $signals = $call->signals()->where('id', '>', $after)->where('sender_id', '!=', $userId)
            ->orderBy('id')->limit(100)->get(['id', 'sender_id', 'type', 'payload']);

        return response()->json([
            'status' => $call->status,
            'signals' => $signals->map(fn (ChatCallSignal $signal) => [
                'id' => $signal->id,
                'type' => $signal->type,
                'payload' => $signal->payload,
            ])->values(),
        ]);
    }

    public function sendSignal(Request $request, string $uuid): JsonResponse
    {
        $call = $this->authorizedCall($uuid);
        abort_unless($call->status === 'accepted', 409);
        $data = $request->validate([
            'type' => ['required', 'in:offer,answer,candidate'],
            'payload' => ['required', 'array'],
        ]);
        abort_if(strlen(json_encode($data['payload'], JSON_THROW_ON_ERROR)) > 100_000, 413);

        $userId = (int) auth()->id();
        abort_if($data['type'] === 'offer' && $userId !== (int) $call->caller_id, 403);
        abort_if($data['type'] === 'answer' && $userId !== (int) $call->callee_id, 403);

        if ($data['type'] === 'offer' || $data['type'] === 'answer') {
            validator($data['payload'], ['type' => ['required', 'in:offer,answer'], 'sdp' => ['required', 'string', 'max:90000']])->validate();
            abort_unless($data['payload']['type'] === $data['type'], 422);
        } else {
            validator($data['payload'], ['candidate' => ['required', 'string', 'max:8192']])->validate();
        }

        $signal = $call->signals()->create([
            'sender_id' => $userId,
            'type' => $data['type'],
            'payload' => $data['payload'],
        ]);

        return response()->json(['id' => $signal->id], 201);
    }

    private function authorizedConnection(int $id): ChatConnection
    {
        return ChatConnection::query()->whereKey($id)->where('status', 'accepted')
            ->where(fn ($query) => $query->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id()))
            ->firstOrFail();
    }

    private function authorizedCall(string $uuid): ChatCall
    {
        $call = ChatCall::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless(in_array((int) auth()->id(), [(int) $call->caller_id, (int) $call->callee_id], true), 404);
        $this->authorizedConnection((int) $call->connection_id);

        return $call;
    }
}
