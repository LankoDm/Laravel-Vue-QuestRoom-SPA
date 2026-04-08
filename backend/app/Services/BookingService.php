<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Events\BookingCreated;
use App\Jobs\FinishBookingJob;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingService
{
    /**
     * Create a new booking and dispatch events.
     *
     * @param array $data
     * @param int|null $userId
     * @return Booking
     */
    public function createBooking(array $data, ?int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $room = Room::findOrFail($data['room_id']);
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($room->duration_minutes);

            $this->validatePlayersCount($room, $data['players_count']);
            $this->checkActiveBookingsLimit($userId, $data['guest_phone'], $data['guest_email'] ?? null);
            $this->verifyHoldToken($room->id, $startTime, $data['hold_token']);
            $this->checkTimeConflict($room->id, $startTime, $endTime);

            $finalPrice = $this->calculatePrice($room, $startTime, $data['players_count']);

            $booking = Booking::create([
                'user_id' => $userId,
                'room_id' => $room->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'players_count' => $data['players_count'],
                'total_price' => $finalPrice,
                'status' => 'pending',
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'guest_email' => $data['guest_email'] ?? null,
                'comment' => $data['comment'] ?? null,
                'payment_method' => $data['payment_method'],
            ]);

            $this->releaseHoldToken($room->id, $startTime);

            $booking->load('room');
            BookingCreated::dispatch($booking);

            return $booking;
        });
    }

    /**
     * Confirm booking and dispatch the finish job.
     *
     * @param Booking $booking
     * @return void
     */
    public function confirmBooking(Booking $booking): void
    {
        if ($booking->status !== 'pending') {
            abort(400, 'Бронювання вже оброблено');
        }

        $booking->update(['status' => 'confirmed']);

        $finishTime = Carbon::parse($booking->end_time);
        FinishBookingJob::dispatch($booking->id)->delay($finishTime);
    }

    /**
     * Hold a specific time slot in cache.
     *
     * @param int $roomId
     * @param Carbon $startTime
     * @param string $token
     * @return void
     */
    public function holdSlot(int $roomId, Carbon $startTime, string $token): void
    {
        $this->checkTimeConflict($roomId, $startTime, $startTime->copy()->addMinutes(1)); // Quick check

        $cacheKey = "hold_room_{$roomId}_time_{$startTime->timestamp}";
        $locked = Cache::add($cacheKey, $token, now()->addMinutes(10));

        if (!$locked && Cache::get($cacheKey) !== $token) {
            abort(409, 'Цей час зараз оформлює інший користувач. Спробуйте пізніше або виберіть інший час.');
        }
    }

    /**
     * Release a held slot from cache.
     *
     * @param int $roomId
     * @param Carbon $startTime
     * @return void
     */
    public function releaseHoldToken(int $roomId, Carbon $startTime): void
    {
        $cacheKey = "hold_room_{$roomId}_time_{$startTime->timestamp}";
        Cache::forget($cacheKey);
    }

    /**
     * Calculate the final price based on time and players.
     */
    private function calculatePrice(Room $room, Carbon $startTime, int $playersCount): int
    {
        $basePrice = $startTime->isWeekend() ? $room->weekend_price : $room->weekday_price;
        $lateSurcharge = $startTime->hour >= 21 ? 20000 : 0;

        $extraPlayers = max(0, $playersCount - $room->min_players);
        $playersSurcharge = $extraPlayers * 10000;

        return $basePrice + $lateSurcharge + $playersSurcharge;
    }

    /**
     * Validate min and max players.
     */
    private function validatePlayersCount(Room $room, int $playersCount): void
    {
        if ($playersCount < $room->min_players || $playersCount > $room->max_players) {
            abort(422, "Кількість гравців має бути від {$room->min_players} до {$room->max_players}");
        }
    }

    /**
     * Prevent spam bookings from the same user/phone.
     */
    private function checkActiveBookingsLimit(?int $userId, string $phone, ?string $email): void
    {
        $activeBookingsCount = Booking::whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '>=', now())
            ->where(function ($query) use ($userId, $phone, $email) {
                $query->where(function($inner) use ($phone, $email) {
                    $inner->where('guest_phone', $phone);
                    if (!empty($email)) {
                        $inner->orWhere('guest_email', $email);
                    }
                });
                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })->count();

        if ($activeBookingsCount >= 2) {
            abort(429, 'Ви вже маєте 2 активних бронювання. Щоб забронювати більше ігор, зателевонуйте нам.');
        }
    }

    /**
     * Check if someone else is currently holding this slot.
     */
    private function verifyHoldToken(int $roomId, Carbon $startTime, string $token): void
    {
        $cacheKey = "hold_room_{$roomId}_time_{$startTime->timestamp}";
        $holder = Cache::get($cacheKey);

        if ($holder && $holder !== $token) {
            abort(409, 'На жаль, хтось інший вже почав оформлювати цей час.');
        }
    }

    /**
     * Check DB for overlapping bookings.
     */
    private function checkTimeConflict(int $roomId, Carbon $startTime, Carbon $endTime): void
    {
        $isConflict = Booking::where('room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->lockForUpdate()
            ->exists();

        if ($isConflict) {
            abort(422, 'На жаль, цей час вже заброньовано іншими гравцями.');
        }
    }
}