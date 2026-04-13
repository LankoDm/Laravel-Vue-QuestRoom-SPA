<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = Carbon::instance($this->faker->dateTimeBetween('now', '+1 month'))->setMinute(0)->setSecond(0);

        return [
            'room_id' => Room::factory(),
            'user_id' => null,
            'start_time' => $startTime,
            'end_time' => (clone $startTime)->addMinutes(60),
            'total_price' => 80000,
            'status' => 'pending',
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->unique()->safeEmail(),
            'guest_phone' => '+380' . $this->faker->numerify('#########'),
            'players_count' => 2,
        ];
    }
}