<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Review;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get comprehensive statistics for the admin dashboard.
     * Includes aggregated data for revenue, rooms, bookings, and reviews.
     *
     * @return array
     */
    public function getStats(): array
    {
        $now = Carbon::now();
        $today = clone $now->startOfDay();

        $totalRooms = Room::count();
        $bookingsToday = Booking::whereDate('start_time', $today)->count();
        $newReviews = Review::where('is_approved', false)->count();
        $totalReviews = Review::count();

        $totalRevenue = Booking::whereIn('status', ['confirmed', 'finished'])->sum('total_price') / 100;

        $revenueWeek = Booking::whereIn('status', ['confirmed', 'finished'])
                ->where('created_at', '>=', clone $now->subDays(7))
                ->sum('total_price') / 100;

        $revenueMonth = Booking::whereIn('status', ['confirmed', 'finished'])
                ->where('created_at', '>=', clone $now->subDays(30))
                ->sum('total_price') / 100;

        // Generate revenue chart data for the last 7 days
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Booking::whereIn('status', ['confirmed', 'finished'])
                    ->whereDate('created_at', $date)
                    ->sum('total_price') / 100;

            $last7Days->push([
                'date' => $date->format('d.m'),
                'revenue' => $revenue
            ]);
        }

        // Fetch rooms with aggregated bookings and reviews data
        $rooms = Room::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['confirmed', 'finished']);
        }])->withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')->get();

        // Generate data for the popularity chart with timeframes
        $roomsPopularity = [
            'all' => $rooms->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count]),
            '30' => Room::withCount(['bookings' => fn($q) => $q->where('created_at', '>=', clone $now->subDays(30))])
                ->get()->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count]),
            '7' => Room::withCount(['bookings' => fn($q) => $q->where('created_at', '>=', clone $now->subDays(7))])
                ->get()->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count]),
        ];

        return [
            'total_rooms' => $totalRooms,
            'bookings_today' => $bookingsToday,
            'new_reviews' => $newReviews,
            'total_reviews' => $totalReviews,
            'total_revenue' => $totalRevenue,
            'revenue_week' => $revenueWeek,
            'revenue_month' => $revenueMonth,
            'revenue_chart' => $last7Days,
            'rooms_chart' => $roomsPopularity,
            'insights' => [
                'most_booked' => $rooms->sortByDesc('bookings_count')->first()?->only(['name', 'bookings_count']),
                'least_booked' => $rooms->sortBy('bookings_count')->first()?->only(['name', 'bookings_count']),
                'best_rated' => $rooms->whereNotNull('reviews_avg_rating')->sortByDesc('reviews_avg_rating')->first()?->only(['name', 'reviews_avg_rating']),
                'worst_rated' => $rooms->whereNotNull('reviews_avg_rating')->sortBy('reviews_avg_rating')->first()?->only(['name', 'reviews_avg_rating']),
            ]
        ];
    }

    /**
     * Retrieve aggregated data required for generating the PDF report.
     *
     * @return array
     */
    public function getPdfReportData(): array
    {
        $now = Carbon::now();

        $rooms = Room::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['confirmed', 'finished']);
        }])->withAvg(['reviews' => function ($query) {
            $query->where('is_approved', true);
        }], 'rating')->get();

        return [
            'date' => $now->format('d.m.Y H:i'),
            'totalRevenue' => Booking::whereIn('status', ['confirmed', 'finished'])->sum('total_price') / 100,
            'revenueWeek' => Booking::whereIn('status', ['confirmed', 'finished'])
                    ->where('created_at', '>=', clone $now->subDays(7))->sum('total_price') / 100,
            'revenueMonth' => Booking::whereIn('status', ['confirmed', 'finished'])
                    ->where('created_at', '>=', clone $now->subDays(30))->sum('total_price') / 100,
            'totalBookings' => Booking::whereIn('status', ['confirmed', 'finished'])->count(),
            'mostBooked' => $rooms->sortByDesc('bookings_count')->first(),
            'leastBooked' => $rooms->sortBy('bookings_count')->first(),
            'bestRated' => $rooms->whereNotNull('reviews_avg_rating')->sortByDesc('reviews_avg_rating')->first(),
            'worstRated' => $rooms->whereNotNull('reviews_avg_rating')->sortBy('reviews_avg_rating')->first(),
            'rooms' => $rooms->sortByDesc('bookings_count')
        ];
    }
}