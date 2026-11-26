<?php

namespace App\Support;

use Dotenv\Dotenv;
use Throwable;

class EnvironmentMaintenanceSettings
{
    /** @return array{enabled: bool, message: string|null, retryAfter: int|null} */
    public static function fromDotEnv(string $contents): array
    {
        try {
            $values = Dotenv::parse($contents);
        } catch (Throwable) {
            $values = [];
        }

        $enabled = filter_var($values['APP_MAINTENANCE'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $message = trim((string) ($values['APP_MAINTENANCE_MESSAGE'] ?? ''));
        $retry = filter_var($values['APP_MAINTENANCE_RETRY'] ?? null, FILTER_VALIDATE_INT);

        return [
            'enabled' => $enabled,
            'message' => $message !== '' ? $message : null,
            'retryAfter' => $retry !== false && $retry !== null && $retry > 0 ? $retry : null,
        ];
    }
}
