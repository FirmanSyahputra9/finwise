<?php

use App\Support\WebPushSettings;
use Illuminate\Support\Facades\Artisan;
use Minishlink\WebPush\VAPID;

it('parses VAPID keys and subject from environment file contents', function () {
    $settings = WebPushSettings::parse(<<<'ENV'
PUSH_VAPID_PUBLIC_KEY="public-key"
PUSH_VAPID_PRIVATE_KEY="private-key"
PUSH_VAPID_SUBJECT=mailto:admin@example.com
ENV);

    expect($settings)->toBe([
        'publicKey' => 'public-key',
        'privateKey' => 'private-key',
        'subject' => 'mailto:admin@example.com',
    ]);
});

it('generates a valid VAPID key pair without writing it to the environment file', function () {
    Artisan::call('webpush:generate-keys');
    $output = Artisan::output();
    preg_match('/^PUSH_VAPID_PUBLIC_KEY=(.+)$/m', $output, $publicMatch);
    preg_match('/^PUSH_VAPID_PRIVATE_KEY=(.+)$/m', $output, $privateMatch);

    $keys = VAPID::validate([
        'subject' => 'mailto:admin@example.com',
        'publicKey' => $publicMatch[1] ?? '',
        'privateKey' => $privateMatch[1] ?? '',
    ]);

    expect(strlen($keys['publicKey']))->toBe(65)
        ->and(strlen($keys['privateKey']))->toBe(32);
});
