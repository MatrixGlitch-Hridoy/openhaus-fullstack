<?php

namespace App\Http\Middleware;

use App\Helpers\PublicHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;

class TokenBlacklistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next): Response
    {
        $publicHelper = new PublicHelper();

        try {
            $decodedToken = $publicHelper->GetAndDecodeJWT();

            // Check if the token is blacklisted
            if (Redis::get("blacklist:$decodedToken->jti")) {
                return response()->json(['message' => 'Token blacklisted'], 401);
            } else {
                return $next($request);
            }

            // Set the authenticated user in the request
            // return $request->auth = $decodedToken;
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
