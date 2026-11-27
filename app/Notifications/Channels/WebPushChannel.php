<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Support\WebPushSender;
use Illuminate\Notifications\Notification;
use Throwable;

class WebPushChannel
{
    public function __construct(private readonly WebPushSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toArray')) {
            return;
        }

        try {
            $this->sender->send($notifiable, $notification->toArray($notifiable));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
