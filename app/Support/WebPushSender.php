<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushSender
{
    /** @param array<string, mixed> $details */
    public function send(User $user, array $details): void
    {
        $settings = app(WebPushSettings::class)->read();
        $publicKey = $settings['publicKey'];
        $privateKey = $settings['privateKey'];
        $subject = $settings['subject'];

        if (! $publicKey || ! $privateKey || ! $subject) {
            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ], ['TTL' => 300], new Client(['connect_timeout' => 3, 'timeout' => 8]));

            $payload = json_encode([
                'title' => __('ui.financial_activity'),
                'body' => __('ui.push_financial_activity'),
                'url' => route('dashboard'),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]), $payload);
            }

            foreach ($webPush->flush() as $report) {
                $endpointHash = hash('sha256', $report->getEndpoint());

                if ($report->isSubscriptionExpired()) {
                    PushSubscription::query()->where('endpoint_hash', $endpointHash)->delete();
                } elseif (! $report->isSuccess()) {
                    Log::warning('Web push delivery failed.', [
                        'user_id' => $user->id,
                        'reason' => $report->getReason(),
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Web push delivery could not be completed.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
