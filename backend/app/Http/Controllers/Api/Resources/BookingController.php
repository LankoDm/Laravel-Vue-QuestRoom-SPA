<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\HoldSlotRequest;
use App\Http\Requests\ReleaseSlotRequest;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /**
     * @var BookingService
     */
    protected BookingService $bookingService;
    protected TicketService $ticketService;

    /**
     * Inject the BookingService.
     */
    public function __construct(BookingService $bookingService, TicketService $ticketService)
    {
        $this->bookingService = $bookingService;
        $this->ticketService = $ticketService;
    }

    /**
     * Display a listing of the bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getIndexBookings($request);

        return response()->json($bookings);
    }

    /**
     * Store a newly created booking.
     */
    public function store(BookingRequest $request): JsonResponse
    {
        $userId = $request->user('sanctum')?->id;

        $booking = $this->bookingService->createBooking(
            $request->validated(),
            $userId
        );

        $guestPaymentToken = $this->bookingService->issueGuestPaymentToken($booking);
        if ($guestPaymentToken) {
            $booking->setAttribute('payment_token', $guestPaymentToken);
        }

        return response()->json($booking, 201);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = $this->bookingService->getBookingForShow($id);

        Gate::forUser($request->user('sanctum'))->authorize('view', $booking);

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
        $this->bookingService->cancelBooking($booking);

        return response()->json(['message' => 'Бронювання скасовано.']);
    }

    /**
     * Finish the booking.
     */
    public function bookingFinish(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('update', $booking);

        return response()->json(['message' => $this->bookingService->finishBooking($booking)]);
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
        $bookings = $this->bookingService->getMyBookings(
            $request->user(),
            $request->query('type'),
            (int) $request->query('per_page', 10)
        );

        return response()->json($bookings);
    }

    /**
     * Hold a specific time slot temporarily.
     */
    public function holdSlot(HoldSlotRequest $request): JsonResponse
    {
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
    public function releaseSlot(ReleaseSlotRequest $request): JsonResponse
    {
        $this->bookingService->releaseHoldToken(
            $request->room_id,
            Carbon::parse($request->start_time),
            $request->hold_token
        );

        return response()->json(['message' => 'Резерв знято.']);
    }

    /**
     * Download the ticket as PDF.
     */
    public function downloadTicket(Booking $booking)
    {
        if (!request()->hasValidSignature() && Gate::forUser(request()->user('sanctum'))->denies('view', $booking)) {
            abort(403, 'Unauthorized action.');
        }

        return $this->ticketService->downloadBookingTicket($booking);
    }

    /**
     * Update the admin note for a booking.
     */
    public function updateNote(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $this->bookingService->updateAdminNote($booking, $request->admin_note);

        return response()->json(['message' => 'Note updated']);
    }
}