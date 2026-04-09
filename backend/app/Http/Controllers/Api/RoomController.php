<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    protected RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Display a paginated and filtered list of rooms.
     */
    public function index(Request $request): JsonResponse
    {
        $rooms = $this->roomService->getFilteredRooms($request);

        return response()->json($rooms);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(RoomRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        if ($request->hasFile('image_path')) {
            $validatedData['image_path'] = $this->roomService->uploadImages($request->file('image_path'));
        }

        $room = Room::create($validatedData);

        return response()->json($room, 201);
    }

    /**
     * Display the specified room by Slug or ID.
     */
    public function show(string $identifier): JsonResponse
    {
        $room = Room::withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')
            ->withCount(['reviews' => function ($query) {
                $query->where('is_approved', true);
            }])
            ->with(['bookings' => function ($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'finished'])
                    ->where('end_time', '>', now());
            }])
            ->where('slug', $identifier)
            ->orWhere('id', $identifier)
            ->firstOrFail();

        return response()->json($room);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(RoomRequest $request, string $id): JsonResponse
    {
        $validatedData = $request->validated();
        $room = Room::findOrFail($id);

        if ($request->hasFile('image_path')) {
            // Delete old images before saving new ones to save disk space
            $this->roomService->deleteOldImages($room->image_path);
            $validatedData['image_path'] = $this->roomService->uploadImages($request->file('image_path'));
        } else {
            unset($validatedData['image_path']); // Keep existing images
        }

        $room->update($validatedData);

        return response()->json($room, 200);
    }

    /**
     * Toggle the active status of the room.
     */
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['is_active' => 'required|boolean']);

        $room = Room::findOrFail($id);
        $room->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'Статус оновлено',
            'is_active' => $room->is_active
        ]);
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // Clean up the disk
        $this->roomService->deleteOldImages($room->image_path);

        $room->delete();

        return response()->json(["message" => "Кімнату успішно видалено."]);
    }
}