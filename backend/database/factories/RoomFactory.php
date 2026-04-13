<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'min_players' => 2,
            'max_players' => 4,
            'duration_minutes' => 60,
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'weekday_price' => 80000,
            'weekend_price' => 100000,
            'image_path' => json_encode(['default.jpg']),
            'is_active' => true,
            'age' => '12+',
            'hint_phrase' => 'Look under the rug',
        ];
    }
}