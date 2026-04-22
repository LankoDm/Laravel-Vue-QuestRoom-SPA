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
use App\Exceptions\Booking\TimeConflictException;
use App\Exceptions\Booking\InvalidPlayerCountException;
use App\Exceptions\Booking\ActiveBookingLimitException;
use InvalidArgumentException;
use \Illuminate\Support\Facades\Mail;
use \App\Mail\BookingConfirmed;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BookingService
{
    private const GUEST_PAYMENT_TOKEN_TTL_MINUTES = 20;

    /**
     * Get paginated and filtered bookings for Admin/Manager dashboard.
     */
    public function getFilteredBookings(Request $request): LengthAwarePaginator
    {
        $query = Booking::with(['room', 'user']);

        // Only admins/managers should hit this
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('statuses')) {
            $query->whereIn('status', (array) $request->statuses);
        }

        if ($request->filled('dateMode') && $request->dateMode !== 'all') {
            if ($request->dateMode === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($request->dateMode === 'custom' && $request->filled('customDate')) {
                $query->whereDate('created_at', $request->customDate);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $searchDigits = preg_replace('/\D/', '', $search);

            $query->where(function ($q) use ($search, $searchDigits) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%");
                  
                if (strlen($searchDigits) > 0) {
                     $q->orWhereRaw("REGEXP_REPLACE(guest_phone, '[^0-9]', '') LIKE ?", ["%{$searchDigits}%"]);
                }

                $q->orWhereHas('user', function ($userQuery) use ($search, $searchDigits) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                              
                    if (strlen($searchDigits) > 0) {
                        $userQuery->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ["%{$searchDigits}%"]);
                    }
                });
            });
        }

        // Order by latest created
        $query->latest();

        return $query->paginate((int) $request->input('per_page', 20));
    }

    /**
     * Create a new booking and dispatch events.
     */
    public function createBooking(array $data, ?int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($room->duration_minutes);

            $this->validatePlayersCount($room, $data['players_count']);
            $this->checkActiveBookingsLimit($userId, $data['guest_phone'], $data['guest_email'] ?? null);
            $this->verifyHoldToken($room->id, $startTime, $data['hold_token']);
            $this->checkTimeConflict($room->id, $startTime, $endTime);

            $finalPrice = $this->calculatePrice($room, $startTime, $data['players_count']);

            if (isset($data['total_price']) && $finalPrice !== (int) $data['total_price']) {
                throw new TimeConflictException('Ціна змінилася через зміну умов. Будь ласка, оновіть сторінку.');
            }
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
     * Issue a short-lived token that allows a guest booking to initialize card payment.
     */
    public function issueGuestPaymentToken(Booking $booking): ?string
    {
        if ($booking->user_id !== null || $booking->payment_method !== 'card') {
            return null;
        }

        $token = Str::random(64);

        Cache::put(
            $this->guestPaymentTokenCacheKey($booking->id),
            hash('sha256', $token),
            now()->addMinutes(self::GUEST_PAYMENT_TOKEN_TTL_MINUTES)
        );

        return $token;
    }

    /**
     * Validate a guest payment token for the given booking.
     */
    public function validateGuestPaymentToken(Booking $booking, ?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $storedHash = Cache::get($this->guestPaymentTokenCacheKey($booking->id));

        return is_string($storedHash) && hash_equals($storedHash, hash('sha256', $token));
    }

    /**
     * Confirm booking and dispatch the finish job.
     */
    public function confirmBooking(Booking $booking): void
    {
        if ($booking->status !== 'pending') {
            throw new InvalidArgumentException('Бронювання вже оброблено');
        }

        $booking->update(['status' => 'confirmed']);

        $customerEmail = $booking->guest_email ?? $booking->user?->email;
        if ($customerEmail) {
            Mail::to($customerEmail)->queue(new BookingConfirmed($booking));
        }

        $finishTime = Carbon::parse($booking->end_time);
        FinishBookingJob::dispatch($booking->id)->delay($finishTime);
    }

    /**
     * Hold a specific time slot in cache.
     */
    public function holdSlot(int $roomId, Carbon $startTime, string $token): void
    {
        $this->checkTimeConflict($roomId, $startTime, $startTime->copy()->addMinutes(1));

        $cacheKey = "hold_room_{$roomId}_time_{$startTime->timestamp}";
        $locked = Cache::add($cacheKey, $token, now()->addMinutes(10));

        if (!$locked && Cache::get($cacheKey) !== $token) {
            throw new TimeConflictException('Цей час зараз оформлює інший користувач. Спробуйте пізніше або виберіть інший час.');
        }
    }

    /**
     * Release a held slot from cache.
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
            throw new InvalidPlayerCountException("Кількість гравців має бути від {$room->min_players} до {$room->max_players}");
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
            throw new ActiveBookingLimitException('Ви вже маєте 2 активних бронювання. Щоб забронювати більше ігор, зателевонуйте нам.');
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
            throw new TimeConflictException('На жаль, хтось інший вже почав оформлювати цей час.');
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
            ->exists();

        if ($isConflict) {
            throw new TimeConflictException('На жаль, цей час вже заброньовано іншими гравцями.');
        }
    }

    private function guestPaymentTokenCacheKey(int $bookingId): string
    {
        return "guest_payment_token_booking_{$bookingId}";
    }
}