<?php

namespace App\Http\Middleware;

use App\Support\DeletionRetention;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PurgeExpiredUserDeletions
{
    public function __construct(private readonly DeletionRetention $retention) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->retention->purgeExpired($request->user());
        }

        return $next($request);
    }
}
