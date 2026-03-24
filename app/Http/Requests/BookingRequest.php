<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'players_count' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_phone' => ['required', 'string', 'regex:/^\+380 \(\d{2}\) \d{3}-\d{2}-\d{2}$/'],
            'guest_email' => 'nullable|email|max:255',
            'comment' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,paypal',
            'total_price' => 'required|integer',
        ];
    }
    public function messages(): array
    {
        return [
            'guest_phone.regex' => 'Номер телефону має бути у форматі +380 (XX) XXX-XX-XX',
        ];
    }
}
