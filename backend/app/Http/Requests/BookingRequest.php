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
            'payment_method' => 'required|in:cash,card',
            'total_price' => 'required|integer',
        ];
    }
    public function messages(): array
    {
        return [
            'room_id.required' => 'Будь ласка, оберіть квест-кімнату.',
            'room_id.exists' => 'Обрана кімната не знайдена.',
            'start_time.required' => 'Оберіть дату та час гри.',
            'start_time.after' => 'Час бронювання має бути в майбутньому.',
            'players_count.required' => 'Вкажіть кількість гравців.',
            'players_count.min' => 'У грі має брати участь хоча б одна людина.',
            'guest_name.required' => 'Введіть ваше ім\'я.',
            'guest_name.max' => 'Ім\'я занадто довге.',
            'guest_phone.required' => 'Номер телефону обов\'язковий для підтвердження броні.',
            'guest_phone.regex' => 'Номер телефону має бути у форматі +380 (XX) XXX-XX-XX',
            'guest_email.email' => 'Введіть коректну електронну адресу.',
            'payment_method.required' => 'Оберіть зручний спосіб оплати.',
            'payment_method.in' => 'Обрано недопустимий спосіб оплати.',
        ];
    }
}
