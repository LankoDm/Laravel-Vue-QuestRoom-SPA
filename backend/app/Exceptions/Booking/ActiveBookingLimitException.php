<?php

namespace App\Exceptions\Booking;

use Exception;
use Illuminate\Http\JsonResponse;

class ActiveBookingLimitException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage()
        ], 429);
    }
}