<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    protected AuthService $authService;

    /**
     * SocialAuthController constructor.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Provide the Google OAuth redirect URL to the frontend.
     */
    public function redirectToGoogle(): JsonResponse
    {
        return response()->json([
            'url' => $this->authService->getGoogleRedirectUrl()
        ]);
    }

    /**
     * Handle the callback from Google and redirect back to the frontend.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $redirectUrl = $this->authService->handleGoogleCallback($request->query('state'));

        return redirect()->away($redirectUrl);
    }
}
