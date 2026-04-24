<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() && !auth('sanctum')->check()) {
            return response()->json([
                'message' => 'Сесія недійсна або вас було заблоковано.'
            ], 401);
        }

        if (auth('sanctum')->check() && auth('sanctum')->user()->is_blocked) {

            auth('sanctum')->user()->tokens()->delete();

            return response()->json([
                'message' => 'Ваш акаунт було заблоковано адміністратором.'
            ], 403);
        }

        return $next($request);
    }
}
