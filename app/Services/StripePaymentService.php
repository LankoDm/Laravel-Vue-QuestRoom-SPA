<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripePaymentService implements PaymentGatewayInterface
{
    public function createPaymentUrl(Booking $booking): string
    {
        Stripe::setApiKey(config('services.stripe.secret')); // секретний ключ
        $customerEmail = $booking->guest_email ?? $booking->user?->email;
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $sessionConfig = [ // створюємо сесію в Stripe
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'uah',
                    'product_data' => [
                        'name' => 'Квест: ' . $booking->room->name,
                    ],
                    'unit_amount' => $booking->total_price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/cancel',
            'metadata' => [
                'booking_id' => $booking->id,
            ],
            'billing_address_collection' => 'auto',
        ];
        if ($customerEmail) {
            $sessionConfig['customer_email'] = $customerEmail;
        }
        $session = Session::create($sessionConfig);
        $booking->payment()->updateOrCreate( // зберігаємо транзакцію в БД
            ['booking_id' => $booking->id],
            [
                'transaction_id' => $session->id,
                'amount' => $booking->total_price,
                'currency' => 'uah',
                'status' => 'pending',
                'payment_method' => 'stripe'
            ]
        );
        return $session->url;
    }
}
