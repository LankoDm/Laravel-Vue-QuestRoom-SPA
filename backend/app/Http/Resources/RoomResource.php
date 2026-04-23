<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $routeUri = $request->route()?->uri();
        $isRoomsListRequest = $routeUri === 'api/rooms';

        $firstImageUrl = $this->resolveFirstImageUrl($images);
        $imageUrls = $isRoomsListRequest
            ? array_values(array_filter([$firstImageUrl]))
            : $this->resolveAllImageUrls($images);

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

    /**
     * Resolve the first valid image URL from room image payload.
     */
    private function resolveFirstImageUrl(mixed $images): ?string
    {
        if (!is_array($images)) {
            return null;
        }

        foreach ($images as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            return $this->resolveImageUrl($path);
        }

        return null;
    }

    /**
     * Resolve all room image URLs.
     *
     * @return array<int, string>
     */
    private function resolveAllImageUrls(mixed $images): array
    {
        if (!is_array($images)) {
            return [];
        }

        $urls = [];
        foreach ($images as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $urls[] = $this->resolveImageUrl($path);
        }

        return $urls;
    }

    /**
     * Resolve single image path to a browser URL.
     */
    private function resolveImageUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($path, 'questroom/')) {
            return Storage::disk('cloudinary')->url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}