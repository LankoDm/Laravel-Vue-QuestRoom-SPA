<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingPaymentService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PaymentGatewayInterface $paymentGateway
    ) {
    }

    /**
     * Authorize and initialize checkout session for booking payment.
     */
    public function createCheckoutSessionUrl(Request $request, Booking $booking): string
    {
        $user = $request->user('sanctum');

        if ($booking->user_id !== null) {
            if (!$user) {
                throw new HttpException(401, 'Потрібна авторизація.');
            }

            Gate::forUser($user)->authorize('view', $booking);
        } else {
            $paymentToken = $request->input('payment_token');
            $isValidGuestToken = $this->bookingService->validateGuestPaymentToken(
                $booking,
                is_string($paymentToken) ? $paymentToken : null
            );

            if (!$isValidGuestToken) {
                throw new HttpException(403, 'Недійсний токен оплати.');
            }
        }

        if ($booking->payment_status === 'paid' || ($booking->payment && $booking->payment->status === 'succeeded')) {
            throw new HttpException(400, 'Бронювання вже оплачено');
        }

        try {
            return $this->paymentGateway->createPaymentUrl($booking);
        } catch (\Throwable $e) {
            throw new HttpException(502, 'Не вдалося ініціалізувати оплату. Спробуйте ще раз.');
        }
    }
}
