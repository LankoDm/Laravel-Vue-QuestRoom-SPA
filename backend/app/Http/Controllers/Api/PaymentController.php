<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Create a checkout session URL for the specified booking.
     * Uses the injected PaymentGatewayInterface to support multiple gateways.
     *
     * @param Booking $booking
     * @param PaymentGatewayInterface $paymentGateway
     * @return JsonResponse
     */
    public function createCheckoutSession(Booking $booking, PaymentGatewayInterface $paymentGateway): JsonResponse
    {
        if ($booking->payment && $booking->payment->status === 'succeeded') {
            return response()->json(['message' => 'Бронювання вже оплачено'], 400);
        }

        $url = $paymentGateway->createPaymentUrl($booking);

        return response()->json(['url' => $url]);
    }
}