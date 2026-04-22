<?php

namespace Tests\Unit;

use App\Events\ReviewCreated;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_created_payload_contains_message_field(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $review = Review::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'guest_name' => null,
            'message' => 'Текст відгуку',
            'rating' => 5,
            'is_approved' => false,
        ]);

        $event = new ReviewCreated($review);
        $payload = $event->broadcastWith();

        $this->assertSame('Текст відгуку', $payload['review']['message']);
        $this->assertArrayNotHasKey('comment', $payload['review']);
    }
}
