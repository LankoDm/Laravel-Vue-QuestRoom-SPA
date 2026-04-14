<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    /**
     * Get paginated and filtered rooms.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getFilteredRooms(Request $request): LengthAwarePaginator
    {
        $query = Room::withAvg(['reviews' => function ($q) {
            $q->where('is_approved', true);
        }], 'rating')
            ->withCount(['reviews' => function ($q) {
                $q->where('is_approved', true);
            }]);

        if (!$request->has('show_all')) {
            $query->where('is_active', 1);
        }

        if ($request->filled('difficulty')) {
            $query->whereIn('difficulty', (array) $request->difficulty);
        }

        if ($request->filled('players_count')) {
            $query->where('min_players', '<=', $request->players_count)
                ->where('max_players', '>=', $request->players_count);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('age')) {
            $ages = (array) $request->age;
            $query->where(function ($q) use ($ages) {
                foreach ($ages as $ageStr) {
                    $minAge = (int) str_replace('+', '', $ageStr);
                    $q->orWhereRaw("CAST(REPLACE(age, '+', '') AS UNSIGNED) >= ?", [$minAge]);
                }
            });
        }

        if ($request->filled('genres')) {
            $query->whereIn('genre', $request->genres);
        }

        if ($request->filled('sort')) {
            $this->applySorting($query, $request->sort);
        } else {
            $query->latest();
        }

        return $query->paginate(6);
    }

    /**
     * Apply sorting to the query builder.
     */
    private function applySorting($query, string $sortType): void
    {
        switch ($sortType) {
            case 'rating_desc':
                $query->orderBy('reviews_avg_rating', 'desc');
                break;
            case 'rating_asc':
                $query->orderBy('reviews_avg_rating', 'asc');
                break;
            case 'difficulty_asc':
                $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard', 'ultra hard') ASC");
                break;
            case 'difficulty_desc':
                $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard', 'ultra hard') DESC");
                break;
        }
    }

    /**
     * Handle the upload of room images and return their URLs.
     *
     * @param array $files
     * @return string JSON encoded array of URLs
     */
    public function uploadImages(array $files): string
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store('rooms', 'public');
        }

        return json_encode($paths);
    }

    /**
     * Delete old images from storage.
     * * @param string|null $imagePathJson
     * @return void
     */
    public function deleteOldImages(?string $imagePathJson): void
    {
        if (!$imagePathJson) return;

        $oldPaths = json_decode($imagePathJson, true);
        if (is_array($oldPaths)) {
            foreach ($oldPaths as $path) {
                if (str_starts_with($path, 'http')) {
                    $path = str_replace(url('storage') . '/', '', $path);
                }

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    /**
     * Get room by ID or Slug with safe relations loaded.
     */
    public function getRoomByIdentifier(string $identifier): Room
    {
        return Room::withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')
            ->withCount(['reviews' => function ($query) {
                $query->where('is_approved', true);
            }])
            ->with(['bookings' => function ($query) {
                $query->select('id', 'room_id', 'start_time', 'end_time', 'status')
                    ->whereIn('status', ['pending', 'confirmed', 'finished'])
                    ->where('end_time', '>', now());
            }])
            ->where('slug', $identifier)
            ->orWhere('id', $identifier)
            ->firstOrFail();
    }
}