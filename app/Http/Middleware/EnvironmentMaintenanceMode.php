<?php

namespace App\Http\Middleware;

use App\Support\EnvironmentMaintenanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnvironmentMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        $environmentFile = base_path('.env');

        if (! is_readable($environmentFile)) {
            return $next($request);
        }

        $settings = EnvironmentMaintenanceSettings::fromDotEnv((string) file_get_contents($environmentFile));

        if (! $settings['enabled']) {
            return $next($request);
        }

        $response = response()->view('errors.maintenance', [
            'maintenanceMessage' => $settings['message'],
            'retryAfter' => $settings['retryAfter'],
        ], 503);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
