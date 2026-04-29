<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Services\RoomService;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(Request $request): AnonymousResourceCollection
    {
        $rooms = $this->roomService->getFilteredRooms($request);

        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(RoomRequest $request): RoomResource
    {
        $validatedData = $request->validated();

        if ($request->hasFile('image_path')) {
            $validatedData['image_path'] = $this->roomService->uploadImages($request->file('image_path'));
        }

        $room = Room::create($validatedData);

        return new RoomResource($room);
    }

    /**
     * Display the specified room by Slug or ID.
     */
    public function show(Request $request, string $identifier): RoomResource
    {
        $room = $this->roomService->getRoomByIdentifier($identifier);

        if (!$room->is_active && !$this->roomService->canViewInactiveRooms($request)) {
            abort(404);
        }

        return new RoomResource($room);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(RoomRequest $request, string $roomIdentifier): RoomResource
    {
        $validatedData = $request->validated();
        $room = $this->roomService->findRoomByIdentifier($roomIdentifier);

        if ($request->hasFile('image_path')) {
            // Delete old images before saving new ones to save disk space
            $this->roomService->deleteOldImages($room->image_path);
            $validatedData['image_path'] = $this->roomService->uploadImages($request->file('image_path'));
        } else {
            unset($validatedData['image_path']); // Keep existing images
        }

        $room->update($validatedData);

        return new RoomResource($room);
    }

    /**
     * Toggle the active status of the room.
     */
    public function toggleStatus(Request $request, string $roomIdentifier): JsonResponse
    {
        $request->validate(['is_active' => 'required|boolean']);

        $room = $this->roomService->findRoomByIdentifier($roomIdentifier);
        $room->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'Статус оновлено',
            'is_active' => $room->is_active
        ]);
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(string $roomIdentifier): JsonResponse
    {
        $room = $this->roomService->findRoomByIdentifier($roomIdentifier);

        // Clean up the disk
        $this->roomService->deleteOldImages($room->image_path);

        $room->delete();

        return response()->json(["message" => "Кімнату успішно видалено."]);
    }
}