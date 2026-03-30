<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $totalRooms = Room::count();
        $bookingsToday = Booking::whereDate('start_time', Carbon::today())->count();
        $newReviews = Review::where('is_approved', 0)->count();
        $totalReviews = Review::count();
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
        $rooms = Room::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['confirmed', 'finished']);
        }])->withAvg(['reviews' => function($query) {
            $query->where('is_approved', true);
        }], 'rating')->get();
        $roomsPopularity = Room::withCount('bookings')
            ->get()
            ->map(function ($room) {
                return [
                    'name' => $room->name,
                    'count' => $room->bookings_count
                ];
            });
        $mostBooked = $rooms->sortByDesc('bookings_count')->first();
        $leastBooked = $rooms->sortBy('bookings_count')->first();
        $bestRated = $rooms->whereNotNull('reviews_avg_rating')->sortByDesc('reviews_avg_rating')->first();
        $worstRated = $rooms->whereNotNull('reviews_avg_rating')->sortBy('reviews_avg_rating')->first();
        return response()->json([
            'total_rooms' => $totalRooms,
            'bookings_today' => $bookingsToday,
            'new_reviews' => $newReviews,
            'total_reviews' => $totalReviews,
            'total_revenue' => $totalRevenue,
            'revenue_chart' => $last7Days,
            'rooms_chart' => $roomsPopularity,
            'insights' => [
                'most_booked' => $mostBooked ? ['name' => $mostBooked->name, 'count' => $mostBooked->bookings_count] : null,
                'least_booked' => $leastBooked ? ['name' => $leastBooked->name, 'count' => $leastBooked->bookings_count] : null,
                'best_rated' => $bestRated ? ['name' => $bestRated->name, 'rating' => round($bestRated->reviews_avg_rating, 1)] : null,
                'worst_rated' => $worstRated ? ['name' => $worstRated->name, 'rating' => round($worstRated->reviews_avg_rating, 1)] : null,
            ]
        ]);
    }
    public function downloadPdfReport(Request $request)
    {
        $rooms = Room::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['confirmed', 'finished']);
        }])->withAvg(['reviews' => function($query) {
            $query->where('is_approved', true);
        }], 'rating')->get();
        $data = [
            'date' => Carbon::now()->format('d.m.Y H:i'),
            'totalRevenue' => Booking::whereIn('status', ['confirmed', 'finished'])->sum('total_price') / 100,
            'totalBookings' => Booking::whereIn('status', ['confirmed', 'finished'])->count(),
            'mostBooked' => $rooms->sortByDesc('bookings_count')->first(),
            'leastBooked' => $rooms->sortBy('bookings_count')->first(),
            'bestRated' => $rooms->whereNotNull('reviews_avg_rating')->sortByDesc('reviews_avg_rating')->first(),
            'worstRated' => $rooms->whereNotNull('reviews_avg_rating')->sortBy('reviews_avg_rating')->first(),
            'rooms' => $rooms->sortByDesc('bookings_count')
        ];
        $pdf = Pdf::loadView('pdf.admin_report', $data);
        return $pdf->download('report.pdf');
    }
}
