<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', 'id');
        app()->setLocale(in_array($locale, ['id', 'en', 'ja'], true) ? $locale : 'id');
        \Illuminate\Support\Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}
