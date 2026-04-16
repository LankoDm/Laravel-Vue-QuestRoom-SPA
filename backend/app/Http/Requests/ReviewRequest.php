<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Booking;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($booking = $this->route('booking')) {
            $this->merge([
                'room_id' => $booking->room_id ?? (int) Booking::find($booking)?->room_id
            ]);
        }
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
            'message' => 'required|string|min:2',
            'rating' => 'nullable|integer|min:1|max:5',
        ];
    }
    public function messages(): array
    {
        return [
            'room_id.required' => 'Сталася помилка: не вказано кімнату.',
            'message.required' => 'Текст відгуку не може бути порожнім.',
            'message.min' => 'Напишіть хоча б кілька слів про ваші враження.',
            'rating.min' => 'Мінімальна оцінка - 1 зірка.',
            'rating.max' => 'Максимальна оцінка - 5 зірок.',
        ];
    }
}
