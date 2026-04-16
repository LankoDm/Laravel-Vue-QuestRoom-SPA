<?php

namespace App\Events;

use App\Models\Review;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Review $review)
    {
        $this->review->load('user');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('manager-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'review.created';
    }

    /**
     * Get the data to broadcast.
     * Prevents PII leaking to public WebSocket clients.
     */
    public function broadcastWith(): array
    {
        return [
            'review' => [
                'id' => $this->review->id,
                'room_id' => $this->review->room_id,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'is_approved' => $this->review->is_approved,
                'user' => [
                    'name' => $this->review->user->name ?? 'Гість',
                ],
                'guest_name' => $this->review->guest_name,
            ]
        ];
    }
}
