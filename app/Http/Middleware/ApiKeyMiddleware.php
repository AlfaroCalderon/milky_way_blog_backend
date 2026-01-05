<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $apikey = $request->header('X-API-Key');

        $validApiKey = config('app.apikey');

        if(!$apikey || $apikey != $validApiKey ){
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'invalid or missing API key'
            ], 401);
        }
        return $next($request);
    }
}
