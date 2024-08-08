<?php

namespace App\Http\Middleware;

use App\Http\Constants\Http;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHeadersHandler
{
    protected const ACCEPT = 'Accept';
    protected const APP_JSON = 'application/json';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set(self::ACCEPT, self::APP_JSON);

        return $next($request);
    }
}
