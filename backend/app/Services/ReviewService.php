<?php

namespace App\Services;

use App\Models\Review;
use App\Events\ReviewCreated;
use App\Exceptions\Review\ReviewAlreadyExistsException;
use App\Models\Booking;
use App\Models\User;
use \Illuminate\Http\Request;

class ReviewService
{
    public function __construct(private readonly RoomService $roomService)
    {
    }

    /**
     * Get all approved reviews for a specific room.
     */
    public function getApprovedByRoom(string $roomId, ?int $perPage = null)
    {
        $room = $this->roomService->findRoomByIdentifier($roomId);

        $query = Review::with('user:id,name')
            ->where('room_id', $room->id)
            ->where('is_approved', true)
            ->latest();

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Get all reviews (approved and pending) for the manager dashboard.
     */
    public function getAllForManagement(Request $request)
    {
        $query = Review::with(['user:id,name', 'room:id,name']);

        if ($request->has('status')) {
            $status = $request->query('status');
            if ($status === 'new') {
                $query->where('is_approved', false);
            } elseif ($status === 'published') {
                $query->where('is_approved', true);
            }
        }

        return $query->orderBy('is_approved', 'asc')
            ->latest()
            ->paginate($request->query('per_page', 9));
    }

    /**
     * Create a new review if the user hasn't reviewed this room yet.
     */
    public function createReview(array $data, User $user): Review
    {
        $alreadyReviewed = Review::where('user_id', $user->id)
            ->where('room_id', $data['room_id'])
            ->exists();

        if ($alreadyReviewed) {
            throw new ReviewAlreadyExistsException('Ви вже залишали відгук на цю кімнату.');
        }

        $review = Review::create([
            'user_id' => $user->id,
            'room_id' => $data['room_id'],
            'message' => $data['message'],
            'rating' => $data['rating'] ?? null,
        ]);

        ReviewCreated::dispatch($review);

        return $review;
    }

    /**
     * Create a new guest review based on a booking.
     */
    public function createGuestReview(array $data, Booking $booking): Review
    {
        $alreadyReviewed = Review::where('booking_id', $booking->id)->exists();

        if ($alreadyReviewed) {
            throw new ReviewAlreadyExistsException('Ви вже залишили свій відгук. Дякуємо!');
        }

        $reviewUserId = $booking->user_id;
        if ($reviewUserId) {
            $alreadyReviewedByUser = Review::where('user_id', $reviewUserId)
                ->where('room_id', $booking->room_id)
                ->exists();

            if ($alreadyReviewedByUser) {
                throw new ReviewAlreadyExistsException('Ви вже залишали відгук на цю кімнату.');
            }
        }

        $guestName = $reviewUserId ? null : $booking->guest_name;

        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => $reviewUserId,
            'guest_name' => $guestName,
            'room_id' => $booking->room_id,
            'message' => $data['message'],
            'rating' => $data['rating'] ?? null,
        ]);

        ReviewCreated::dispatch($review);

        return $review;
    }

    /**
     * Approve a pending review.
     */
    public function approveReview(string $id): Review
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);

        return $review;
    }

    /**
     * Delete a review permanently.
     */
    public function deleteReview(string $id): void
    {
        Review::findOrFail($id)->delete();
    }
}