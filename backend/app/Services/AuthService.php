<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user and generate an access token.
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
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            Hash::check($credentials['password'], '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            return null;
        }

        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'email' => ['Ваш акаунт заблоковано адміністратором.']
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Revoke all prior tokens to ensure single active session per user
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Revoke the current access token for the user.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    /**
     * Get the Google OAuth redirect URL.
     */
    public function getGoogleRedirectUrl(): string
    {
        $state = Str::random(64);
        Cache::put($this->googleStateCacheKey($state), true, now()->addMinutes(10));

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * Handle the callback from Google OAuth and authenticate/register the user.
     */
    public function handleGoogleCallback(?string $state = null): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        try {
            if (!$state || !Cache::pull($this->googleStateCacheKey($state))) {
                throw new Exception('Invalid OAuth state.');
            }

            $googleUser = Socialite::driver('google')->stateless()->user();
            if (isset($googleUser->user['email_verified']) && !$googleUser->user['email_verified']) {
                throw new Exception('Google email is not verified.');
            }

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Link Google ID if the user exists but hasn't linked Google yet
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                } elseif ($user->google_id !== $googleUser->getId()) {
                    throw new Exception('Email is associated with a different Google account.');
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

            if ($user && $user->is_blocked) {
                throw new Exception('Account is blocked by administrator.');
            }

            // Clean up old tokens before creating a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // A URL fragment is used to prevent the token from being sent via the Referer header in subsequent requests.
            return $frontendUrl . '/auth/callback#token=' . $token;

        } catch (Exception $e) {
            return $frontendUrl . '/login?error=google_auth_failed';
        }
    }

    private function googleStateCacheKey(string $state): string
    {
        return 'oauth:google:state:' . $state;
    }

    /**
     * Send a password reset link to the given email.
     */
    public function sendResetLink(array $data): string
    {
        return Password::sendResetLink($data);
    }

    /**
     * Reset the user's password using the provided token and credentials.
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
                
                $user->tokens()->delete();
            }
        );
    }
}