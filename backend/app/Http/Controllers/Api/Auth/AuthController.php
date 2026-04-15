<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected AuthService $authService;

    /**
     * Inject the AuthService.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle incoming registration requests.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Реєстрація успішна',
            'user' => $result['user'],
            'token' => $result['token']
        ], 201);
    }

    /**
     * Handle incoming login requests with Brute Force protection.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Generate a unique throttle key based on email and IP address
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // Check if the user has exceeded the maximum number of login attempts (5 attempts)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Забагато спроб входу. Спробуйте через ' . $seconds . ' секунд.'
            ], 429);
        }

        $result = $this->authService->login($request->validated());

        if (!$result) {
            // Record a failed login attempt
            RateLimiter::hit($throttleKey);

            return response()->json([
                'message' => 'Невірний email або пароль'
            ], 401);
        }

        // Clear login attempts upon successful authentication
        RateLimiter::clear($throttleKey);

        return response()->json([
            'message' => 'Ви успішно увійшли',
            'user' => $result['user'],
            'token' => $result['token']
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Ви успішно вийшли з системи'
        ]);
    }
}