<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /**
     * @var BookingService
     */
    protected BookingService $bookingService;

    /**
     * Inject the BookingService.
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Display a listing of the bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = ($user->isAdmin() || $user->isManager())
            ? Booking::with(['room'])->get()
            : Booking::with(['room'])->where('user_id', $user->id)->get();

        return response()->json($bookings);
    }

    /**
     * Store a newly created booking.
     */
    public function store(BookingRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $booking = $this->bookingService->createBooking(
            $request->validated() + ['hold_token' => $request->hold_token],
            $userId
        );

        return response()->json($booking, 201);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = Booking::with(['room'])->findOrFail($id);

        Gate::authorize('view', $booking);

        if (in_array($booking->status, ['confirmed', 'finished'])) {
            $booking->ticket_url = URL::signedRoute('ticket.download', ['booking' => $booking->id]);
        }

        return response()->json($booking);
    }

    /**
     * Update the specified booking.
     */
    public function update(BookingRequest $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('update', $booking);
        $booking->update($request->safe()->only(['status', 'admin_note']));

        return response()->json($booking);
    }

    /**
     * Confirm the booking and schedule the finish job.
     */
    public function bookingConfirmation(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        Gate::authorize('update', $booking);
        try {
            $this->bookingService->confirmBooking($booking);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Бронювання підтверджено. Задача на завершення створена.',
            'booking' => $booking->fresh()
        ]);
    }

    /**
     * Cancel the booking.
     */
    public function bookingCancellation(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('update', $booking);
        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Бронювання скасовано.']);
    }

    /**
     * Finish the booking.
     */
    public function bookingFinish(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('update', $booking);
        $booking->update(['status' => 'finished']);

        return response()->json(['message' => 'Бронювання успішно завершено.']);
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('delete', $booking);
        $booking->delete();

        return response()->json(['message' => 'Бронювання видалено.']);
    }

    /**
     * Get bookings for the authenticated user.
     */
    public function myBookings(Request $request): JsonResponse
    {
        $bookings = Booking::with('room:id,name,image_path,slug')
            ->where('user_id', $request->user()->id)
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($booking) {
                if (in_array($booking->status, ['confirmed', 'finished'])) {
                    $booking->ticket_url = URL::signedRoute('ticket.download', ['booking' => $booking->id]);
                }
                return $booking;
            });

        return response()->json($bookings);
    }

    /**
     * Hold a specific time slot temporarily.
     */
    public function holdSlot(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'hold_token' => 'required|string',
        ]);

        $this->bookingService->holdSlot(
            $request->room_id,
            Carbon::parse($request->start_time),
            $request->hold_token
        );

        return response()->json(['message' => 'Час успішно зарезервовано на 10 хвилин.']);
    }

    /**
     * Release a previously held time slot.
     */
    public function releaseSlot(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required',
            'start_time' => 'required|date',
            'hold_token' => 'required|string',
        ]);

        $this->bookingService->releaseHoldToken(
            $request->room_id,
            Carbon::parse($request->start_time)
        );

        return response()->json(['message' => 'Резерв знято.']);
    }

    /**
     * Download the ticket as PDF.
     */
    public function downloadTicket(Booking $booking)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('emails.booking_confirmed', [
            'booking' => $booking,
            'isPdf' => true
        ]);

        return $pdf->download("Ticket_Onea_Quests_{$booking->id}.pdf");
    }

    /**
     * Update the admin note for a booking.
     */
    public function updateNote(Request $request, Booking $booking): JsonResponse
    {
        $booking->update(['admin_note' => $request->admin_note]);

        return response()->json(['message' => 'Note updated']);
    }
}