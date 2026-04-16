<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /**
     * Create a checkout session URL for the specified booking.
     * Uses the injected PaymentGatewayInterface to support multiple gateways.
     */
    public function createCheckoutSession(\Illuminate\Http\Request $request, Booking $booking, PaymentGatewayInterface $paymentGateway): JsonResponse
    {
        if ($booking->user_id !== null) {
            Gate::forUser($request->user('sanctum'))->authorize('view', $booking);
        }

        if ($booking->payment && $booking->payment->status === 'succeeded') {
            return response()->json(['message' => 'Бронювання вже оплачено'], 400);
        }

        $url = $paymentGateway->createPaymentUrl($booking);

        return response()->json(['url' => $url]);
    }
}