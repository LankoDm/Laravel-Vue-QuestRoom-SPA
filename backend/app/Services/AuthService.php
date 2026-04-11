<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new user and generate an access token.
     *
     * @param array $data
     * @return array Contains the User model and plain text token
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Clean up any potential existing tokens (prevent DB bloat)
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Authenticate a user by email and password.
     *
     * @param array $credentials
     * @return array|null Contains the User model and token, or null if invalid
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Revoke all prior tokens to ensure single active session per user
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Revoke the current access token for the user.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Get the Google OAuth redirect URL.
     *
     * @return string
     */
    public function getGoogleRedirectUrl(): string
    {
        return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
    }

    /**
     * Handle the callback from Google OAuth and authenticate/register the user.
     *
     * @return string The frontend redirect URL with token or error parameter
     */
    public function handleGoogleCallback(): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google ID if the user exists but hasn't linked Google yet
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            } else {
                // Register a new user via Google
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null // OAuth users do not require a standard password
                ]);
            }

            // Clean up old tokens before creating a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $frontendUrl . '/auth/callback?token=' . $token;

        } catch (Exception $e) {
            return $frontendUrl . '/login?error=google_auth_failed';
        }
    }

    /**
     * Send a password reset link to the given email.
     *
     * @param array $data Contains the user's email
     * @return string Status constant from Password broker
     */
    public function sendResetLink(array $data): string
    {
        return Password::sendResetLink($data);
    }

    /**
     * Reset the user's password using the provided token and credentials.
     *
     * @param array $data Contains email, password, password_confirmation, and token
     * @return string Status constant from Password broker
     */
    public function resetPassword(array $data): string
    {
        return Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );
    }
}