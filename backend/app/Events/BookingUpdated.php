<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Booking $booking)
    {
        //
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
        return 'booking.updated';
    }

    /**
     * Get the data to broadcast.
     * Prevents PII leaking to public WebSocket clients.
     */
    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'room_id' => $this->booking->room_id,
                'start_time' => $this->booking->start_time,
                'end_time' => $this->booking->end_time,
                'status' => $this->booking->status,
                'guest_name' => $this->booking->guest_name,
                'guest_phone' => $this->booking->guest_phone,
                'user' => $this->booking->user ? ['name' => $this->booking->user->name, 'phone' => $this->booking->user->phone] : null,
                'room' => $this->booking->room,
                'total_price' => $this->booking->total_price,
                'players_count' => $this->booking->players_count,
                'payment_method' => $this->booking->payment_method,
                'payment_status' => $this->booking->payment_status,
            ]
        ];
    }
}
