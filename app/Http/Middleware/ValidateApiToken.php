<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        try {
            // For auth service, we validate the token using Laravel Sanctum directly
            // since this IS the auth service
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

            if (!$user) {
                Log::warning('Invalid token provided to auth service', [
                    'service' => 'fitnease-auth',
                    'token_preview' => substr($token, 0, 10) . '...',
                    'ip' => $request->ip()
                ]);

                return response()->json(['error' => 'Invalid token'], 401);
            }

            Log::info('Token validation successful in auth service', [
                'service' => 'fitnease-auth',
                'user_id' => $user->user_id,
                'user_email' => $user->email
            ]);

            // Store user data in request attributes for controllers
            $request->attributes->set('user', $user->toArray());
            $request->attributes->set('user_id', $user->user_id);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Failed to validate token in auth service', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'token_preview' => substr($token ?? '', 0, 10) . '...'
            ]);

            return response()->json(['error' => 'Token validation failed'], 500);
        }
    }
}