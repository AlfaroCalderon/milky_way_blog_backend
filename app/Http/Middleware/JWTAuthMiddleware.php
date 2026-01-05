<?php

namespace App\Http\Middleware;

use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JWTAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected $jwtservice;

    public function __construct(JWTService $jwtservice){
         $this->jwtservice = $jwtservice;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if(!$header || !str_starts_with($header, 'bearer')){
            return response()->json([
                'status' => 'not_authenticated',
                'message' => 'Invalid or missing authorization header'
            ],401);
        }

        $token = substr($header, strlen('bearer '));

        try {
            $decoded = $this->jwtservice->decodeAccessToken($token);

            if($decoded->type != 'access_token'){
                return response()->json([
                     'status' => 'unauthorized',
                     'message' => 'Invalid token type'
                ],401);
            }

            $request->merge([
                'auth_user_id' => $decoded->user_id ?? null,
                'auth_user_rol' => $decoded->user_rol ?? null,
                'auth_user_email' => $decoded->user_email ?? null
            ]);

            return $next($request);

        } catch (\Exception $error) {
           return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid or expired token: ' . $error->getMessage()
            ], 401);
        }
    }
}
