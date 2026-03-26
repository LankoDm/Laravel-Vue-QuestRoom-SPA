<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmed;
use App\Jobs\FinishBookingJob;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature'); // Беремо підпис Stripe із заголовків
        $event = null;
        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $bookingId = $session->metadata->booking_id ?? null;
                if ($bookingId) {
                    $payment = Payment::where('booking_id', $bookingId)->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'succeeded',
                            'transaction_id' => $session->id
                        ]);
                    }
                    $booking = Booking::findOrFail($bookingId);
                    if ($booking) {
                        $booking->update(['status' => 'confirmed']);
                        $finishTime = Carbon::parse($booking->end_time);
                        FinishBookingJob::dispatch($booking->id)->delay($finishTime);
                        $customerEmail = $booking->guest_email ?? $booking->user?->email;
                        if ($customerEmail) {
                            Mail::to($customerEmail)->send(new BookingConfirmed($booking));
                        }
                    }

                }
                break;
            default:
                Log::info('Отримано невідомий тип події від Stripe: ' . $event->type);
        }
        return response()->json(['status' => 'success']);
    }
}
