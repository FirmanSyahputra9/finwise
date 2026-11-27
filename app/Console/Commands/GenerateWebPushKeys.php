<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:generate-keys';

    protected $description = 'Generate VAPID keys for browser push notifications';

    public function handle(): int
    {
        $keys = $this->generateKeys();

        $this->line('PUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('PUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('PUSH_VAPID_SUBJECT=mailto:your-email@example.com');
        $this->newLine();
        $this->warn('Save these values in .env and keep the private key secret. Generate this pair only once.');

        return self::SUCCESS;
    }

    /** @return array{publicKey: string, privateKey: string} */
    private function generateKeys(): array
    {
        $options = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];
        $windowsConfig = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf';

        if (PHP_OS_FAMILY === 'Windows' && is_file($windowsConfig)) {
            $options['config'] = $windowsConfig;
        }

        $key = openssl_pkey_new($options);
        $details = $key ? openssl_pkey_get_details($key) : false;

        if (! is_array($details) || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            throw new RuntimeException('OpenSSL could not generate a P-256 VAPID key pair.');
        }

        return [
            'publicKey' => $this->base64UrlEncode("\x04".$details['ec']['x'].$details['ec']['y']),
            'privateKey' => $this->base64UrlEncode($details['ec']['d']),
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
