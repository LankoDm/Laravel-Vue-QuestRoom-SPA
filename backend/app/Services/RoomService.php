<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    /**
     * Resolve room by numeric id or slug.
     */
    public function findRoomByIdentifier(string $identifier): Room
    {
        $query = Room::query()->where('slug', $identifier);

        if (is_numeric($identifier)) {
            $query->orWhere('id', (int) $identifier);
        }

        return $query
            ->orderByRaw('slug = ? DESC', [$identifier])
            ->firstOrFail();
    }

    /**
     * Get paginated and filtered rooms.
     */
    public function getFilteredRooms(Request $request): LengthAwarePaginator
    {
        $query = Room::withAvg(['reviews' => function ($q) {
            $q->where('is_approved', true);
        }], 'rating')
            ->withCount(['reviews' => function ($q) {
                $q->where('is_approved', true);
            }]);

        if (!$request->boolean('show_all') || !$this->canViewInactiveRooms($request)) {
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
     * Staff-only access to inactive rooms and show_all filter.
     */
    public function canViewInactiveRooms(Request $request): bool
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return false;
        }

        return $user->isManager() || $user->isAdmin();
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
     */
    public function uploadImages(array $files): string
    {
        $paths = [];
        foreach ($files as $file) {
            $path = $file->store('questroom/rooms', 'cloudinary');
            $paths[] = $path;
        }

        return json_encode($paths);
    }

    /**
     * Delete old images from storage.
     */
    public function deleteOldImages(?string $imagePathJson): void
    {
        if (!$imagePathJson) return;

        $oldPaths = json_decode($imagePathJson, true);
        if (is_array($oldPaths)) {
            foreach ($oldPaths as $path) {
                if (!is_string($path) || $path === '') {
                    continue;
                }

                $this->deletePathFromDisks($path);
            }
        }
    }

    /**
     * Best-effort deletion across local and cloud storage path formats.
     */
    private function deletePathFromDisks(string $path): void
    {
        $candidatePublicPath = $path;

        if (str_starts_with($path, 'http')) {
            $candidatePublicPath = str_replace(url('storage') . '/', '', $path);
        }

        if (Storage::disk('public')->exists($candidatePublicPath)) {
            Storage::disk('public')->delete($candidatePublicPath);
        }

        $cloudinaryId = $this->extractCloudinaryPublicId($path);
        if ($cloudinaryId !== null) {
            try {
                Storage::disk('cloudinary')->delete($cloudinaryId);
            } catch (\Throwable $e) {
                Log::warning('Failed to delete image from cloudinary.', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Extract cloudinary public id from either stored path or full URL.
     */
    private function extractCloudinaryPublicId(string $path): ?string
    {
        if (!str_starts_with($path, 'http')) {
            return $path;
        }

        if (!str_contains($path, '/upload/')) {
            return null;
        }

        $parts = explode('/upload/', $path, 2);
        if (count($parts) !== 2 || $parts[1] === '') {
            return null;
        }

        $publicPath = preg_replace('#^v\d+/#', '', $parts[1]);
        if (!is_string($publicPath) || $publicPath === '') {
            return null;
        }

        return preg_replace('/\.[a-zA-Z0-9]+$/', '', $publicPath);
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
            ->where(function ($query) use ($identifier) {
                $query->where('slug', $identifier);
                if (is_numeric($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->orderByRaw('slug = ? DESC', [$identifier])
            ->firstOrFail();
    }
}