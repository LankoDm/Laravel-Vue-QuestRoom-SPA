<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        return response()->json(Booking::all());
    }

    public function store(BookingRequest $request)
    {
        $room = Room::findOrFail($request->room_id);
        if($request->players_count < $room->min_players || $request->players_count > $room->max_players) {
            return response()->json([
                'message' => "Кількість гравців має бути від {$room->min_players} до {$room->max_players}"
            ], 422);
        }
        $startTime = Carbon::parse($request->start_time);
        $endTime = $startTime->copy()->addMinutes($room->duration_minutes);
        $isConflict = Booking::where('room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
        if($isConflict) {
            return response()->json([
               'message' =>  'На жаль, цей час вже заброньовано іншими гравцями.'
            ], 422);
        }
        if($startTime->isWeekend()){
            $price = $room->weekend_price;
        }else{
            $price = $room->weekday_price;
        }
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'players_count' => $request->players_count,
            'total_price' => $price,
            'status' => 'pending',
        ]);
        return response()->json($booking, 201);
    }

    public function show(string $id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json($booking);
    }

    public function update(BookingRequest $request, string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update([$request->validated()->only('status', 'admin_note')]);
        return response()->json($booking);
    }

    public function destroy(string $id)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Бронювання скасовано.']);
    }
}
