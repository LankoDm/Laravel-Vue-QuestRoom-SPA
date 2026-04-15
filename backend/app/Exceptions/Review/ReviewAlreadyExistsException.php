<?php

namespace App\Exceptions\Review;

use Exception;
use Illuminate\Http\JsonResponse;

class ReviewAlreadyExistsException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Ви вже залишали відгук на цю кімнату.'
        ], 422); // 422 Unprocessable Entity
    }
}
