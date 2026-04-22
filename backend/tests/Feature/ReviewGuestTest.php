<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReviewGuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_review_via_signed_link_once(): void
    {
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'guest_name' => 'Гість Тестер',
            'status' => 'finished',
        ]);

        $signedUrl = URL::signedRoute('guest.review.store', ['booking' => $booking->id], now()->addHour());

        $response = $this->postJson($signedUrl, [
            'message' => 'Все було супер, дуже сподобалося!',
            'rating' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('review.booking_id', $booking->id)
            ->assertJsonPath('review.room_id', $room->id)
            ->assertJsonPath('review.message', 'Все було супер, дуже сподобалося!');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'guest_name' => 'Гість Тестер',
        ]);
    }

    public function test_guest_cannot_submit_review_twice_for_same_booking(): void
    {
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'guest_name' => 'Гість Тестер',
            'status' => 'finished',
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'user_id' => null,
            'guest_name' => 'Гість Тестер',
            'message' => 'Перший відгук',
            'rating' => 5,
            'is_approved' => false,
        ]);

        $signedUrl = URL::signedRoute('guest.review.store', ['booking' => $booking->id], now()->addHour());

        $response = $this->postJson($signedUrl, [
            'message' => 'Другий відгук',
            'rating' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Ви вже залишили свій відгук. Дякуємо!']);
    }
}
