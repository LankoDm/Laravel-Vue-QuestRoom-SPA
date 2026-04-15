<?php

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\StripePaymentService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Binds the PaymentGatewayInterface to the Stripe implementation.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentService::class);
    }

    /**
     * Bootstrap any application services.
     * Configures the custom URL for the password reset emails.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return $frontendUrl . '/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
        });
        Gate::define('view-dashboard', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });
    }
}