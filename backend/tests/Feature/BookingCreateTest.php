<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for the booking creation process.
 */
class BookingCreateTest extends TestCase
{
    // Resets the in-memory database state after each test method is executed
    use RefreshDatabase;

    /**
     * Test that an authenticated user can successfully finalize a booking with a valid hold token.
     */
    public function test_user_can_create_booking_successfully(): void
    {
        // Create an authenticated user and an active room
        $user = User::factory()->create();
        
        $room = Room::factory()->create([
            'is_active' => true,
            'min_players' => 2,
            'max_players' => 5,
            'weekday_price' => 100000,
            'weekend_price' => 120000,
            'duration_minutes' => 60
        ]);

        $startTimeStr = '2026-05-13 14:00:00';
        $timestamp = Carbon::parse($startTimeStr)->timestamp;
        $holdToken = 'test_hold_token';
        
        // Simulate a hold token stored in the cache by the Hold API endpoint
        Cache::put("hold_room_{$room->id}_time_{$timestamp}", $holdToken, 10 * 60);

        // Define the booking payload as submitted from the frontend form
        $payload = [
            'room_id' => $room->id,
            'start_time' => $startTimeStr,
            'players_count' => 3,
            'guest_name' => 'Іван Клієнт',
            'guest_phone' => '+380 (99) 123-45-67',
            'guest_email' => 'client@example.com',
            'payment_method' => 'cash',
            'total_price' => 110000,
            'hold_token' => $holdToken,
        ];

        // Send a POST request as the authenticated user to create the booking
        $response = $this->actingAs($user)->postJson('/api/bookings', $payload);

        // Verify the response status and that the booking is in pending statate
        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');
        
        // Verify the booking was successfully stored in the database
        $this->assertDatabaseHas('bookings', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        // Verify the hold token was removed from the cache after successful booking
        $this->assertNull(Cache::get("hold_room_{$room->id}_time_{$timestamp}"));
    }

    /**
     * Test that booking fails (409 Conflict) when the time slot is held by another hold token.
     */
    public function test_cannot_book_when_slot_held_by_someone_else(): void
    {
        // Create an active room
        $room = Room::factory()->create([
            'is_active' => true,
            'min_players' => 2,
            'max_players' => 5,
            'weekday_price' => 100000,
            'weekend_price' => 120000
        ]);

        $startTimeStr = '2026-05-13 14:00:00';
        $timestamp = Carbon::parse($startTimeStr)->timestamp;

        // Simulate another user already holding this exact time slot via the cache
        Cache::put("hold_room_{$room->id}_time_{$timestamp}", 'someone_elses_token', 10 * 60);

        // Construct the payload with a mismatched/invalid hold token
        $payload = [
            'room_id' => $room->id,
            'start_time' => $startTimeStr,
            'players_count' => 2,
            'guest_name' => 'Гість',
            'guest_phone' => '+380 (99) 123-45-67',
            'payment_method' => 'cash',
            'total_price' => 100000,
            'hold_token' => 'my_invalid_token'
        ];

        // Send the POST request to create the booking
        $response = $this->postJson('/api/bookings', $payload);

        // Verify the response is rejected with a correct status
        $response->assertStatus(409) 
                 ->assertJson(['message' => 'На жаль, хтось інший вже почав оформлювати цей час.']);
        
        // Ensure no new booking was recorded in the database
        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * Test that booking fails when hold token was not created beforehand.
     */
    public function test_cannot_book_without_existing_hold_token(): void
    {
        $room = Room::factory()->create([
            'is_active' => true,
            'min_players' => 2,
            'max_players' => 5,
            'weekday_price' => 100000,
            'weekend_price' => 120000,
        ]);

        $payload = [
            'room_id' => $room->id,
            'start_time' => '2026-05-16 14:00:00',
            'players_count' => 2,
            'guest_name' => 'Гість',
            'guest_phone' => '+380 (99) 123-45-67',
            'payment_method' => 'cash',
            'total_price' => 100000,
            'hold_token' => 'non_existing_hold_token',
        ];

        $response = $this->postJson('/api/bookings', $payload);

        $response->assertStatus(409)
            ->assertJson(['message' => 'Сесія резерву часу завершилась. Будь ласка, оберіть час ще раз.']);

        $this->assertDatabaseCount('bookings', 0);
    }
}
