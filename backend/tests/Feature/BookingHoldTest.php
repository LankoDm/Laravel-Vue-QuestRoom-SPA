<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Feature tests for the booking hold functionality.
 */
class BookingHoldTest extends TestCase
{
    // Resets the in-memory database state after each test method is executed
    use RefreshDatabase;

    /**
     * Test that a user can successfully place a hold on an available room slot.
     * The hold token should be temporarily stored in the Cache.
     */
    public function test_user_can_hold_available_slot(): void
    {
        // Create an active room
        $room = Room::factory()->create([
            'is_active' => true,
        ]);

        $startTimeStr = '2026-05-15 14:00:00';
        $timestamp = Carbon::parse($startTimeStr)->timestamp;

        $payload = [
            'room_id' => $room->id,
            'start_time' => $startTimeStr,
            'hold_token' => 'random_token_123'
        ];

        // Send a POST request to hold the slot
        $response = $this->postJson('/api/bookings/hold', $payload);

        // Verify the response status and the exact message returned by the controller
        $response->assertStatus(200)
            ->assertJson(['message' => 'Час успішно зарезервовано на 10 хвилин.']);

        // Verify that the hold token is correctly stored in the Application Cache
        $cacheKey = "hold_room_{$room->id}_time_{$timestamp}";
        $this->assertEquals('random_token_123', Cache::get($cacheKey));
    }

    /**
     * Test that the system prevents holding a slot that overlaps with a confirmed booking.
     */
    public function test_user_cannot_hold_confirmed_slot(): void
    {
        // Create a room and a confirmed booking occupying the target time slot
        $room = Room::factory()->create();

        Booking::factory()->create([
            'room_id' => $room->id,
            'start_time' => '2026-05-15 14:00:00',
            'end_time' => '2026-05-15 15:00:00',
            'status' => 'confirmed'
        ]);

        $payload = [
            'room_id' => $room->id,
            'start_time' => '2026-05-15 14:00:00',
            'hold_token' => 'new_token_456'
        ];

        // Attempt to hold the slot that is already booked in the database
        $response = $this->postJson('/api/bookings/hold', $payload);

        // Verify the request is rejected with a correct status
        $response->assertStatus(409)
            ->assertJson(['message' => 'На жаль, цей час вже заброньовано іншими гравцями.']);
    }
}