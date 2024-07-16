<?php

namespace App\Http\Middleware;

use App\Constants\Http;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHeadersHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set(Http::ACCEPT, Http::APP_JSON);

        return $next($request);
    }
}
