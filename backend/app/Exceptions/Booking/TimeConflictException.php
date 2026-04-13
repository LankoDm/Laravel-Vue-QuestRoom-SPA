<?php

namespace App\Exceptions\Booking;

use Exception;
use Illuminate\Http\JsonResponse;

class TimeConflictException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Цей час вже заброньовано або оформлюється.'
        ], 409);
    }
}