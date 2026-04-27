<?php

namespace App\Http\Resources;

use App\Services\ImagePathService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = is_string($this->image_path) ? json_decode($this->image_path, true) : $this->image_path;
        $isRoomsListRequest = $request->route('room') === null;
        $imagePathService = app(ImagePathService::class);

        $firstImageUrl = $imagePathService->resolveFirstUrl($images);
        $imageUrls = $isRoomsListRequest
            ? array_values(array_filter([$firstImageUrl]))
            : $imagePathService->resolveAllUrls($images);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'age' => $this->age,
            'hint_phrase' => $this->hint_phrase,
            'genre' => $this->genre,
            'min_players' => $this->min_players,
            'max_players' => $this->max_players,
            'weekday_price' => $this->weekday_price,
            'weekend_price' => $this->weekend_price,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => $this->is_active,
            'image_path' => $imageUrls,
            'first_image_url' => $firstImageUrl,

            'rating' => round($this->reviews_avg_rating ?? 0, 1),
            'reviews_count' => $this->reviews_count ?? 0,

            'bookings' => $this->whenLoaded('bookings', function () {
                return $this->bookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                    ];
                });
            }),
        ];
    }
}