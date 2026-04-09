<?php

namespace App\Services;

use App\Models\Review;
use App\Events\ReviewCreated;
use Illuminate\Database\Eloquent\Collection;

class ReviewService
{
    /**
     * Get all approved reviews for a specific room.
     *
     * @param string $roomId
     * @return Collection
     */
    public function getApprovedByRoom(string $roomId): Collection
    {
        return Review::with('user:id,name')
            ->where('room_id', $roomId)
            ->where('is_approved', true)
            ->get();
    }

    /**
     * Get all reviews (approved and pending) for the manager dashboard.
     *
     * @return Collection
     */
    public function getAllForManagement(): Collection
    {
        return Review::with(['user:id,name', 'room:id,name'])
            ->orderBy('is_approved', 'asc')
            ->latest()
            ->get();
    }

    /**
     * Create a new review if the user hasn't reviewed this room yet.
     *
     * @param array $data
     * @param object $user
     * @return Review
     */
    public function createReview(array $data, object $user): Review
    {
        $alreadyReviewed = Review::where('user_id', $user->id)
            ->where('room_id', $data['room_id'])
            ->exists();

        if ($alreadyReviewed) {
            abort(422, 'Ви вже залишали відгук на цю кімнату.');
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
     * Approve a pending review.
     *
     * @param string $id
     * @return Review
     */
    public function approveReview(string $id): Review
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);

        return $review;
    }

    /**
     * Delete a review permanently.
     *
     * @param string $id
     * @return void
     */
    public function deleteReview(string $id): void
    {
        Review::findOrFail($id)->delete();
    }
}