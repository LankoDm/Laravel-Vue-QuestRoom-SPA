<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for fetching room data from the API.
 */
class RoomApiTest extends TestCase
{
    // Resets the in-memory database state after each test method is executed
    use RefreshDatabase;

    /**
     * Test that the API returns only active rooms in the collection.
     */
    public function test_can_fetch_list_of_active_rooms(): void
    {
        // Create 2 active rooms and 1 inactive room in the database
        Room::factory()->count(2)->create(['is_active' => true]);
        Room::factory()->create(['is_active' => false]);

        // Send a GET request to fetch rooms
        $response = $this->getJson('/api/rooms');

        // Verify the response status and that only the 2 active rooms are returned
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test that a single room can be fetched by its slug.
     */
    public function test_can_fetch_single_room(): void
    {
        // Create a single active room with a specific name and slug
        $room = Room::factory()->create([
            'is_active' => true,
            'name' => 'Тестова Кімната',
            'slug' => 'testova-kimnata'
        ]);

        // Send a GET request to fetch the specific room by slug
        $response = $this->getJson('/api/rooms/' . $room->slug);

        // Verify the response status and the expected data structure
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Тестова Кімната')
            ->assertJsonPath('data.slug', 'testova-kimnata');
    }

    /**
     * Inactive room details should not be exposed to guests.
     */
    public function test_guest_cannot_fetch_inactive_room(): void
    {
        $room = Room::factory()->create([
            'is_active' => false,
            'slug' => 'inactive-room',
        ]);

        $response = $this->getJson('/api/rooms/' . $room->slug);

        $response->assertStatus(404);
    }

    /**
     * Only manager/admin can request show_all and see inactive rooms.
     */
    public function test_show_all_is_staff_only(): void
    {
        Room::factory()->count(2)->create(['is_active' => true]);
        Room::factory()->create(['is_active' => false]);

        $guestResponse = $this->getJson('/api/rooms?show_all=1');
        $guestResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $manager = User::factory()->create(['role' => 'manager']);
        Sanctum::actingAs($manager);

        $staffResponse = $this->getJson('/api/rooms?show_all=1');
        $staffResponse->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
