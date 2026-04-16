<?php

namespace App\Http\Controllers\Api\Payments;

use App\Events\BookingUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\FinishBookingJob;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     * Validates the signature and processes checkout session completions.
     */
    public function handle(Request $request): JsonResponse
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $event = null;

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            default:
                Log::info('Received unknown Stripe event type: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Process a successfully completed checkout session.
     * Updates payment and booking statuses, dispatches events, and queues emails.
     */
    private function handleCheckoutSessionCompleted(object $session): void
    {
        $bookingId = $session->metadata->booking_id ?? null;

        if (!$bookingId) {
            return;
        }

        $payment = Payment::where('booking_id', $bookingId)->first();

        if ($payment) {
            $payment->update([
                'status' => 'succeeded',
                'transaction_id' => $session->id
            ]);
        }

        $booking = Booking::find($bookingId);

        if ($booking) {
            $booking->update(['status' => 'confirmed']);
            $booking->load('room');

            event(new BookingUpdated($booking));

            // Schedule booking finish job
            $finishTime = Carbon::parse($booking->end_time);
            FinishBookingJob::dispatch($booking->id)->delay($finishTime);

            // Queue confirmation email to prevent webhook timeout
            $customerEmail = $booking->guest_email ?? $booking->user?->email;
            if ($customerEmail) {
                Mail::to($customerEmail)->queue(new BookingConfirmed($booking));
            }
        }
    }
}