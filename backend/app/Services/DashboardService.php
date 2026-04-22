<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    private const SUCCESS_BOOKING_STATUSES = ['confirmed', 'finished'];

    /**
     * Get comprehensive statistics for the admin dashboard.
     * Includes aggregated data for revenue, rooms, bookings, and reviews.
     *
     * @return array
     */
    public function getStats(): array
    {
        $today = Carbon::today();
        $revenue = $this->getRevenueAggregates();
        $revenueChart = $this->getRevenueChartData();
        $rooms = $this->getRoomsMetrics();

        $totalRooms = Room::count();
        $bookingsToday = Booking::whereDate('start_time', $today)->count();
        $newReviews = Review::where('is_approved', false)->count();
        $totalReviews = Review::count();

        // Generate data for the popularity chart with timeframes
        $roomsPopularity = [
            'all' => $rooms->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count]),
            '30' => $rooms->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count_30]),
            '7' => $rooms->map(fn($r) => ['name' => $r->name, 'count' => $r->bookings_count_7]),
        ];

        return [
            'total_rooms' => $totalRooms,
            'bookings_today' => $bookingsToday,
            'new_reviews' => $newReviews,
            'total_reviews' => $totalReviews,
            'total_revenue' => $revenue['total'],
            'revenue_week' => $revenue['week'],
            'revenue_month' => $revenue['month'],
            'revenue_chart' => $revenueChart,
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
        $revenue = $this->getRevenueAggregates();
        $rooms = $this->getRoomsMetrics();

        return [
            'date' => $now->format('d.m.Y H:i'),
            'totalRevenue' => $revenue['total'],
            'revenueWeek' => $revenue['week'],
            'revenueMonth' => $revenue['month'],
            'totalBookings' => $revenue['count'],
            'mostBooked' => $rooms->sortByDesc('bookings_count')->first(),
            'leastBooked' => $rooms->sortBy('bookings_count')->first(),
            'bestRated' => $rooms->whereNotNull('reviews_avg_rating')->sortByDesc('reviews_avg_rating')->first(),
            'worstRated' => $rooms->whereNotNull('reviews_avg_rating')->sortBy('reviews_avg_rating')->first(),
            'rooms' => $rooms->sortByDesc('bookings_count')
        ];
    }

    private function getRevenueAggregates(): array
    {
        return [
            'total' => Booking::whereIn('status', self::SUCCESS_BOOKING_STATUSES)->sum('total_price') / 100,
            'week' => Booking::whereIn('status', self::SUCCESS_BOOKING_STATUSES)
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('total_price') / 100,
            'month' => Booking::whereIn('status', self::SUCCESS_BOOKING_STATUSES)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('total_price') / 100,
            'count' => Booking::whereIn('status', self::SUCCESS_BOOKING_STATUSES)->count(),
        ];
    }

    private function getRevenueChartData(): Collection
    {
        $from = Carbon::today()->subDays(6)->startOfDay();

        $dailyRevenue = Booking::query()
            ->selectRaw('DATE(created_at) as day, SUM(total_price) as total')
            ->whereIn('status', self::SUCCESS_BOOKING_STATUSES)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->pluck('total', 'day');

        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayKey = $date->toDateString();

            $last7Days->push([
                'date' => $date->format('d.m'),
                'revenue' => ((int) ($dailyRevenue[$dayKey] ?? 0)) / 100,
            ]);
        }

        return $last7Days;
    }

    private function getRoomsMetrics(): Collection
    {
        return Room::query()
            ->withCount([
                'bookings' => fn($query) => $query->whereIn('status', self::SUCCESS_BOOKING_STATUSES),
                'bookings as bookings_count_30' => fn($query) => $query
                    ->whereIn('status', self::SUCCESS_BOOKING_STATUSES)
                    ->where('created_at', '>=', now()->subDays(30)),
                'bookings as bookings_count_7' => fn($query) => $query
                    ->whereIn('status', self::SUCCESS_BOOKING_STATUSES)
                    ->where('created_at', '>=', now()->subDays(7)),
            ])
            ->withAvg([
                'reviews' => fn($query) => $query->where('is_approved', true),
            ], 'rating')
            ->get();
    }
}