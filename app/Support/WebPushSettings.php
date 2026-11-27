<?php

namespace App\Support;

use Dotenv\Dotenv;
use Throwable;

class WebPushSettings
{
    /** @return array{publicKey: string|null, privateKey: string|null, subject: string|null} */
    public function read(): array
    {
        $path = base_path('.env');
        $contents = is_readable($path) ? (string) file_get_contents($path) : '';
        $values = self::parse($contents);

        return [
            'publicKey' => $values['publicKey'] ?: config('services.webpush.public_key'),
            'privateKey' => $values['privateKey'] ?: config('services.webpush.private_key'),
            'subject' => $values['subject'] ?: config('services.webpush.subject'),
        ];
    }

    /** @return array{publicKey: string, privateKey: string, subject: string} */
    public static function parse(string $contents): array
    {
        try {
            $environment = Dotenv::parse($contents);
        } catch (Throwable) {
            $environment = [];
        }

        return [
            'publicKey' => trim((string) ($environment['PUSH_VAPID_PUBLIC_KEY'] ?? '')),
            'privateKey' => trim((string) ($environment['PUSH_VAPID_PRIVATE_KEY'] ?? '')),
            'subject' => trim((string) ($environment['PUSH_VAPID_SUBJECT'] ?? '')),
        ];
    }

    public function publicKey(): ?string
    {
        $key = $this->read()['publicKey'];

        return is_string($key) && $key !== '' ? $key : null;
    }
}
