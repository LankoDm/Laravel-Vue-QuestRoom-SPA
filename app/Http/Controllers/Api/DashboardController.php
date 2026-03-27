<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $totalRooms = Room::count();
        $bookingsToday = Booking::whereDate('start_time', Carbon::today())->count();
        $newReviews = Review::where('is_approved', 0)->count();
        $totalRevenue = Booking::whereIn('status', ['confirmed', 'finished'])->sum('total_price') / 100;
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Booking::whereIn('status', ['confirmed', 'finished'])
                    ->whereDate('start_time', $date)
                    ->sum('total_price') / 100;

            $last7Days->push([
                'date' => $date->format('d.m'),
                'revenue' => $revenue
            ]);
        }
        $roomsPopularity = Room::withCount('bookings')
            ->get()
            ->map(function ($room) {
                return [
                    'name' => $room->name,
                    'count' => $room->bookings_count
                ];
            });
        return response()->json([
            'total_rooms' => $totalRooms,
            'bookings_today' => $bookingsToday,
            'new_reviews' => $newReviews,
            'total_revenue' => $totalRevenue,
            'revenue_chart' => $last7Days,
            'rooms_chart' => $roomsPopularity
        ]);
    }
}
