<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->cookie('locale', 'en'));
        app()->setLocale(in_array($locale, ['en', 'my'], true) ? $locale : 'en');

        return $next($request);
    }
}
