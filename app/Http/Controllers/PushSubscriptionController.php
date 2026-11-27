<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url:https', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $endpoint = $data['endpoint'];
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));

        if (! $this->isTrustedPushHost($host)) {
            throw ValidationException::withMessages(['endpoint' => __('ui.push_invalid_endpoint')]);
        }

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => 'aes128gcm',
            ],
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url:https', 'max:2000'],
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['subscribed' => false]);
    }

    private function isTrustedPushHost(string $host): bool
    {
        return $host === 'fcm.googleapis.com'
            || $host === 'updates.push.services.mozilla.com'
            || Str::endsWith($host, '.push.services.mozilla.com')
            || $host === 'web.push.apple.com'
            || Str::endsWith($host, '.push.apple.com')
            || Str::endsWith($host, '.notify.windows.com');
    }
}
