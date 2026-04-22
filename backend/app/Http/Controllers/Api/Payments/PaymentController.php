<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    public function __construct(private readonly BookingPaymentService $bookingPaymentService)
    {
    }

    /**
     * Create a checkout session URL for the specified booking.
     * Uses the injected PaymentGatewayInterface to support multiple gateways.
     */
    public function createCheckoutSession(Request $request, Booking $booking): JsonResponse
    {
        try {
            $url = $this->bookingPaymentService->createCheckoutSessionUrl($request, $booking);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['url' => $url]);
    }
}