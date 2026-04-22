<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
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
     * Handle the incoming request to send a password reset link.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLink($request->validated());

        return response()->json([
            'message' => 'Якщо такий email існує, посилання для відновлення пароля буде надіслано.'
        ]);
    }

    /**
     * Handle the incoming request to reset the user's password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Ваш пароль успішно змінено.'])
            : response()->json(['message' => 'Невірний токен або email.'], 400);
    }
}