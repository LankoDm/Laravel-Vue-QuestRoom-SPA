<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'hold_token' => 'required|string',
        ];
    }
}
